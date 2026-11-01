@extends('layouts.app')

@section('title', 'Stock Bahan Kimia (OS) - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">
    @include('layouts.navigation')
    @if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="app-flash" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;" role="alert">{{ $errors->first() }}</div>@endif
    <div class="content-body">
        @php($formatKg = fn ($quantity) => rtrim(rtrim(number_format((float) $quantity, 3, ',', '.'), '0'), ','))
        <section class="dashboard-stats" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));">
            <article class="card"><div class="stat-card-body"><div><h3>Total Stok</h3><p class="app-stat-value">{{ $formatKg($stockTotal) }} kg</p></div><div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div></div></article>
            <article class="card"><div class="stat-card-body"><div><h3>Item Stok Kritis</h3><p class="app-stat-value">{{ number_format($criticalCount, 0, ',', '.') }}</p></div><div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div></div></article>
            <article class="card stock-os-export-card">
                <div class="stat-card-body">
                    <div>
                        <h3>Ekspor Laporan</h3>
                        <p class="stock-os-export-description">Unduh data Stock Bahan Kimia (OS) dan catatan pemakaian dalam format Excel.</p>
                        <a class="btn btn-primary" href="{{ route('stock-os.export', array_filter([
                            'search' => $validated['search'] ?? null,
                            'warehouse' => $validated['warehouse'] ?? null,
                            'critical' => !empty($validated['critical']) ? 1 : null,
                            'sort' => $validated['sort'] ?? null,
                        ], fn ($value) => $value !== null && $value !== '')) }}"><i class="fa-solid fa-file-excel"></i> Ekspor Excel</a>
                    </div>
                    <div class="stat-icon"><i class="fa-solid fa-file-export"></i></div>
                </div>
            </article>
        </section>
        @can('admin')
            <section class="card app-form-card">
                <div class="page-header-action"><h1 style="margin:0;">Manajemen Stock Bahan Kimia (OS)</h1><a href="#add-stock" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Stock Bahan Kimia (OS)</a></div>
                <div id="add-stock" class="app-form-card" style="padding:0;">
                    <h2>Tambah Stock Bahan Kimia (OS)</h2>
                    <form method="POST" action="{{ route('stock-os.store') }}">@csrf
                        <div class="app-form-grid">
                            <label>Nama Material<input name="material_name" value="{{ old('material_name') }}" required maxlength="255"></label>
                            <label>Gudang<select name="warehouse_name" required>
                                <option value="">Pilih gudang</option>
                                @foreach($managedWarehouses as $warehouse)
                                    <option value="{{ $warehouse->warehouse_name }}" @selected(old('warehouse_name') === $warehouse->warehouse_name)>{{ $warehouse->warehouse_name }}</option>
                                @endforeach
                            </select></label>
                            <label>Stok (kg)<input type="number" name="stock_qty" min="0" step="0.001" value="{{ old('stock_qty', 0) }}" required></label>
                            <label>Sisa PO (kg)<input type="number" name="outstanding_qty" min="0" step="0.001" value="{{ old('outstanding_qty', 0) }}" required></label>
                            <label>Safety Stock (kg)<input type="number" name="safety_stock" min="0" step="0.001" value="{{ old('safety_stock', 0) }}" required></label>
                            <label>Catatan<input name="notes" value="{{ old('notes') }}" maxlength="2000"></label>
                        </div>
                        <button class="btn btn-primary" type="submit">Simpan Stok</button>
                    </form>
                </div>
            </section>
        @else
            <section class="card"><h1 style="margin:0;">Manajemen Stock Bahan Kimia (OS)</h1><p style="color:#64748b;">Pantau jumlah stok, sisa PO, safety stock, dan mutasi penerimaan/pemakaian.</p></section>
        @endcan

        <section class="card stock-os-filter">
            <div class="stock-os-filter-heading">
                <h2>Filter Stock Bahan Kimia (OS)</h2>
                <p>Cari dan susun data stok. Ekspor Excel akan mengikuti filter yang dipilih.</p>
            </div>
            <form method="GET" action="{{ route('stock-os.index') }}">
                <div class="stock-os-filter-fields">
                    <label>Cari Material/Gudang<input type="search" name="search" value="{{ $validated['search'] ?? '' }}" placeholder="Nama material atau catatan"></label>
                    <label>Gudang<select name="warehouse"><option value="">Semua gudang</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse }}" @selected(($validated['warehouse'] ?? '') === $warehouse)>{{ $warehouse }}</option>@endforeach</select></label>
                    <label>Urutkan<select name="sort"><option value="name" @selected(($validated['sort'] ?? 'name')==='name')>Nama material A-Z</option><option value="stock" @selected(($validated['sort'] ?? '')==='stock')>Stok terendah</option><option value="outstanding" @selected(($validated['sort'] ?? '')==='outstanding')>Sisa PO tertinggi</option></select></label>
                </div>
                <div class="stock-os-filter-footer">
                    <label class="stock-os-critical-filter"><input type="checkbox" name="critical" value="1" @checked((bool)($validated['critical'] ?? false))> Stok kritis/kosong</label>
                    <div class="stock-os-filter-actions">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Terapkan Filter</button>
                        <a class="btn" href="{{ route('stock-os.index') }}">Reset</a>
                    </div>
                </div>
            </form>
            <div class="table-container"><table class="table-modern">
                <thead><tr><th>Nama Material</th><th>Gudang</th><th>Stok (kg)</th><th>Sisa PO (kg)</th><th>Safety Stock (kg)</th><th>Status</th><th>Catatan</th><th>Update</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    @php($statusClass = (float)$item->stock_qty <= 0 ? 'is-empty' : ((float)$item->stock_qty <= (float)$item->safety_stock ? 'is-critical' : 'is-complete'))
                    <tr>
                        <td style="font-weight:700;color:var(--primary-blue);">{{ $item->material_name }}</td><td>{{ $item->warehouse_name }}</td>
                        <td>{{ $formatKg($item->stock_qty) }} kg</td><td>{{ $formatKg($item->outstanding_qty) }} kg</td><td>{{ $formatKg($item->safety_stock) }} kg</td>
                        <td><span class="app-status-pill {{ $statusClass }}">{{ (float)$item->stock_qty <= 0 ? 'Stok Habis' : ((float)$item->stock_qty <= (float)$item->safety_stock ? 'Stok Kritis' : 'Aman') }}</span></td>
                        <td>{{ $item->notes ?: '-' }}</td><td>{{ $item->last_updated?->format('d/m/Y') ?? '-' }}</td>
                        <td><div class="app-inline-actions">
                            <a class="btn btn-sm btn-primary" href="{{ route('stock-os.history', $item) }}">Catatan Pemakaian</a>
                            @can('admin')
                                <a class="btn btn-sm btn-warning" href="{{ route('stock-os.edit', $item) }}">Edit</a>
                                <form action="{{ route('stock-os.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus item stok ini beserta riwayat transaksinya?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Hapus</button></form>
                            @endcan
                        </div>
                        @can('stock-operations')
                            <details style="margin-top:8px;"><summary style="cursor:pointer;color:#0d6efd;font-size:.8rem;">Catat transaksi</summary>
                                <form method="POST" action="{{ route('stock-os.use', $item) }}" class="app-form-card" style="padding:10px;">@csrf
                                    <strong>Catat Pemakaian</strong><div class="app-form-grid">
                                        <label>Jumlah (kg)<input name="qty" type="number" min="0.001" step="0.001" required></label>
                                        <label>Tanggal<input name="transaction_date" type="date" value="{{ today()->toDateString() }}" required></label>
                                        <label>Keterangan<input name="notes" maxlength="2000"></label>
                                    </div><button class="btn btn-sm btn-warning" type="submit">Kurangi Stok</button>
                                </form>
                                @can('stock-receipts')
                                    <form method="POST" action="{{ route('stock-os.receive', $item) }}" class="app-form-card" style="padding:10px;">@csrf
                                        <strong>Catat Penerimaan</strong><div class="app-form-grid">
                                            <label>Jumlah diterima (kg)<input name="qty" type="number" min="0.001" step="0.001" required></label>
                                            <label>Supplier<input name="supplier" maxlength="255" required></label>
                                            <label>Tanggal<input name="transaction_date" type="date" value="{{ today()->toDateString() }}" required></label>
                                            <label>Keterangan<input name="notes" maxlength="2000"></label>
                                        </div><button class="btn btn-sm btn-primary" type="submit">Tambah Stok</button>
                                    </form>
                                @endcan
                            </details>
                        @endcan
                        </td>
                    </tr>
                @empty<tr><td colspan="9" style="text-align:center;padding:35px;color:#64748b;">Belum ada data Stock Bahan Kimia (OS). Admin dapat menambahkan data secara manual.</td></tr>@endforelse
                </tbody>
            </table></div>
            <div class="app-pagination">{{ $items->links() }}</div>
        </section>
    </div>
    <footer class="app-page-footer"><span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span><span>Inventory Control System</span></footer>
</main></div>
@endsection
