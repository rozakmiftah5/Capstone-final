@extends('layouts.app')

@section('title', 'Catatan Pemakaian')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">@include('layouts.navigation')
<div class="content-body"><section class="card">
@php($formatKg = fn ($quantity) => rtrim(rtrim(number_format((float) $quantity, 3, ',', '.'), '0'), ','))
@if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
<div class="page-header-action"><div><h1 style="margin:0;">Catatan Pemakaian</h1><p style="color:#64748b;">{{ $stockOsItem->material_name }} · {{ $stockOsItem->warehouse_name }}</p></div><a class="btn" href="{{ route('stock-os.index') }}">Kembali ke Stock Bahan Kimia (OS)</a></div>
<div class="dashboard-stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
<div class="card"><h3>Stok saat ini</h3><p class="app-stat-value">{{ $formatKg($stockOsItem->stock_qty) }} kg</p></div>
<div class="card"><h3>Sisa PO</h3><p class="app-stat-value">{{ $formatKg($stockOsItem->outstanding_qty) }} kg</p></div>
</div>
<div class="table-container"><table class="table-modern"><thead><tr><th>Tanggal</th><th>Tipe</th><th>Jumlah (kg)</th><th>Supplier</th><th>Catatan</th><th>Dicatat oleh</th></tr></thead><tbody>
@forelse($transactions as $transaction)<tr><td>{{ $transaction->transaction_date->format('d/m/Y') }}</td><td><span class="app-status-pill {{ $transaction->type==='IN'?'is-complete':'is-critical' }}">{{ $transaction->type==='IN'?'Penerimaan':'Pemakaian' }}</span></td><td>{{ $formatKg($transaction->qty) }} kg</td><td>{{ $transaction->supplier ?: '-' }}</td><td>{{ $transaction->notes ?: '-' }}</td><td>{{ $transaction->user?->username ?? $transaction->user?->email ?? '-' }}</td></tr>
@empty<tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b;">Belum ada transaksi untuk material ini.</td></tr>@endforelse
</tbody></table></div><div class="app-pagination">{{ $transactions->links() }}</div>
</section></div></main></div>
@endsection
