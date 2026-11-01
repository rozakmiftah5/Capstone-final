@extends('layouts.app')

@section('title', 'Detail Pengajuan Penambahan Kode Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')
        <div class="content-body request-detail-content">
            @if(session('success'))
                <div class="app-flash" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="app-flash" role="alert">{{ $errors->first() }}</div>
            @endif

            <header class="request-detail-heading">
                <div>
                    <a href="{{ Auth::user()->role === 'Admin' ? route('material-requests.index') : route('dashboard') }}" class="request-back-link">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        {{ Auth::user()->role === 'Admin' ? 'Kembali ke Inbox' : 'Kembali ke Dashboard' }}
                    </a>
                    <p class="request-detail-eyebrow">INBOX PENGAJUAN</p>
                    <h1>Detail Form Pengajuan Penambahan Kode Material</h1>
                    <p>Nomor pengajuan: <strong>{{ $materialRequest->request_number }}</strong></p>
                </div>
                <span class="app-status-pill {{ $materialRequest->read_at ? 'is-complete' : 'is-critical' }}">
                    {{ $materialRequest->status === 'completed' ? 'Selesai' : ($materialRequest->read_at ? $materialRequest->status : 'Baru') }}
                </span>
            </header>

            <section class="request-detail-card" aria-labelledby="request-info-heading">
                <div class="request-detail-section-heading">
                    <h2 id="request-info-heading">Informasi Pengajuan</h2>
                </div>
                <dl class="request-detail-meta-grid">
                    <div><dt>Nomor Surat</dt><dd>{{ $materialRequest->letter_number ?: '-' }}</dd></div>
                    <div><dt>Divisi Peminta</dt><dd>{{ $materialRequest->requesting_division ?: '-' }}</dd></div>
                    <div><dt>Dinas Peminta</dt><dd>{{ $materialRequest->requesting_department ?: '-' }}</dd></div>
                    <div><dt>Tanggal Pengajuan</dt><dd>{{ $materialRequest->request_date?->format('d/m/Y') ?? '-' }}</dd></div>
                    <div><dt>Pemohon</dt><dd>{{ $materialRequest->requester?->username ?? $materialRequest->requester?->email ?? 'Pengguna terhapus' }}</dd></div>
                    <div><dt>Tanggal Dikirim</dt><dd>{{ $materialRequest->created_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </section>

            <section class="request-detail-materials" aria-labelledby="request-materials-heading">
                <div class="request-detail-section-heading">
                    <div>
                        <h2 id="request-materials-heading">Data Material</h2>
                        <p>{{ $materialRequest->items->count() }} material pada form ini.</p>
                    </div>
                </div>

                @php
                    $materialFields = [
                        'mtart' => 'Material Type (MTART)',
                        'werks' => 'Plant (WERKS)',
                        'lgort' => 'Storage Location (LGORT)',
                        'maktx' => 'Material Description (MAKTX)',
                        'meins' => 'Base Unit of Measure (MEINS)',
                        'purchasingGroup' => 'Purchasing Group',
                        'matkl' => 'Material Group (MATKL)',
                        'dismm' => 'MRP Type (DISMM)',
                        'mtvfp' => 'Checking Group (MTVFP)',
                        'sernp' => 'Serial Number Profile (SERNP)',
                        'prctr' => 'Profit Center (PRCTR)',
                        'bklas' => 'Valuation Class (BKLAS)',
                        'vprsv' => 'Price Control Indicator (VPRSV)',
                        'peinh' => 'Price Unit (PEINH)',
                        'verpr' => 'Moving Average Price (VERPR)',
                        'bwtty' => 'Valuation Category (BWTTY)',
                        'longText' => 'Long Text Material',
                    ];
                @endphp

                @forelse($materialRequest->items as $index => $item)
                    <article class="request-detail-item">
                        <h3><span>{{ $index + 1 }}</span>{{ $item->material_description }}</h3>
                        <dl class="request-detail-fields">
                            <div>
                                <dt>Feedback / Nomor Kode Material</dt>
                                <dd>{{ $item->sap_material_code ?: 'Belum ada feedback' }}</dd>
                            </div>
                            @foreach($materialFields as $field => $label)
                                <div class="{{ $field === 'longText' ? 'is-long-text' : '' }}">
                                    <dt>{{ $label }}</dt>
                                    <dd>{{ filled($item->details[$field] ?? null) ? $item->details[$field] : '-' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @empty
                    <div class="request-detail-empty">Tidak ada data material pada pengajuan ini.</div>
                @endforelse
            </section>

            @if(Auth::user()->role === 'Admin' && $materialRequest->status !== 'completed')
                <section class="request-detail-card" aria-labelledby="request-sap-heading">
                    <div class="request-detail-section-heading">
                        <div>
                            <h2 id="request-sap-heading">Input Nomor Kode Material SAP</h2>
                            <p>Isi nomor kode untuk setiap material sebelum menyelesaikan pengajuan.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('material-requests.complete', $materialRequest) }}">
                        @csrf
                        @foreach($materialRequest->items as $index => $item)
                            <label>
                                {{ $index + 1 }}. {{ $item->material_description }}
                                <input
                                    type="text"
                                    name="sap_codes[{{ $item->id }}]"
                                    value="{{ old("sap_codes.{$item->id}", $item->sap_material_code) }}"
                                    maxlength="40"
                                    required
                                >
                            </label>
                        @endforeach
                        <button class="btn btn-primary" type="submit">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Selesai
                        </button>
                    </form>
                </section>
            @endif

            <div class="request-detail-actions">
                <a href="{{ Auth::user()->role === 'Admin' ? route('material-requests.index') : route('dashboard') }}" class="btn request-detail-back-button">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    {{ Auth::user()->role === 'Admin' ? 'Kembali ke Inbox' : 'Kembali ke Dashboard' }}
                </a>
                <a href="{{ route('material-requests.download', $materialRequest) }}" class="btn">
                    <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                    Unduh PDF
                </a>
                @if(Auth::user()->role === 'Admin' && !$materialRequest->read_at)
                    <form method="POST" action="{{ route('material-requests.read', $materialRequest) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Tandai Dibaca
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </main>
</div>
@endsection
