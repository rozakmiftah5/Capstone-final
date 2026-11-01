@extends('layouts.app')

@section('title', 'Tambah Gudang - Master Material')
@section('body-class', 'app-body app-warehouse-page')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')

        <div class="content-body warehouse-content">
            <header class="warehouse-form-heading">
                <span class="warehouse-form-icon" aria-hidden="true">
                    <i class="fa-solid fa-warehouse"></i>
                </span>
                <p class="warehouse-eyebrow">DATA MASTER</p>
                <h1>Tambah Gudang</h1>
                <p>Masukkan informasi lokasi gudang baru.</p>
            </header>

            <section class="warehouse-form-card">
                <form action="{{ route('warehouses.store') }}" method="POST">
                    @csrf
                    <div class="warehouse-form-fields">
                        <label for="warehouse_code">
                            Kode Gudang
                            <input id="warehouse_code" type="text" name="warehouse_code" value="{{ old('warehouse_code') }}" maxlength="255" autocomplete="off" required @error('warehouse_code') aria-invalid="true" aria-describedby="warehouse-code-error" @enderror>
                            @error('warehouse_code')
                                <span id="warehouse-code-error" class="warehouse-field-error">{{ $message }}</span>
                            @enderror
                        </label>
                        <label for="warehouse_name">
                            Nama Gudang
                            <input id="warehouse_name" type="text" name="warehouse_name" value="{{ old('warehouse_name') }}" maxlength="255" required @error('warehouse_name') aria-invalid="true" aria-describedby="warehouse-name-error" @enderror>
                            @error('warehouse_name')
                                <span id="warehouse-name-error" class="warehouse-field-error">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                    <div class="warehouse-form-actions">
                        <a href="{{ route('warehouses.index') }}" class="warehouse-secondary-button">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            Kembali
                        </a>
                        <button type="submit" class="warehouse-primary-button">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            Simpan Gudang
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
@endsection
