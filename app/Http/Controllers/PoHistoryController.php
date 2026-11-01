<?php

namespace App\Http\Controllers;

use App\Models\PoHistory;
use App\Services\SpreadsheetImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'material_group' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:latest,oldest,price_high,price_low'],
        ]);

        $search = trim($validated['search'] ?? '');
        $sort = $validated['sort'] ?? 'latest';
        $order = match ($sort) {
            'oldest' => ['po_date', 'asc'],
            'price_high' => ['unit_price', 'desc'],
            'price_low' => ['unit_price', 'asc'],
            default => ['po_date', 'desc'],
        };

        $purchaseOrders = PoHistory::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('po_number', 'like', "%{$search}%")
                    ->orWhere('material_code', 'like', "%{$search}%")
                    ->orWhere('short_text', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhere('material_group', 'like', "%{$search}%");
            }))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('po_date', '>=', $request->date('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('po_date', '<=', $request->date('end_date')))
            ->when($request->filled('material_group'), fn ($query) => $query->where('material_group', $request->string('material_group')->toString()))
            ->orderBy($order[0], $order[1])
            ->orderBy('po_number')
            ->paginate(50)
            ->withQueryString();

        $materialGroups = PoHistory::query()
            ->whereNotNull('material_group')
            ->distinct()
            ->orderBy('material_group')
            ->pluck('material_group');

        return view('po-history.index', compact('purchaseOrders', 'materialGroups', 'validated'));
    }

    public function import(Request $request, SpreadsheetImportService $importer): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*' => ['required', 'array'],
        ]);

        $summary = $importer->importPoHistory($validated['rows']);
        $message = "Riwayat PO diperbarui: {$summary['imported']} baris diimpor, {$summary['skipped']} baris dilewati.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, ...$summary])
            : back()->with('success', $message);
    }
}
