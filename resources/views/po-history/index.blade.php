@extends('layouts.app')

@section('title', 'Riwayat Purchase Order - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">
        @include('layouts.navigation')
        @if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="app-flash" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;" role="alert">{{ $errors->first() }}</div>@endif
        <div class="content-body">
            <section class="card">
                <div class="page-header-action">
                    <div><h1 style="margin:0;">Riwayat Purchase Order</h1><p style="margin-top:6px;color:#64748b;">{{ number_format($purchaseOrders->total(), 0, ',', '.') }} baris riwayat pembelian.</p></div>
                </div>
                <form method="GET" action="{{ route('po-history.index') }}" class="app-form-grid">
                    <label>Pencarian<input type="search" name="search" value="{{ $validated['search'] ?? '' }}" placeholder="PO, kode material, vendor..."></label>
                    <label>Dari tanggal<input type="date" name="start_date" value="{{ $validated['start_date'] ?? '' }}"></label>
                    <label>Sampai tanggal<input type="date" name="end_date" value="{{ $validated['end_date'] ?? '' }}"></label>
                    <label>Material Group
                        <select name="material_group"><option value="">Semua group</option>
                            @foreach($materialGroups as $group)<option value="{{ $group }}" @selected(($validated['material_group'] ?? '') === $group)>{{ $group }}</option>@endforeach
                        </select>
                    </label>
                    <label>Urutkan
                        <select name="sort">
                            <option value="latest" @selected(($validated['sort'] ?? 'latest') === 'latest')>Tanggal terbaru</option>
                            <option value="oldest" @selected(($validated['sort'] ?? '') === 'oldest')>Tanggal terlama</option>
                            <option value="price_high" @selected(($validated['sort'] ?? '') === 'price_high')>Harga tertinggi</option>
                            <option value="price_low" @selected(($validated['sort'] ?? '') === 'price_low')>Harga terendah</option>
                        </select>
                    </label>
                    <div class="app-inline-actions" style="align-self:end;"><button type="submit" class="btn btn-primary">Terapkan</button><a href="{{ route('po-history.index') }}" class="btn">Reset</a></div>
                </form>
                <div class="table-container">
                    <table class="table-modern">
                        <thead><tr><th>Nomor PO</th><th>Tanggal</th><th>Kode Material</th><th>Deskripsi</th><th>Group</th><th>Vendor</th><th>Qty</th><th>Unit</th><th style="text-align:right;">Harga Satuan</th><th style="text-align:right;">Total</th></tr></thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                                <tr>
                                    <td style="font-weight:700;color:var(--primary-blue);">{{ $po->po_number }}</td>
                                    <td>{{ $po->po_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td style="font-family:monospace;">{{ $po->material_code }}</td>
                                    <td>{{ $po->short_text ?: '-' }}</td><td>{{ $po->material_group ?: '-' }}</td><td>{{ $po->vendor ?: '-' }}</td>
                                    <td>{{ number_format((float) $po->qty, 3, ',', '.') }}</td><td>{{ $po->uom ?: '-' }}</td>
                                    <td style="text-align:right;">{{ $po->currency }} {{ number_format((float) $po->unit_price, 0, ',', '.') }}</td>
                                    <td style="text-align:right;font-weight:700;">{{ $po->currency }} {{ number_format((float) $po->total_price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" style="text-align:center;padding:38px;color:#64748b;">Belum ada riwayat PO. Admin dapat mengimpor berkas Excel.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="app-pagination">{{ $purchaseOrders->links() }}</div>
            </section>
        </div>
        <footer class="app-page-footer"><span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span><span>Inventory Control System</span></footer>
    </main></div>
@endsection
