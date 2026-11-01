<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('images/master-material/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('build/assets/fonts.css') }}">
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('head')
</head>
<body class="@yield('body-class', 'app-body') {{ Auth::check() ? 'app-sidebar-page' : '' }}">
    @auth
        @include('layouts.sidebar')
    @endauth

    @yield('content')

    @stack('scripts')
</body>
</html>
