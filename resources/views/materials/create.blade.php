@extends('layouts.app')

@section('title', 'Tambah Material - Master Material')
@section('body-class', 'app-body')

@section('content')
@include('layouts.navigation')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Tambah Material Baru</h1>

        <div class="bg-white shadow-md rounded-lg p-6">
            <form action="{{ route('materials.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode Material</label>
                        <input type="text" name="material_code" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('material_code') }}" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Material</label>
                        <input type="text" name="material_name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('material_name') }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tipe Material</label>
                        <input type="text" name="material_type" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_type') }}" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <input type="text" name="category" class="w-full px-3 py-2 border rounded-lg" value="{{ old('category') }}" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Satuan (Unit)</label>
                        <input type="text" name="unit" class="w-full px-3 py-2 border rounded-lg" value="{{ old('unit') }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Stok Material</label>
                        <input type="number" name="material_stock" class="w-full px-3 py-2 border rounded-lg" value="{{ old('material_stock', 0) }}" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Safety Stock</label>
                        <input type="number" name="safety_stock" class="w-full px-3 py-2 border rounded-lg" value="{{ old('safety_stock', 0) }}" min="0">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Sisa PO</label>
                        <input type="number" name="sisa_po" class="w-full px-3 py-2 border rounded-lg" value="{{ old('sisa_po', 0) }}" min="0">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Gudang</label>
                        <select name="warehouse_id" class="w-full px-3 py-2 border rounded-lg" required>
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->warehouse_id }}" {{ old('warehouse_id') == $warehouse->warehouse_id ? 'selected' : '' }}>
                                    {{ $warehouse->warehouse_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Valuation Class</label>
                        <input type="text" name="valuation_class" class="w-full px-3 py-2 border rounded-lg" value="{{ old('valuation_class') }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <input type="text" name="status" class="w-full px-3 py-2 border rounded-lg" value="{{ old('status') }}">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Catatan (Note)</label>
                        <input type="text" name="note" class="w-full px-3 py-2 border rounded-lg" value="{{ old('note') }}">
                    </div>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <a href="{{ route('materials.index') }}" class="text-gray-600 hover:underline">Kembali</a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
