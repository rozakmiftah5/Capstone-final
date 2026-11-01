@extends('layouts.app')

@section('title', 'Tambah Pengguna - Master Material')
@section('body-class', 'app-body app-user-management-page')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')

        <div class="content-body user-management-content">
            <a href="{{ route('users.index') }}" class="user-management-back-link">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali ke Daftar Pengguna
            </a>

            <header class="user-management-form-heading">
                <span class="user-management-form-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
                <p class="user-management-eyebrow">AKUN BARU</p>
                <h1>Tambah Pengguna</h1>
                <p>Isi informasi berikut untuk membuat akun baru.</p>
            </header>

            @if($errors->any())
                <div class="user-management-alert is-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <section class="user-management-form-card">
                <form action="{{ route('users.store') }}" method="POST" class="user-management-form">
                    @csrf
                    <label class="user-management-field" for="username">
                        <span>Username</span>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" required>
                    </label>

                    <label class="user-management-field" for="email">
                        <span>Email</span>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@perusahaan.com" autocomplete="email" required>
                    </label>

                    <label class="user-management-field" for="password">
                        <span>Password</span>
                        <input id="password" type="password" name="password" placeholder="Buat password minimal 6 karakter" autocomplete="new-password" required>
                    </label>

                    <label class="user-management-field" for="role">
                        <span>Peran</span>
                        <select id="role" name="role" required>
                            <option value="">Pilih peran pengguna</option>
                            <option value="Admin" @selected(old('role') === 'Admin')>Admin</option>
                            <option value="User" @selected(old('role') === 'User')>User</option>
                            <option value="Warehouse Staff" @selected(old('role') === 'Warehouse Staff')>Warehouse Staff</option>
                        </select>
                    </label>

                    <div class="user-management-form-actions">
                        <a href="{{ route('users.index') }}" class="user-management-secondary-button">Batal</a>
                        <button type="submit" class="user-management-primary-button">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            Buat Pengguna
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
@endsection
