@extends('layouts.app')

@section('title', 'Inbox Pengajuan Penambahan Kode Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content">@include('layouts.navigation')
@if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
<div class="content-body"><section class="card">
<div class="page-header-action"><div><h1 style="margin:0;">Inbox Pengajuan Penambahan Kode Material</h1><p style="color:#64748b;margin-top:6px;">Pengajuan yang dikirim melalui Form LPPM.</p></div><a href="{{ route('material-requests.create') }}" class="btn btn-primary">Buat Pengajuan</a></div>
<div class="table-container"><table class="table-modern"><thead><tr><th>No. Pengajuan</th><th>Nomor Surat</th><th>Pemohon</th><th>Divisi / Dinas</th><th>Material</th><th>Feedback / Nomor Kode Material</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($requests as $materialRequest)
<tr><td style="font-weight:700;color:var(--primary-blue);">{{ $materialRequest->request_number }}</td><td>{{ $materialRequest->letter_number ?: '-' }}</td>
<td>{{ $materialRequest->requester?->username ?? $materialRequest->requester?->email ?? 'Pengguna terhapus' }}</td>
<td>{{ $materialRequest->requesting_division ?: '-' }} / {{ $materialRequest->requesting_department ?: '-' }}</td>
<td>{{ $materialRequest->items->pluck('material_description')->join(', ') ?: '-' }}</td><td>{{ $materialRequest->items->pluck('sap_material_code')->filter()->join(', ') ?: 'Belum ada feedback' }}</td><td>{{ $materialRequest->request_date?->format('d/m/Y') ?? $materialRequest->created_at->format('d/m/Y') }}</td>
<td><span class="app-status-pill {{ $materialRequest->read_at ? 'is-complete' : 'is-critical' }}">{{ $materialRequest->read_at ? $materialRequest->status : 'Baru' }}</span></td>
<td><div class="request-inbox-actions"><a class="btn btn-primary btn-sm" href="{{ route('material-requests.show', $materialRequest) }}">Lihat detail</a>@if(!$materialRequest->read_at)<form method="POST" action="{{ route('material-requests.read', $materialRequest) }}">@csrf<button class="btn btn-sm request-inbox-read-button" type="submit">Tandai dibaca</button></form>@endif</div></td></tr>
@empty<tr><td colspan="9" style="text-align:center;padding:35px;color:#64748b;">Belum ada pengajuan material.</td></tr>@endforelse
</tbody></table></div><div class="app-pagination">{{ $requests->links() }}</div>
</section></div>
</main></div>
@endsection
