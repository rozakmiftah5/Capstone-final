<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Notifications\MaterialRequestCompleted;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaterialRequestController extends Controller
{
    public function create(): View
    {
        return view('material-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'letter_number' => ['nullable', 'string', 'max:50'],
            'requesting_division' => ['nullable', 'string', 'max:100'],
            'requesting_department' => ['nullable', 'string', 'max:100'],
            'request_date' => ['nullable', 'date'],
            'materials' => ['required', 'array', 'min:1', 'max:10'],
            'materials.*.mtart' => ['required', 'string', 'max:10'],
            'materials.*.werks' => ['nullable', 'string', 'max:4'],
            'materials.*.lgort' => ['required', 'string', 'max:4'],
            'materials.*.maktx' => ['required', 'string', 'max:40'],
            'materials.*.meins' => ['required', 'string', 'max:10'],
            'materials.*.purchasingGroup' => ['required', 'string', 'max:20'],
            'materials.*.matkl' => ['required', 'string', 'max:20'],
            'materials.*.dismm' => ['nullable', 'string', 'max:2'],
            'materials.*.mtvfp' => ['nullable', 'string', 'max:2'],
            'materials.*.sernp' => ['nullable', 'string', 'max:4'],
            'materials.*.prctr' => ['nullable', 'string', 'max:10'],
            'materials.*.bklas' => ['required', 'string', 'max:20'],
            'materials.*.vprsv' => ['nullable', 'string', 'max:1'],
            'materials.*.peinh' => ['nullable', 'integer', 'min:1'],
            'materials.*.verpr' => ['required', 'numeric', 'min:0'],
            'materials.*.bwtty' => ['nullable', 'string', 'max:1'],
            'materials.*.longText' => ['required', 'string', 'max:132'],
        ]);

        $materialRequest = DB::transaction(function () use ($request, $validated): MaterialRequest {
            $materialRequest = MaterialRequest::create([
                'request_number' => 'LPPM-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $request->user()->user_id,
                'letter_number' => $validated['letter_number'] ?? null,
                'requesting_division' => $validated['requesting_division'] ?? null,
                'requesting_department' => $validated['requesting_department'] ?? null,
                'request_date' => $validated['request_date'] ?? null,
            ]);

            foreach ($validated['materials'] as $material) {
                $materialRequest->items()->create([
                    'material_description' => $material['maktx'],
                    'details' => $material,
                ]);
            }

            return $materialRequest;
        });

        return to_route('material-requests.create')
            ->with('success', "Pengajuan {$materialRequest->request_number} berhasil dikirim.");
    }

    public function index(): View
    {
        $requests = MaterialRequest::query()
            ->with(['requester:user_id,username,email', 'items:id,material_request_id,material_description,sap_material_code'])
            ->latest()
            ->paginate(20);

        return view('material-requests.index', compact('requests'));
    }

    public function show(MaterialRequest $materialRequest): View
    {
        abort_unless(
            request()->user()->role === 'Admin' || $materialRequest->user_id === request()->user()->user_id,
            403
        );

        $materialRequest->load([
            'requester:user_id,username,email',
            'items:id,material_request_id,material_description,details,sap_material_code',
        ]);

        request()->user()->unreadNotifications
            ->filter(fn ($notification): bool => (int) ($notification->data['material_request_id'] ?? 0) === $materialRequest->id)
            ->each(fn ($notification) => $notification->markAsRead());

        return view('material-requests.show', compact('materialRequest'));
    }

    public function download(MaterialRequest $materialRequest): Response
    {
        abort_unless(
            request()->user()->role === 'Admin' || $materialRequest->user_id === request()->user()->user_id,
            403
        );

        $materialRequest->load([
            'requester:user_id,username,email',
            'items:id,material_request_id,material_description,details,sap_material_code',
        ]);

        return Pdf::loadView('material-requests.pdf', compact('materialRequest'))
            ->setPaper('a4')
            ->download("Form-LPPM-{$materialRequest->request_number}.pdf");
    }

    public function complete(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless($request->user()->role === 'Admin', 403);

        $items = $materialRequest->items()->get(['id']);
        $rules = ['sap_codes' => ['required', 'array']];
        foreach ($items as $item) {
            $rules["sap_codes.{$item->id}"] = ['required', 'string', 'max:40'];
        }

        $validated = Validator::make($request->all(), $rules, [
            'sap_codes.*.required' => 'Nomor kode material SAP wajib diisi untuk setiap material.',
            'sap_codes.*.max' => 'Nomor kode material SAP maksimal 40 karakter.',
        ])->validate();

        $submittedItemIds = array_map('intval', array_keys($validated['sap_codes']));
        $requestItemIds = $items->modelKeys();
        if (array_diff($submittedItemIds, $requestItemIds) !== []) {
            throw ValidationException::withMessages([
                'sap_codes' => 'Data kode material tidak sesuai dengan item pengajuan.',
            ]);
        }

        DB::transaction(function () use ($materialRequest, $validated, $requestItemIds): void {
            $lockedRequest = MaterialRequest::query()
                ->whereKey($materialRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status === 'completed') {
                return;
            }

            foreach ($requestItemIds as $itemId) {
                $lockedRequest->items()
                    ->whereKey($itemId)
                    ->update(['sap_material_code' => $validated['sap_codes'][$itemId]]);
            }

            $lockedRequest->update([
                'status' => 'completed',
                'read_at' => $lockedRequest->read_at ?? now(),
                'completed_at' => now(),
            ]);

            if ($lockedRequest->requester !== null) {
                $lockedRequest->requester->notify(new MaterialRequestCompleted($lockedRequest));
            }
        });

        return to_route('material-requests.show', $materialRequest)
            ->with('success', "Pengajuan {$materialRequest->request_number} selesai diproses.");
    }

    public function markRead(MaterialRequest $materialRequest): RedirectResponse
    {
        $materialRequest->update([
            'read_at' => $materialRequest->read_at ?? now(),
            'status' => $materialRequest->status === 'new' ? 'read' : $materialRequest->status,
        ]);

        return back()->with('success', "Pengajuan {$materialRequest->request_number} ditandai telah dibaca.");
    }
}
