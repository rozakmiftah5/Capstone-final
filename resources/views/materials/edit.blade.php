@extends('layouts.app')

@section('title', 'Edit Material - Master Material')
@section('body-class', 'app-body')

@section('content')
@include('layouts.navigation')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Data Material</h1>

        <div class="bg-white shadow-md rounded-lg p-6">
            <form action="{{ route('materials.update', $material->material_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode Material</label>
                        <input type="text" name="material_code" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_code', $material->material_code) }}" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Material</label>
                        <input type="text" name="material_name" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_name', $material->material_name) }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tipe Material</label>
                        <input type="text" name="material_type" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_type', $material->material_type) }}" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <input type="text" name="category" class="w-full px-3 py-2 border rounded-lg" value="{{ old('category', $material->category) }}" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Satuan (Unit)</label>
                        <input type="text" name="unit" class="w-full px-3 py-2 border rounded-lg" value="{{ old('unit', $material->unit) }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Stok Material</label>
                        <input type="number" name="material_stock" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_stock', $material->material_stock) }}" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Safety Stock</label>
                        <input type="number" name="safety_stock" class="w-full px-3 py-2 border rounded-lg" value="{{ old('safety_stock', $material->safety_stock) }}" min="0">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Sisa PO</label>
                        <input type="number" name="sisa_po" class="w-full px-3 py-2 border rounded-lg" value="{{ old('sisa_po', $material->sisa_po) }}" min="0">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Gudang</label>
                        <select name="warehouse_id" class="w-full px-3 py-2 border rounded-lg" required>
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->warehouse_id }}" {{ old('warehouse_id', $material->warehouse_id) == $warehouse->warehouse_id ? 'selected' : '' }}>
                                {{ $warehouse->warehouse_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Valuation Class</label>
                        <input type="text" name="valuation_class" class="w-full px-3 py-2 border rounded-lg" value="{{ old('valuation_class', $material->valuation_class) }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <input type="text" name="status" class="w-full px-3 py-2 border rounded-lg" value="{{ old('status', $material->status) }}">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Catatan (Note)</label>
                        <input type="text" name="note" class="w-full px-3 py-2 border rounded-lg" value="{{ old('note', $material->note) }}">
                    </div>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <a href="{{ route('materials.index') }}" class="text-gray-600 hover:underline">Kembali</a>
                    <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg shadow hover:bg-yellow-700 transition">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
@endsection
