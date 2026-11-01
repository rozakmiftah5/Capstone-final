@extends('layouts.app')

@section('title', 'Manajemen Gudang - Master Material')
@section('body-class', 'app-body app-warehouse-page')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')

        <div class="content-body warehouse-content">
            <header class="warehouse-heading">
                <div>
                    <p class="warehouse-eyebrow">DATA MASTER</p>
                    <h1>Manajemen Gudang</h1>
                    <p class="warehouse-description">Kelola daftar lokasi penyimpanan material.</p>
                </div>
                @if(Auth::user()->role === 'Admin')
                    <a href="{{ route('warehouses.create') }}" class="warehouse-primary-button">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Tambah Gudang</span>
                    </a>
                @endif
            </header>

            @if(session('success'))
                <div class="warehouse-alert is-success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="warehouse-alert is-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <section class="warehouse-card" aria-labelledby="warehouse-list-heading">
                <div class="warehouse-card-heading">
                    <div>
                        <h2 id="warehouse-list-heading">Daftar Gudang</h2>
                        <p>Lokasi gudang yang terdaftar pada aplikasi.</p>
                    </div>
                    <span class="warehouse-count">
                        <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                        {{ $warehouses->count() }} gudang
                    </span>
                </div>

                <div class="warehouse-table-wrap">
                    <table class="warehouse-table">
                        <thead>
                            <tr>
                                <th scope="col" class="warehouse-number-column">No.</th>
                                <th scope="col">Kode Gudang</th>
                                <th scope="col">Nama Gudang</th>
                                @if(Auth::user()->role === 'Admin')
                                    <th scope="col" class="warehouse-actions-heading">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warehouses as $index => $warehouse)
                                <tr>
                                    <td class="warehouse-number-column">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="warehouse-code">
                                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                            {{ $warehouse->warehouse_code }}
                                        </span>
                                    </td>
                                    <td class="warehouse-name">{{ $warehouse->warehouse_name }}</td>
                                    @if(Auth::user()->role === 'Admin')
                                        <td>
                                            <div class="warehouse-actions">
                                                <a href="{{ route('warehouses.edit', $warehouse->warehouse_id) }}" class="warehouse-action is-edit">
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    <span>Edit</span>
                                                </a>
                                                <form action="{{ route('warehouses.destroy', $warehouse->warehouse_id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data gudang ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="warehouse-action is-delete">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                        <span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->role === 'Admin' ? 4 : 3 }}">
                                        <div class="warehouse-empty">
                                            <span><i class="fa-solid fa-warehouse" aria-hidden="true"></i></span>
                                            <strong>Belum ada data gudang</strong>
                                            <p>Gudang yang ditambahkan akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <a href="{{ route('dashboard') }}" class="warehouse-back-link">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali ke Dashboard
            </a>
        </div>
    </main>
</div>
@endsection
