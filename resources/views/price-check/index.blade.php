@extends('layouts.app')

@section('title', 'Cek Harga Material - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">
        @include('layouts.navigation')
        <div class="content-body">
            <section class="card" style="max-width:900px;margin:25px auto;">
                <div style="max-width:650px;margin:15px auto 28px;text-align:center;">
                    <h1 style="margin-bottom:12px;">Cek Harga Terakhir</h1>
                    <p style="color:#64748b;">Cari berdasarkan nomor atau nama material untuk melihat harga dan vendor dari pembelian terbaru.</p>
                    <form method="GET" action="{{ route('price-check') }}" class="app-inline-actions" style="margin-top:18px;">
                        <input class="form-control" name="code" value="{{ $code }}" placeholder="Masukkan nomor atau nama material" required aria-label="Nomor atau nama material">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                    </form>
                </div>
                @if($latest)
                    <div class="card" style="border-left:5px solid var(--primary-blue);">
                        <div class="page-header-action">
                            <div><span class="badge badge-success">Data ditemukan</span><h2 style="margin:10px 0 4px;">{{ $material?->short_text ?? $latest->short_text ?? 'Material ' . $code }}</h2>
                                <p style="color:#64748b;">Kode: {{ $material?->material_code ?? $latest->material_code }} · Group: {{ $material?->material_group ?? $latest->material_group ?? '-' }} · Type: {{ $material?->material_type ?? '-' }}</p>
                            </div>
                            <div style="text-align:right;"><label class="label-sm">Tanggal pembelian terakhir</label><strong>{{ $latest->po_date?->format('d/m/Y') ?? '-' }}</strong></div>
                        </div>
                        <label class="label-sm">Harga Satuan Terakhir</label>
                        <div class="app-stat-value">{{ $latest->currency }} {{ number_format((float) $latest->unit_price, 0, ',', '.') }}</div>
                        <p style="margin-top:10px;color:#475569;"><i class="fa-solid fa-building" style="color:var(--primary-blue);"></i> {{ $latest->vendor ?: 'Vendor tidak tercatat' }}</p>
                    </div>
                    <div class="table-container">
                        <table class="table-modern"><thead><tr><th>Tanggal</th><th>PO</th><th>Vendor</th><th>Qty</th><th>Harga Satuan</th></tr></thead><tbody>
                            @foreach($history as $item)<tr><td>{{ $item->po_date?->format('d/m/Y') ?? '-' }}</td><td>{{ $item->po_number }}</td><td>{{ $item->vendor ?: '-' }}</td><td>{{ number_format((float) $item->qty, 3, ',', '.') }} {{ $item->uom }}</td><td>{{ $item->currency }} {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td></tr>@endforeach
                        </tbody></table>
                    </div>
                    <div class="app-pagination">{{ $history->links() }}</div>
                @elseif($hasSearched)
                    <div style="padding:28px;text-align:center;color:#64748b;"><i class="fa-solid fa-circle-question" style="font-size:2.4rem;"></i><h2>Material tidak ditemukan</h2><p>Periksa kembali nomor atau nama material yang Anda masukkan.</p></div>
                @endif
            </section>
        </div>
        <footer class="app-page-footer"><span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span><span>Inventory Control System</span></footer>
    </main></div>
@endsection
