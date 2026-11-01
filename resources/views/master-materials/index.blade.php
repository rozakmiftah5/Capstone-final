@extends('layouts.app')

@section('title', 'Daftar Kode Material - Master Material')
@section('body-class', 'app-body')

@section('content')
<div class="wrapper">
        <main class="main-content">
            @include('layouts.navigation')
            @if(session('success'))<div class="app-flash" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="app-flash" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;" role="alert">{{ $errors->first() }}</div>@endif

            <div class="content-body">
                <section class="card">
                    <div class="page-header-action">
                        <div>
                            <h1 style="margin:0;">Daftar Kode Material</h1>
                            <p style="margin-top:6px;color:#64748b;">{{ number_format($materials->total(), 0, ',', '.') }} material ditemukan.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('master-materials.index') }}" class="app-inline-actions" style="margin-bottom:18px;">
                        <input class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Cari kode, deskripsi, group, atau type..." aria-label="Cari material">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        @if($search !== '')<a href="{{ route('master-materials.index') }}" class="btn">Reset</a>@endif
                    </form>

                    <div class="table-container">
                        <table class="table-modern">
                            <thead><tr><th>Kode Material</th><th>Deskripsi Material</th><th>Material Group</th><th>Material Type</th><th>Valuation Class</th><th>UOM</th></tr></thead>
                            <tbody>
                                @forelse($materials as $material)
                                    <tr>
                                        <td style="font-weight:700;color:var(--primary-blue);font-family:monospace;">
                                            {{ $material->material_code }}
                                            <button type="button" class="btn btn-sm copy-code-button" data-copy-code="{{ $material->material_code }}" title="Salin kode material" aria-label="Salin kode material {{ $material->material_code }}"><i class="fa-solid fa-copy" aria-hidden="true"></i></button>
                                        </td>
                                        <td>{{ $material->short_text }}</td>
                                        <td>{{ $material->material_group ?: '-' }}</td>
                                        <td>{{ $material->material_type ?: '-' }}</td>
                                        <td>{{ $material->valuation_class ?: '-' }}</td>
                                        <td>{{ $material->uom ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="text-align:center;padding:36px;color:#64748b;">Katalog belum memiliki data. Admin dapat mengimpor daftar material dari Excel.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="app-pagination">{{ $materials->links() }}</div>
                </section>
            </div>
            <footer class="app-page-footer"><span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span><span>Inventory Control System</span></footer>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-copy-code]').forEach(button => {
            const icon = button.querySelector('i');
            const originalTitle = button.title;
            const originalAriaLabel = button.getAttribute('aria-label');
            let feedbackTimeout;

            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copyCode);
                    icon.classList.replace('fa-copy', 'fa-check');
                    button.classList.add('is-copied');
                    button.title = 'Kode berhasil disalin';
                    button.setAttribute('aria-label', `Kode material ${button.dataset.copyCode} berhasil disalin`);
                    window.clearTimeout(feedbackTimeout);
                    feedbackTimeout = window.setTimeout(() => {
                        icon.classList.replace('fa-check', 'fa-copy');
                        button.classList.remove('is-copied');
                        button.title = originalTitle;
                        button.setAttribute('aria-label', originalAriaLabel);
                    }, 1600);
                } catch {
                    window.prompt('Salin kode material:', button.dataset.copyCode);
                }
            });
        });
    </script>
@endsection
