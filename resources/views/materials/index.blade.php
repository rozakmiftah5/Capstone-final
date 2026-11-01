@extends('layouts.app')

@section('title', 'Daftar Material - Master Material')
@section('body-class', 'app-body')

@section('content')
@include('layouts.navigation')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Material</h1>
            @if(Auth::user()->role == 'Admin')
                <a href="{{ route('materials.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">+ Tambah Material</a>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="materials-table-wrap bg-white shadow-md rounded-lg">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-xs font-semibold tracking-wider">
                        <th class="px-4 py-3 text-left">No</th>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Nama Material</th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Kategori</th>
                        <th class="px-4 py-3 text-left">Gudang</th>
                        <th class="px-4 py-3 text-center">Stok</th>
                        <th class="px-4 py-3 text-center">Satuan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($materials as $index => $material)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-4">{{ $index + 1 }}</td>
                            <td class="px-4 py-4 font-medium">{{ $material->material_code }}</td>
                            <td class="px-4 py-4">{{ $material->material_name }}</td>
                            <td class="px-4 py-4">{{ $material->material_type ?? '-' }}</td>
                            <td class="px-4 py-4">{{ $material->category ?? '-' }}</td>
                            <td class="px-4 py-4">{{ $material->warehouse ? $material->warehouse->warehouse_name : '-' }}</td>
                            <td class="px-4 py-4 text-center font-semibold">{{ $material->material_stock }}</td>
                            <td class="px-4 py-4 text-center">{{ $material->unit ?? '-' }}</td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @if(Auth::user()->role == 'Admin')
                                    <a href="{{ route('materials.edit', $material->material_id) }}" class="text-yellow-600 hover:text-yellow-900 mr-3 font-semibold">Edit</a>
                                    <form action="{{ route('materials.destroy', $material->material_id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus material ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-5 text-center text-gray-500">Belum ada data material.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:underline">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
@endsection
