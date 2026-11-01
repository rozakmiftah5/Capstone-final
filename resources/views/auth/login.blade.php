@extends('layouts.app')

@section('title', 'Login - Master Material | PT. Krakatau Tirta Industri')
@section('body-class', 'app-login-page fade-in')

@section('content')
<div class="login-wrapper">
        <div class="login-box">
            <div class="login-right">
                <div style="text-align: center; margin-bottom: 22px;">
                    <img src="{{ asset('images/master-material/logo.png') }}" alt="Logo PT. Krakatau Tirta Industri" class="app-login-logo">
                    <h1 style="margin: 0; font-weight: 800; letter-spacing: -1px;">Master Material</h1>
                    <p style="margin: 6px 0 0; color: var(--primary-blue); font-size: 0.8rem; font-weight: 700; letter-spacing: 1px;">PT. KRAKATAU TIRTA INDUSTRI</p>
                </div>

                @if(session('success'))
                    <div class="app-login-error" style="border-color: #bbf7d0; background: #f0fdf4; color: #166534;" role="status">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="app-login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('login.process') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> MASUK KE SISTEM
                    </button>
                </form>

                <div class="register-link" style="text-align: center; margin-top: 22px;">
                    <span style="font-size: 0.75rem; color: #718096;">&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span>
                </div>
            </div>
        </div>
    </div>
@endsection
