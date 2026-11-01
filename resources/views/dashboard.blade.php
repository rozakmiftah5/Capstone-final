@extends('layouts.app')

@section('title', 'Dashboard - Master Material')
@section('body-class', 'app-body app-dashboard-page')

@section('content')
<div class="wrapper">
    <main class="main-content">
            @include('layouts.navigation')

            @if(session('success'))
                <div class="app-flash" role="status">{{ session('success') }}</div>
            @endif

            <div class="content-body">
                <output class="app-price-search-status" data-price-search-status role="status" aria-live="polite"></output>
                <div id="price-search-results" data-price-search-results aria-live="polite">
                    @include('dashboard.partials.price-search-results')
                </div>

                <div class="app-dashboard">
                    <section class="app-dashboard-hero" aria-labelledby="dashboard-title">
                        <img
                            class="app-dashboard-hero-image"
                            src="{{ asset('images/master-material/accelator.jpg') }}"
                            alt="Pemandangan fasilitas pengolahan air dan lingkungan sekitarnya"
                            fetchpriority="high"
                        >
                        <div class="app-dashboard-hero-content">
                            <h1 id="dashboard-title">Selamat Datang</h1>
                            <p>Kelola data material, persediaan, dan riwayat pembelian PT. Krakatau Tirta Industri dalam satu platform.</p>
                        </div>
                        <div class="app-dashboard-clock" aria-label="Tanggal dan waktu saat ini">
                            <span class="app-dashboard-clock-date" data-current-date>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</span>
                            <time class="app-dashboard-clock-time" data-current-time datetime="{{ now('Asia/Jakarta')->toIso8601String() }}">{{ now('Asia/Jakarta')->format('H:i:s') }}</time>
                            <span class="app-dashboard-clock-zone">Waktu Indonesia Barat (WIB)</span>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="app-page-footer">
                <span>&copy; {{ now()->year }} PT. Krakatau Tirta Industri</span>
                <span>Inventory Control System</span>
            </footer>
    </main>
</div>
    <script>
        (() => {
            const dateElement = document.querySelector('[data-current-date]');
            const timeElement = document.querySelector('[data-current-time]');
            const timeZone = 'Asia/Jakarta';
            const dateFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone,
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
            const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hourCycle: 'h23',
            });

            const updateClock = () => {
                const current = new Date();
                dateElement.textContent = dateFormatter.format(current);
                timeElement.textContent = timeFormatter.format(current);
                timeElement.dateTime = current.toISOString();
            };

            updateClock();
            window.setInterval(updateClock, 1000);

            const form = document.querySelector('[data-price-search]');
            const results = document.querySelector('[data-price-search-results]');
            const status = document.querySelector('[data-price-search-status]');
            const input = form.querySelector('[name="code"]');
            const submit = form.querySelector('[type="submit"]');

            const loadResults = async (url, updateHistory = true) => {
                submit.disabled = true;
                status.textContent = 'Mencari data harga...';

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const validationMessage = Object.values(payload.errors || {}).flat()[0];
                        throw new Error(validationMessage || payload.message || 'Pencarian gagal. Silakan coba lagi.');
                    }

                    results.innerHTML = payload.html;
                    status.textContent = '';
                    if (updateHistory) {
                        window.history.pushState({}, '', url);
                    }
                    return true;
                } catch (error) {
                    status.textContent = error.message || 'Pencarian gagal. Silakan coba lagi.';
                    return false;
                } finally {
                    submit.disabled = false;
                }
            };

            form.addEventListener('submit', event => {
                event.preventDefault();
                const url = new URL(form.action, window.location.origin);
                url.searchParams.set('code', input.value.trim());
                url.searchParams.delete('page');
                loadResults(url);
            });

            results.addEventListener('click', event => {
                const dismissButton = event.target.closest('[data-dismiss-price-results]');
                if (dismissButton) {
                    results.replaceChildren();
                    status.textContent = '';
                    input.value = '';
                    const url = new URL(window.location.href);
                    url.searchParams.delete('code');
                    url.searchParams.delete('page');
                    window.history.replaceState({}, '', url);
                    input.focus();
                    return;
                }

                const detailButton = event.target.closest('[data-toggle-po-details]');
                if (detailButton) {
                    const detailRow = document.getElementById(detailButton.getAttribute('aria-controls'));
                    if (!detailRow) {
                        return;
                    }

                    const isExpanded = detailButton.getAttribute('aria-expanded') === 'true';
                    detailRow.hidden = isExpanded;
                    detailButton.setAttribute('aria-expanded', String(!isExpanded));
                    detailButton.setAttribute(
                        'aria-label',
                        `${isExpanded ? 'Tampilkan' : 'Sembunyikan'} detail PO ${detailButton.textContent.trim()}`,
                    );
                    return;
                }

                const link = event.target.closest('a[href]');
                if (!link || new URL(link.href).pathname !== window.location.pathname) {
                    return;
                }

                event.preventDefault();
                loadResults(new URL(link.href));
            });

            window.addEventListener('popstate', () => {
                const url = new URL(window.location.href);
                input.value = url.searchParams.get('code') || '';
                loadResults(url, false);
            });
        })();
    </script>
@endsection
