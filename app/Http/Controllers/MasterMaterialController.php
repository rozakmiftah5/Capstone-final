<?php

namespace App\Http\Controllers;

use App\Models\MasterMaterial;
use App\Services\SpreadsheetImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $materials = MasterMaterial::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('material_code', 'like', "%{$search}%")
                    ->orWhere('short_text', 'like', "%{$search}%")
                    ->orWhere('material_group', 'like', "%{$search}%")
                    ->orWhere('material_type', 'like', "%{$search}%");
            }))
            ->orderBy('material_code')
            ->paginate(50)
            ->withQueryString();

        return view('master-materials.index', compact('materials', 'search'));
    }

    public function import(Request $request, SpreadsheetImportService $importer): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*' => ['required', 'array'],
        ]);

        $summary = $importer->importMaterials($validated['rows']);
        $message = "Katalog diperbarui: {$summary['imported']} material diimpor, {$summary['skipped']} baris dilewati.";

        return $request->expectsJson()
            ? response()->json(['message' => $message, ...$summary])
            : back()->with('success', $message);
    }
}
