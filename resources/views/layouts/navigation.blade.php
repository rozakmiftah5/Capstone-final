<header class="top-navbar app-top-navbar">
    <button type="button" class="app-sidebar-mobile-toggle" data-sidebar-mobile-toggle aria-controls="app-navigation-sidebar" aria-expanded="false" aria-label="Buka navigasi">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    @unless(request()->routeIs('dashboard'))
        @php
            $currentPageTitle = preg_replace('/\s*-\s*Master Material$/', '', trim($__env->yieldContent('title')));
        @endphp
        <h1 class="app-current-page-title">{{ $currentPageTitle }}</h1>
    @endunless

    @if(request()->routeIs('dashboard'))
        <form method="GET" action="{{ route('dashboard') }}" class="app-quick-price-search" data-price-search aria-label="Cek Harga Terakhir">
            <label for="dashboard-material-code">Quick Check Harga</label>
            <div class="app-quick-price-search-field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input id="dashboard-material-code" name="code" value="{{ $code }}" placeholder="Nomor atau nama material..." maxlength="120" required>
                <button type="submit" title="Cari harga" aria-label="Cari harga">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    @endif

    <div class="app-user-menu">
        <div class="app-user-label">
            <strong>{{ Auth::user()->username ?? Auth::user()->email }}</strong>
            <span>{{ Auth::user()->role ?? 'Pengguna' }}</span>
        </div>
        @if(Auth::user()->role === 'Admin')
            <details class="app-header-notifications">
                <summary class="app-header-inbox" title="Notifikasi pengajuan belum dibaca" aria-label="Notifikasi pengajuan, {{ $unreadRequestCount }} belum dibaca">
                    <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    @if($unreadRequestCount > 0)
                        <span class="app-header-inbox-badge">{{ $unreadRequestCount }}</span>
                    @endif
                </summary>
                <div class="app-header-notifications-panel">
                    <div class="app-header-notifications-heading">
                        <div>
                            <strong>Pengajuan belum dibaca</strong>
                            <span>{{ $unreadRequestCount }} notifikasi</span>
                        </div>
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    </div>
                    @forelse($unreadRequests as $unreadRequest)
                        <a href="{{ route('material-requests.index') }}" class="app-header-notification-item">
                            <span class="app-header-notification-icon"><i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i></span>
                            <span class="app-header-notification-copy">
                                <strong>{{ $unreadRequest->request_number }}</strong>
                                <span>{{ $unreadRequest->requester?->username ?? $unreadRequest->requester?->email ?? 'Pengguna terhapus' }} · {{ $unreadRequest->requesting_division ?: $unreadRequest->requesting_department ?: 'Pengajuan material' }}</span>
                                <small>{{ $unreadRequest->items->pluck('material_description')->take(2)->join(', ') ?: 'Belum ada rincian material' }}@if($unreadRequest->items->count() > 2) +{{ $unreadRequest->items->count() - 2 }} lainnya @endif</small>
                            </span>
                            <time datetime="{{ $unreadRequest->created_at->toIso8601String() }}">{{ $unreadRequest->created_at->format('d/m/Y') }}</time>
                        </a>
                    @empty
                        <p class="app-header-notifications-empty">Tidak ada pengajuan baru yang belum dibaca.</p>
                    @endforelse
                    <a href="{{ route('material-requests.index') }}" class="app-header-notifications-link">Buka Inbox Pengajuan Penambahan Kode Material <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </details>
        @endif
        @if($requestCompletionNotificationCount > 0)
            <details class="app-header-notifications">
                <summary class="app-header-inbox" title="Notifikasi pengajuan selesai" aria-label="Notifikasi pengajuan selesai, {{ $requestCompletionNotificationCount }} belum dibaca">
                    <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    <span class="app-header-inbox-badge">{{ $requestCompletionNotificationCount }}</span>
                </summary>
                <div class="app-header-notifications-panel">
                    <div class="app-header-notifications-heading">
                        <div>
                            <strong>Pengajuan selesai diproses</strong>
                            <span>{{ $requestCompletionNotificationCount }} notifikasi baru</span>
                        </div>
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    </div>
                    @foreach($requestCompletionNotifications as $completionNotification)
                        <a href="{{ route('material-requests.show', $completionNotification->data['material_request_id']) }}" class="app-header-notification-item">
                            <span class="app-header-notification-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                            <span class="app-header-notification-copy">
                                <strong>{{ $completionNotification->data['request_number'] }}</strong>
                                <span>{{ $completionNotification->data['message'] }}</span>
                                <small>Kode material: {{ collect($completionNotification->data['material_codes'] ?? [])->join(', ') }}</small>
                            </span>
                            <time datetime="{{ $completionNotification->created_at->toIso8601String() }}">{{ $completionNotification->created_at->format('d/m/Y') }}</time>
                        </a>
                    @endforeach
                </div>
            </details>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="navbar-logout-btn" title="Keluar" aria-label="Keluar">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</header>
