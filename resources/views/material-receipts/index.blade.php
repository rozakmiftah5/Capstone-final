@extends('layouts.app')

@section('title', 'Penerimaan Barang - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')
        <div class="container mx-auto px-4 py-8">
        <section class="card app-form-card">
            <div class="page-header-action">
                <div>
                    <h1 style="margin:0;">Penerimaan Barang Non-OS</h1>
                    <p style="margin:6px 0 0;color:#64748b;">Catat barang yang diterima dari vendor. Stok Data Material bertambah jika kode katalog sudah terdaftar di sana.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="app-flash" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="app-flash" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;" role="alert">{{ $errors->first() }}</div>
            @endif

            @if($materials->isEmpty())
                <div class="app-flash" role="status">
                    Sebelum mencatat penerimaan, pastikan data material sudah tersedia. Material belum tersedia.
                </div>
            @endif
            @if($warehouses->isEmpty())
                <div class="app-flash" role="status">
                    Sebelum mencatat penerimaan, tambahkan gudang melalui Manajemen Gudang.
                </div>
            @endif

            <form method="POST" action="{{ route('material-receipts.store') }}">
                @csrf
                <div class="app-form-grid">
                    <label>
                        Material
                        <select name="master_material_id" required>
                            <option value="">Pilih material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}" @selected((string) old('master_material_id') === (string) $material->id)>
                                    {{ $material->material_code }} — {{ $material->short_text }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Nama Vendor
                        <input type="text" name="supplier_name" list="receipt-suppliers" value="{{ old('supplier_name') }}" maxlength="255" placeholder="Pilih atau ketik nama vendor" required>
                        <datalist id="receipt-suppliers">
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->suppllier_name }}"></option>
                            @endforeach
                        </datalist>
                    </label>
                    <label>
                        Gudang
                        <select name="warehouse_id" required>
                            <option value="">Pilih gudang</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->warehouse_id }}" @selected((string) old('warehouse_id') === (string) $warehouse->warehouse_id)>
                                    {{ $warehouse->warehouse_name }} ({{ $warehouse->warehouse_code }})
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Jumlah diterima
                        <input type="number" name="quantity" min="1" step="1" value="{{ old('quantity') }}" required>
                    </label>
                    <label>
                        Tanggal penerimaan
                        <input type="date" name="received_at" value="{{ old('received_at', today()->toDateString()) }}" required>
                    </label>
                    <label>
                        Nomor PO
                        <input name="purchase_order_number" value="{{ old('purchase_order_number') }}" maxlength="100" placeholder="Opsional">
                    </label>
                    <label>
                        Nomor surat jalan
                        <input name="delivery_note_number" value="{{ old('delivery_note_number') }}" maxlength="100" placeholder="Opsional">
                    </label>
                    <label>
                        Catatan
                        <input name="notes" value="{{ old('notes') }}" maxlength="2000" placeholder="Keterangan penerimaan (opsional)">
                    </label>
                </div>
                <div class="app-inline-actions">
                    <button class="btn btn-primary" type="submit" @disabled($materials->isEmpty() || $warehouses->isEmpty())>
                        <i class="fa-solid fa-truck-ramp-box"></i> Simpan Penerimaan
                    </button>
                </div>
            </form>
        </section>

        <section class="card" style="margin-top:20px;">
            <div class="page-header-action">
                <div>
                    <h2 style="margin:0;">Riwayat Penerimaan</h2>
                    <p style="margin:6px 0 0;color:#64748b;">Penerimaan terbaru ditampilkan terlebih dahulu.</p>
                </div>
            </div>
            <div class="table-container">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Material</th>
                            <th>Vendor</th>
                            <th>Jumlah</th>
                            <th>Nomor PO</th>
                            <th>Surat Jalan</th>
                            <th>Gudang</th>
                            <th>Diterima oleh</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receipts as $receipt)
                            <tr>
                                <td>{{ $receipt->received_at->format('d/m/Y') }}</td>
                                <td>{{ $receipt->material_code ?: ($receipt->material?->material_code ?? '-') }} — {{ $receipt->material_name ?: ($receipt->material?->material_name ?? '-') }}</td>
                                <td>{{ $receipt->supplier_name ?: ($receipt->supplier?->suppllier_name ?? '-') }}</td>
                                <td>{{ number_format($receipt->quantity, 0, ',', '.') }} {{ $receipt->unit ?: '-' }}</td>
                                <td>{{ $receipt->purchase_order_number ?: '-' }}</td>
                                <td>{{ $receipt->delivery_note_number ?: '-' }}</td>
                                <td>{{ $receipt->warehouse?->warehouse_name ?? $receipt->material?->warehouse?->warehouse_name ?? '-' }}</td>
                                <td>{{ $receipt->receiver?->email ?? '-' }}</td>
                                <td>{{ $receipt->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="padding:30px;text-align:center;color:#64748b;">Belum ada penerimaan barang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="app-pagination">{{ $receipts->links() }}</div>
        </section>
        </div>
    </main>
</div>
@endsection
