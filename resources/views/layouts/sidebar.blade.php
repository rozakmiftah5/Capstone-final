<div class="app-sidebar-shell" data-sidebar-shell>
    <aside id="app-navigation-sidebar" class="app-dashboard-sidebar" aria-label="Navigasi utama">
        <div class="app-dashboard-sidebar-brand">
            <a href="{{ route('dashboard') }}" class="app-dashboard-sidebar-brand-link" aria-label="Master Material - Dashboard">
                <img src="{{ asset('images/master-material/logo.png') }}" alt="">
                <span>
                    <strong>Master Material</strong>
                    <small>Inventory Control</small>
                </span>
            </a>
            <button class="app-dashboard-sidebar-collapse" type="button" data-sidebar-collapse aria-expanded="true" aria-label="Ciutkan navigasi" title="Ciutkan navigasi">
                <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
            </button>
        </div>

        <div class="app-dashboard-sidebar-heading">
            <span class="app-dashboard-sidebar-eyebrow">MENU UTAMA</span>
            <h2>Navigasi</h2>
        </div>
        <nav id="dashboard-sidebar-nav" class="app-dashboard-sidebar-nav" aria-label="Menu aplikasi">
            <span class="app-dashboard-nav-label">Beranda</span>
            <a href="{{ route('dashboard') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif title="Dashboard">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>

            <span class="app-dashboard-nav-label">Inventaris</span>
            <a href="{{ route('master-materials.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('master-materials.*')]) @if(request()->routeIs('master-materials.*')) aria-current="page" @endif title="Daftar Kode Material">
                <i class="fa-solid fa-barcode" aria-hidden="true"></i>
                <span>Daftar Kode Material</span>
            </a>
            <a href="{{ route('stock-os.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('stock-os.*')]) @if(request()->routeIs('stock-os.*')) aria-current="page" @endif title="Stock Bahan Kimia (OS)">
                <i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i>
                <span>Stock Bahan Kimia (OS)</span>
            </a>
            <a href="{{ route('warehouses.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('warehouses.*')]) @if(request()->routeIs('warehouses.*')) aria-current="page" @endif title="Manajemen Gudang">
                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                <span>Manajemen Gudang</span>
            </a>
            @can('material-receipts')
                <a href="{{ route('material-receipts.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('material-receipts.*')]) @if(request()->routeIs('material-receipts.*')) aria-current="page" @endif title="Penerimaan Barang">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <span>Penerimaan Barang</span>
                </a>
            @endcan

            <span class="app-dashboard-nav-label">Menu User</span>
            <a href="{{ route('po-history.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('po-history.*')]) @if(request()->routeIs('po-history.*')) aria-current="page" @endif title="Riwayat Purchase Order">
                <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                <span>Riwayat Purchase Order</span>
            </a>
            <a href="{{ route('material-requests.create') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('material-requests.create', 'material-requests.store')]) @if(request()->routeIs('material-requests.create', 'material-requests.store')) aria-current="page" @endif title="Pengajuan Penambahan Kode Material">
                <i class="fa-solid fa-file-circle-plus" aria-hidden="true"></i>
                <span>Pengajuan Penambahan Kode Material</span>
            </a>

            @if(Auth::user()->role === 'Admin')
                <span class="app-dashboard-nav-label">Administrasi</span>
                <a href="{{ route('material-requests.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('material-requests.index', 'material-requests.show')]) @if(request()->routeIs('material-requests.index', 'material-requests.show')) aria-current="page" @endif title="Inbox Pengajuan Penambahan Kode Material">
                    <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                    <span>Inbox Pengajuan Penambahan Kode Material</span>
                </a>
                <a href="{{ route('users.index') }}" @class(['app-dashboard-nav-link', 'is-active' => request()->routeIs('users.*')]) @if(request()->routeIs('users.*')) aria-current="page" @endif title="Manajemen Pengguna">
                    <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                    <span>Manajemen Pengguna</span>
                </a>
            @endif
        </nav>

        <div class="app-dashboard-sidebar-footer">
            <span class="app-dashboard-sidebar-status"></span>
            <span><strong>Sistem aktif</strong><small>Master Material</small></span>
        </div>
    </aside>
    <button class="app-dashboard-sidebar-overlay" type="button" data-sidebar-overlay aria-label="Tutup navigasi"></button>
</div>

@push('scripts')
    <script>
        (() => {
            const sidebarShell = document.querySelector('[data-sidebar-shell]');
            const sidebar = document.querySelector('.app-dashboard-sidebar');
            const collapseToggle = document.querySelector('[data-sidebar-collapse]');
            const mobileToggle = document.querySelector('[data-sidebar-mobile-toggle]');
            const overlay = document.querySelector('[data-sidebar-overlay]');
            const mobileViewport = window.matchMedia('(max-width: 900px)');

            const closeMobileSidebar = () => {
                sidebarShell.classList.remove('is-mobile-open');
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.setAttribute('aria-label', 'Buka navigasi');
                document.body.classList.remove('app-sidebar-open');
            };

            mobileToggle.addEventListener('click', () => {
                const isOpen = sidebarShell.classList.toggle('is-mobile-open');
                mobileToggle.setAttribute('aria-expanded', String(isOpen));
                mobileToggle.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');
                document.body.classList.toggle('app-sidebar-open', isOpen);
            });

            overlay.addEventListener('click', closeMobileSidebar);
            sidebar.querySelectorAll('.app-dashboard-nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (mobileViewport.matches) {
                        closeMobileSidebar();
                    }
                });
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && sidebarShell.classList.contains('is-mobile-open')) {
                    closeMobileSidebar();
                    mobileToggle.focus();
                }
            });

            collapseToggle.addEventListener('click', () => {
                const isCollapsed = document.body.classList.toggle('app-sidebar-collapsed');
                collapseToggle.setAttribute('aria-expanded', String(!isCollapsed));
                collapseToggle.setAttribute('aria-label', isCollapsed ? 'Perluas navigasi' : 'Ciutkan navigasi');
                collapseToggle.setAttribute('title', isCollapsed ? 'Perluas navigasi' : 'Ciutkan navigasi');
            });

            mobileViewport.addEventListener('change', event => {
                closeMobileSidebar();

                if (event.matches) {
                    document.body.classList.remove('app-sidebar-collapsed');
                    collapseToggle.setAttribute('aria-expanded', 'true');
                    collapseToggle.setAttribute('aria-label', 'Ciutkan navigasi');
                    collapseToggle.setAttribute('title', 'Ciutkan navigasi');
                }
            });
        })();
    </script>
@endpush
