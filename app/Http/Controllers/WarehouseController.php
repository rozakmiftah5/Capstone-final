<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::all();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('warehouses.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menambah gudang.');
        }

        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'warehouse_code' => 'required|unique:warehouses,warehouse_code',
            'warehouse_name' => 'required',
        ]);

        Warehouse::create($request->all());

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit($warehouse_id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('warehouses.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat mengedit gudang.');
        }

        $warehouse = Warehouse::findOrFail($warehouse_id);
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, $warehouse_id)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        $warehouse = Warehouse::findOrFail($warehouse_id);

        $request->validate([
            'warehouse_code' => 'required|unique:warehouses,warehouse_code,' . $warehouse_id . ',warehouse_id',
            'warehouse_name' => 'required',
        ]);

        $warehouse->update($request->all());

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil diperbarui.');
    }

    public function destroy($warehouse_id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('warehouses.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menghapus gudang.');
        }

        $warehouse = Warehouse::findOrFail($warehouse_id);
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil dihapus.');
    }
}