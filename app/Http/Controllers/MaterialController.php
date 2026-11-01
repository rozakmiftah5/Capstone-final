<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- 1. Import Facade Auth

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::with('warehouse')->get();
        return view('materials.index', compact('materials'));
    }

    public function create()
    {
        // Pengecekan Hak Akses Admin
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('materials.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menambah material.');
        }

        $warehouses = Warehouse::all();
        
        return view('materials.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'material_code'  => 'required|unique:materials,material_code',
            'material_name'  => 'required',
            'warehouse_id'   => 'required',
            'material_stock' => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $data['last_updated'] = now()->toDateString();

        Material::create($data);

        return redirect()->route('materials.index')->with('success', 'Material berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('materials.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat mengedit material.');
        }

        $material = Material::findOrFail($id);
        $warehouses = Warehouse::all();
        
        return view('materials.edit', compact('material', 'warehouses'));
    }

    public function update(Request $request, int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        $material = Material::findOrFail($id);

        $request->validate([
            'material_code'  => 'required|unique:materials,material_code,' . $id . ',material_id',
            'material_name'  => 'required',
            'warehouse_id'   => 'required',
            'material_stock' => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $data['last_updated'] = now()->toDateString();

        $material->update($data);

        return redirect()->route('materials.index')->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('materials.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menghapus material.');
        }

        $material = Material::findOrFail($id);
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material berhasil dihapus.');
    }
}