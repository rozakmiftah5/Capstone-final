@extends('layouts.app')

@section('title', 'Pengajuan Penambahan Kode Material - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper"><main class="main-content" id="request-page" data-request-sent="{{ session('success') ? '1' : '0' }}" data-template-url="{{ asset('templates/Form LPPM.xlsx') }}">
    @include('layouts.navigation')
    <div class="content-body request-content">
        @if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="app-flash" role="alert" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;">{{ $errors->first() }}</div>@endif
        <div class="request-page-heading">
            <div><a href="{{ route('dashboard') }}" class="request-back-link"><i class="fa-solid fa-arrow-left"></i> Dashboard</a><h1>Pengajuan Penambahan Kode Material</h1><p>Isi data pengajuan dan material sesuai Form LPPM.</p></div>
            <a class="btn btn-warning" href="{{ asset('templates/Form LPPM.xlsx') }}" download><i class="fa-solid fa-file-arrow-down"></i> Unduh Template Kosong</a>
        </div>

        <form id="material-request-form" action="{{ route('material-requests.store') }}" method="POST">
            @csrf
            <section class="request-section" aria-labelledby="request-info-heading">
                <div class="request-section-heading"><h2 id="request-info-heading">Informasi Pengajuan</h2></div>
                <div class="request-meta-grid">
                    <label class="request-field"><span>Nomor Surat</span><input class="form-control" type="text" name="letter_number" maxlength="50" value="{{ old('letter_number') }}"></label>
                    <label class="request-field"><span>Divisi Peminta</span><input class="form-control" type="text" name="requesting_division" maxlength="100" value="{{ old('requesting_division') }}"></label>
                    <label class="request-field"><span>Dinas Peminta</span><input class="form-control" type="text" name="requesting_department" maxlength="100" value="{{ old('requesting_department') }}"></label>
                    <label class="request-field"><span>Tanggal</span><input class="form-control" type="date" name="request_date" value="{{ old('request_date', today()->toDateString()) }}"></label>
                </div>
            </section>

            <section class="request-section" aria-labelledby="material-data-heading">
                <div class="request-section-heading request-items-heading">
                    <div><h2 id="material-data-heading">Data Material</h2><p>Field bertanda * wajib diisi. Maksimal 10 material dalam satu Form LPPM.</p></div>
                    <button type="button" id="add-material-row" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Material</button>
                </div>
                <div id="material-request-items" class="request-items"></div>
            </section>
            <div class="request-actions">
                <p id="request-status" role="status" aria-live="polite">Pengajuan akan tersimpan pada aplikasi saat dikirim.</p>
                <div class="request-action-buttons">
                    <button type="button" id="save-request-draft" class="btn request-secondary-button"><i class="fa-solid fa-floppy-disk"></i> Simpan Draf</button>
                    <button type="submit" id="submit-material-request" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan</button>
                    <button type="button" id="download-request" class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Unduh Form Terisi</button>
                </div>
            </div>
        </form>
    </div>
    <footer class="request-footer">
        <div class="request-footer-brand"><img src="{{ asset('images/master-material/logo.png') }}" alt="Logo KTI"><span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span></div>
        <span>Inventory Control System</span>
    </footer>
</main></div>

<template id="material-line-template">
    <section class="request-item" aria-label="Data material">
        <div class="request-item-heading"><h3 data-item-title>Material 1</h3><button type="button" class="request-remove-button" data-remove-item title="Hapus material" aria-label="Hapus material"><i class="fa-solid fa-trash"></i></button></div>
        <div class="request-material-grid">
            <label class="request-field"><span>Material type* <small>MTART</small></span><select class="form-control" data-field="mtart" required><option value="">Pilih material type</option></select></label>
            <label class="request-field"><span>Plant <small>WERKS</small></span><input class="form-control" data-field="werks" maxlength="4" value="KTI1"></label>
            <label class="request-field"><span>Storage location* <small>LGORT</small></span><input class="form-control" data-field="lgort" maxlength="4" required></label>
            <label class="request-field"><span>Material description* <small>MAKTX</small></span><input class="form-control" data-field="maktx" maxlength="40" required></label>
            <label class="request-field"><span>Base Unit of Measure* <small>MEINS</small></span><select class="form-control" data-field="meins" required><option value="">Pilih satuan</option></select></label>
            <label class="request-field"><span>Purchasing Group*</span><select class="form-control" data-field="purchasingGroup" required><option value="">Pilih purchasing group</option></select></label>
            <label class="request-field"><span>Material Group* <small>MATKL</small></span><select class="form-control" data-field="matkl" required><option value="">Pilih material group</option></select></label>
            <label class="request-field"><span>MRP Type <small>DISMM</small></span><input class="form-control" data-field="dismm" maxlength="2" value="ND"></label>
            <label class="request-field"><span>Checking Group <small>MTVFP</small></span><input class="form-control" data-field="mtvfp" maxlength="2" value="02"></label>
            <label class="request-field"><span>Serial Number Profile <small>SERNP</small></span><input class="form-control" data-field="sernp" maxlength="4"></label>
            <label class="request-field"><span>Profit Center <small>PRCTR</small></span><input class="form-control" data-field="prctr" maxlength="10" value="220000"></label>
            <label class="request-field"><span>Valuation Class* <small>BKLAS</small></span><select class="form-control" data-field="bklas" required><option value="">Pilih valuation class</option></select></label>
            <label class="request-field"><span>Price control indicator <small>VPRSV</small></span><input class="form-control" data-field="vprsv" maxlength="1" value="V"></label>
            <label class="request-field"><span>Price unit <small>PEINH</small></span><input class="form-control" data-field="peinh" type="number" min="1" step="1" value="1"></label>
            <label class="request-field"><span>Moving Average Price* <small>VERPR</small></span><input class="form-control" data-field="verpr" type="number" min="0" step="any" required></label>
            <label class="request-field"><span>Valuation Category <small>BWTTY</small></span><input class="form-control" data-field="bwtty" maxlength="1"></label>
            <label class="request-field request-long-text"><span>Long Text Material* <small>LONGTEXT</small></span><textarea class="form-control" data-field="longText" maxlength="132" rows="2" required></textarea></label>
            <div class="request-field request-sap-number"><span>Material Number <small>SAP</small></span><output>Diisi setelah proses SAP</output></div>
        </div>
    </section>
</template>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="{{ asset('js/material-request-form.js') }}" defer></script>
@endsection
