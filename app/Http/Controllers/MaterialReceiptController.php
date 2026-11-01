<?php

namespace App\Http\Controllers;

use App\Models\MasterMaterial;
use App\Models\Material;
use App\Models\MaterialReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialReceiptController extends Controller
{
    public function index(): View
    {
        $materials = MasterMaterial::query()
            ->orderBy('material_code')
            ->get(['id', 'material_code', 'short_text', 'uom']);
        $suppliers = Supplier::query()->orderBy('suppllier_name')->get(['supplier_id', 'supplier_code', 'suppllier_name']);
        $warehouses = Warehouse::query()->orderBy('warehouse_name')->get(['warehouse_id', 'warehouse_code', 'warehouse_name']);
        $receipts = MaterialReceipt::query()
            ->with([
                'material:material_id,material_code,material_name,warehouse_id',
                'material.warehouse:warehouse_id,warehouse_name',
                'warehouse:warehouse_id,warehouse_name',
                'supplier:supplier_id,supplier_code,suppllier_name',
                'receiver:user_id,email',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('material-receipts.index', compact('materials', 'suppliers', 'warehouses', 'receipts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'master_material_id' => ['required', 'integer', Rule::exists('master_materials', 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'warehouse_id')],
            'supplier_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'received_at' => ['required', 'date'],
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'delivery_note_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stockUpdated = DB::transaction(function () use ($request, $validated): bool {
            $catalogMaterial = MasterMaterial::query()->findOrFail($validated['master_material_id']);
            $material = Material::query()
                ->where('material_code', $catalogMaterial->material_code)
                ->where('warehouse_id', $validated['warehouse_id'])
                ->lockForUpdate()
                ->first();
            $supplierId = Supplier::query()
                ->where('suppllier_name', $validated['supplier_name'])
                ->value('supplier_id');

            MaterialReceipt::create([
                'material_id' => $material?->material_id,
                'material_code' => $catalogMaterial->material_code,
                'material_name' => $catalogMaterial->short_text,
                'warehouse_id' => $validated['warehouse_id'],
                'supplier_id' => $supplierId,
                'supplier_name' => $validated['supplier_name'],
                'quantity' => $validated['quantity'],
                'unit' => $catalogMaterial->uom ?: $material?->unit ?: '-',
                'received_at' => $validated['received_at'],
                'purchase_order_number' => $validated['purchase_order_number'] ?? null,
                'delivery_note_number' => $validated['delivery_note_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => $request->user()->user_id,
            ]);

            if ($material === null) {
                return false;
            }

            $material->increment('material_stock', $validated['quantity']);

            return true;
        });

        return to_route('material-receipts.index')
            ->with('success', $stockUpdated
                ? 'Penerimaan barang berhasil dicatat dan stok telah diperbarui.'
                : 'Penerimaan berhasil dicatat. Stok Data Material tidak berubah karena kode ini belum terdaftar pada gudang yang dipilih.');
    }
}
