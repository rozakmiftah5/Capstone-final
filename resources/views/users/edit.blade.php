@extends('layouts.app')

@section('title', 'Edit Pengguna - Master Material')
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
                <span class="user-management-form-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span>
                <p class="user-management-eyebrow">PENGATURAN AKUN</p>
                <h1>Edit Pengguna</h1>
                <p>Perbarui informasi akun {{ $user->username }}.</p>
            </header>

            @if($errors->any())
                <div class="user-management-alert is-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <section class="user-management-form-card">
                <form action="{{ route('users.update', $user->user_id) }}" method="POST" class="user-management-form">
                    @csrf
                    @method('PUT')

                    <label class="user-management-field" for="username">
                        <span>Username</span>
                        <input id="username" type="text" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" required>
                    </label>

                    <label class="user-management-field" for="email">
                        <span>Email</span>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                    </label>

                    <label class="user-management-field" for="password">
                        <span>Password baru <small>Opsional</small></span>
                        <input id="password" type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password">
                        <small>Password minimal 6 karakter jika ingin diganti.</small>
                    </label>

                    <label class="user-management-field" for="role">
                        <span>Peran</span>
                        <select id="role" name="role" required>
                            <option value="Admin" @selected(old('role', $user->role) === 'Admin')>Admin</option>
                            <option value="User" @selected(old('role', $user->role) === 'User')>User</option>
                            <option value="Warehouse Staff" @selected(old('role', $user->role) === 'Warehouse Staff')>Warehouse Staff</option>
                        </select>
                    </label>

                    <div class="user-management-form-actions">
                        <a href="{{ route('users.index') }}" class="user-management-secondary-button">Batal</a>
                        <button type="submit" class="user-management-primary-button">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
@endsection
