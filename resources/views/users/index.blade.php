@extends('layouts.app')

@section('title', 'Manajemen Pengguna - Master Material')
@section('body-class', 'app-body app-user-management-page')

@section('content')
<div class="wrapper">
    <main class="main-content">
        @include('layouts.navigation')

        <div class="content-body user-management-content">
            <header class="user-management-heading">
                <div>
                    <p class="user-management-eyebrow">ADMINISTRASI</p>
                    <h1>Manajemen Pengguna</h1>
                    <p class="user-management-description">Kelola akun dan hak akses pengguna aplikasi.</p>
                </div>
                @if(Auth::user()->role === 'Admin')
                    <a href="{{ route('users.create') }}" class="user-management-primary-button">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        <span>Tambah Pengguna</span>
                    </a>
                @endif
            </header>

            @if(session('success'))
                <div class="user-management-alert is-success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="user-management-alert is-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <section class="user-management-card" aria-labelledby="user-list-heading">
                <div class="user-management-card-heading">
                    <div>
                        <h2 id="user-list-heading">Daftar Pengguna</h2>
                        <p>Akun yang terdaftar pada aplikasi Master Material.</p>
                    </div>
                    <span class="user-management-count">
                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                        {{ $users->count() }} pengguna
                    </span>
                </div>

                <div class="user-management-table-wrap">
                    <table class="user-management-table">
                        <thead>
                            <tr>
                                <th scope="col">Pengguna</th>
                                <th scope="col">Email</th>
                                <th scope="col">Peran</th>
                                <th scope="col" class="user-management-actions-heading">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="user-management-identity">
                                            <span class="user-management-avatar" aria-hidden="true">
                                                {{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}
                                            </span>
                                            <span class="user-management-name">{{ $user->username }}</span>
                                        </div>
                                    </td>
                                    <td class="user-management-email">{{ $user->email }}</td>
                                    <td>
                                        <span class="user-management-role {{ $user->role === 'Admin' ? 'is-admin' : 'is-staff' }}">
                                            <i class="fa-solid {{ $user->role === 'Admin' ? 'fa-shield-halved' : 'fa-user' }}" aria-hidden="true"></i>
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(Auth::user()->role === 'Admin')
                                            <div class="user-management-actions">
                                                <a href="{{ route('users.edit', $user->user_id) }}" class="user-management-action is-edit">
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    <span>Edit</span>
                                                </a>
                                                <form action="{{ route('users.destroy', $user->user_id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="user-management-action is-delete">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                        <span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="user-management-no-action">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="user-management-empty">
                                            <span><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                                            <strong>Belum ada pengguna</strong>
                                            <p>Pengguna yang terdaftar akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <a href="{{ route('dashboard') }}" class="user-management-back-link">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali ke Dashboard
            </a>
        </div>
    </main>
</div>
@endsection
