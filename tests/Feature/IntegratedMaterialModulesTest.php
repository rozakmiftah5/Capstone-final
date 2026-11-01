<?php

namespace Tests\Feature;

use App\Models\MasterMaterial;
use App\Models\MaterialRequest;
use App\Models\PoHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IntegratedMaterialModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_integrated_pages_to_login(): void
    {
        foreach ([
            'dashboard',
            'materials.index',
            'materials.create',
            'material-receipts.index',
            'master-materials.index',
            'stock-os.index',
            'po-history.index',
            'price-check',
        ] as $routeName) {
            $this->get(route($routeName))->assertRedirectToRoute('login');
        }

        $this->get('/tracking')->assertNotFound();
        $this->get('/price-comparisons')->assertNotFound();
    }

    public function test_login_page_does_not_render_sidebar_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('id="app-navigation-sidebar"', false)
            ->assertDontSee('app-sidebar-page');
    }

    public function test_login_succeeds_when_notifications_table_is_unavailable(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('notifications');

        $this->post(route('login.process'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirectToRoute('dashboard');
    }

    public function test_opening_the_root_url_ends_an_existing_session_and_shows_login(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirectToRoute('login');

        $this->get(route('dashboard'))
            ->assertRedirectToRoute('login');
    }

    public function test_admin_can_render_the_integrated_feature_pages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        foreach ([
            'dashboard',
            'master-materials.index',
            'stock-os.index',
            'po-history.index',
            'price-check',
            'material-requests.create',
            'material-requests.index',
            'materials.create',
            'material-receipts.index',
            'users.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('class="main-content"', false)
            ->assertSee('id="app-navigation-sidebar"', false);

        $this->get(route('master-materials.index'))
            ->assertDontSee('Impor Excel')
            ->assertDontSee('data-excel-import', false);
        $this->get(route('po-history.index'))
            ->assertDontSee('Sinkronisasi Excel')
            ->assertDontSee('data-excel-import', false);
    }

    public function test_material_codes_render_copy_controls(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        MasterMaterial::create([
            'material_code' => 'MAT-COPY-001',
            'short_text' => 'Material untuk uji salin',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('master-materials.index'));

        $response->assertOk()
            ->assertSee('data-copy-code="MAT-COPY-001"', false)
            ->assertSee('class="fa-solid fa-copy"', false);
    }

    public function test_removed_features_are_not_available_from_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('<nav class="nav-menu"', false)
            ->assertDontSee('Ringkasan inventaris')
            ->assertDontSee('Total Material')
            ->assertDontSee('Total Stok')
            ->assertSee('Selamat Datang')
            ->assertSee('Kelola data material, persediaan, dan riwayat pembelian PT. Krakatau Tirta Industri dalam satu platform.')
            ->assertSee('data-current-date', false)
            ->assertSee('data-current-time', false)
            ->assertSee('Waktu Indonesia Barat (WIB)')
            ->assertDontSee('INVENTORY CONTROL SYSTEM')
            ->assertDontSee('id="dashboard-title">Dashboard', false)
            ->assertDontSee('Ruang kerja')
            ->assertDontSee('class="app-topbar-page-title"', false)
            ->assertDontSee('class="app-current-page-title"', false)
            ->assertSee('class="app-header-notifications"', false)
            ->assertSee('MENU UTAMA')
            ->assertSee('class="app-dashboard-sidebar"', false)
            ->assertSee('aria-label="Menu aplikasi"', false)
            ->assertDontSee('Data Material')
            ->assertSee(route('material-requests.index'), false)
            ->assertSee('Inbox Pengajuan')
            ->assertSee('Manajemen Pengguna')
            ->assertSee(asset('images/master-material/accelator.jpg'), false)
            ->assertSee(route('master-materials.index'), false)
            ->assertSee(route('material-receipts.index'), false)
            ->assertSee('Penerimaan Barang')
            ->assertSee(route('stock-os.index'), false)
            ->assertSee('Cek Harga Terakhir')
            ->assertDontSee('Pengajuan Material Terbaru')
            ->assertDontSee(route('price-check'), false)
            ->assertDontSee('Perbandingan Harga')
            ->assertDontSee('Tracking Pengiriman');
    }

    public function test_user_dashboard_sidebar_shows_only_permitted_navigation_links(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Data Material')
            ->assertSee(route('material-requests.create'), false)
            ->assertDontSee('href="'.route('material-receipts.index').'"', false)
            ->assertDontSee('href="'.route('material-requests.index').'"', false)
            ->assertDontSee('href="'.route('users.index').'"', false)
            ->assertDontSee('Inbox Pengajuan')
            ->assertDontSee('Manajemen Pengguna');
    }

    public function test_warehouse_staff_sidebar_adds_stock_and_material_receipt_access_without_admin_navigation(): void
    {
        $warehouseStaff = User::factory()->create(['role' => 'Warehouse Staff']);

        $this->actingAs($warehouseStaff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('stock-os.index'), false)
            ->assertSee(route('material-receipts.index'), false)
            ->assertSee('Penerimaan Barang')
            ->assertDontSee('href="'.route('material-requests.index').'"', false)
            ->assertDontSee('href="'.route('users.index').'"', false)
            ->assertDontSee('Inbox Pengajuan Penambahan Kode Material')
            ->assertDontSee('Manajemen Pengguna');
    }

    public function test_admin_inbox_is_in_the_header_and_shows_unread_count_on_every_page(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        MaterialRequest::create([
            'request_number' => 'REQ-UNREAD-001',
            'user_id' => $admin->user_id,
            'requesting_division' => 'Operasi',
        ]);
        $unreadRequest = MaterialRequest::query()->where('request_number', 'REQ-UNREAD-001')->firstOrFail();
        $unreadRequest->items()->create([
            'material_description' => 'Seal pompa transfer',
            'details' => ['maktx' => 'Seal pompa transfer'],
        ]);
        MaterialRequest::create([
            'request_number' => 'REQ-UNREAD-002',
            'user_id' => $admin->user_id,
        ]);
        MaterialRequest::create([
            'request_number' => 'REQ-READ-001',
            'user_id' => $admin->user_id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertSee('2', false)
            ->assertSee('class="app-current-page-title">Stock Bahan Kimia (OS)</h1>', false)
            ->assertDontSee('class="brand-link"', false)
            ->assertSee('class="app-header-notifications"', false)
            ->assertSee('class="app-header-inbox-badge"', false)
            ->assertSee('Pengajuan belum dibaca')
            ->assertSee('REQ-UNREAD-001')
            ->assertSee('Seal pompa transfer')
            ->assertDontSee('REQ-READ-001');

        $content = $response->getContent();
        $accountPosition = strpos($content, 'class="app-user-label"');
        $notificationPosition = strpos($content, 'class="app-header-notifications"');
        $administrationPosition = strpos($content, 'Administrasi');
        $sidebarInboxPosition = strpos($content, 'Inbox Pengajuan Penambahan Kode Material');

        $this->assertNotFalse($accountPosition);
        $this->assertNotFalse($notificationPosition);
        $this->assertLessThan($notificationPosition, $accountPosition);
        $this->assertNotFalse($administrationPosition);
        $this->assertNotFalse($sidebarInboxPosition);
        $this->assertLessThan($sidebarInboxPosition, $administrationPosition);
        $this->assertGreaterThanOrEqual(3, substr_count($content, 'href="'.route('material-requests.index').'"'));
    }

    public function test_sidebar_persists_on_other_application_pages_and_highlights_the_active_module(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)
            ->get(route('stock-os.index'))
            ->assertOk();

        $response->assertSee('id="app-navigation-sidebar"', false);
        $response
            ->assertSee('Stock Bahan Kimia (OS)')
            ->assertSee('aria-current="page"', false);
    }

    public function test_dashboard_price_search_displays_latest_purchase_and_history(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        MasterMaterial::create([
            'material_code' => 'MAT-SEARCH',
            'short_text' => 'Pompa uji',
            'material_group' => 'POMPA',
        ]);
        PoHistory::create([
            'po_number' => 'PO-SEARCH-001',
            'po_date' => '2026-01-01',
            'material_code' => 'MAT-SEARCH',
            'short_text' => 'Pompa uji',
            'vendor' => 'Vendor Lama',
            'qty' => 1,
            'currency' => 'IDR',
            'unit_price' => 100000,
            'source_key' => 'search-test-old',
        ]);
        PoHistory::create([
            'po_number' => 'PO-SEARCH-002',
            'po_date' => '2026-02-01',
            'material_code' => 'MAT-SEARCH',
            'short_text' => 'Pompa uji',
            'vendor' => 'Vendor Terbaru',
            'qty' => 2,
            'currency' => 'IDR',
            'unit_price' => 125000,
            'source_key' => 'search-test-new',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['code' => 'MAT-SEARCH']))
            ->assertOk()
            ->assertSee('Vendor Terbaru')
            ->assertSee('125.000')
            ->assertSee('PO-SEARCH-001')
            ->assertSee('PO-SEARCH-002')
            ->assertSee('data-toggle-po-details', false)
            ->assertSee('data-dismiss-price-results', false)
            ->assertSee('Total harga');
    }

    public function test_dashboard_price_search_finds_material_by_name(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        MasterMaterial::create([
            'material_code' => 'MAT-NAME-SEARCH',
            'short_text' => 'Pompa dosing kimia',
            'material_group' => 'POMPA',
        ]);
        PoHistory::create([
            'po_number' => 'PO-NAME-SEARCH',
            'po_date' => '2026-03-01',
            'material_code' => 'MAT-NAME-SEARCH',
            'short_text' => 'Pompa dosing kimia',
            'vendor' => 'Vendor Pompa',
            'qty' => 1,
            'currency' => 'IDR',
            'unit_price' => 250000,
            'source_key' => 'search-test-name',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard', ['code' => 'dosing kimia']))
            ->assertOk()
            ->assertSee('MAT-NAME-SEARCH')
            ->assertSee('Pompa dosing kimia')
            ->assertSee('Vendor Pompa')
            ->assertSee('250.000');

        $this->get(route('price-check', ['code' => 'dosing kimia']))
            ->assertOk()
            ->assertSee('MAT-NAME-SEARCH')
            ->assertSee('PO-NAME-SEARCH');
    }

    public function test_dashboard_price_search_returns_only_the_results_fragment_for_ajax(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        MasterMaterial::create([
            'material_code' => 'MAT-AJAX-SEARCH',
            'short_text' => 'Pompa AJAX',
        ]);
        PoHistory::create([
            'po_number' => 'PO-AJAX-SEARCH',
            'po_date' => '2026-04-01',
            'material_code' => 'MAT-AJAX-SEARCH',
            'short_text' => 'Pompa AJAX',
            'vendor' => 'Vendor AJAX',
            'qty' => 1,
            'currency' => 'IDR',
            'unit_price' => 300000,
            'source_key' => 'search-test-ajax',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('dashboard', ['code' => 'Pompa AJAX']));

        $response->assertOk()->assertJsonStructure(['html']);
        $this->assertStringContainsString('Vendor AJAX', $response->json('html'));
        $this->assertStringContainsString('MAT-AJAX-SEARCH', $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
    }

    public function test_dashboard_price_search_shows_an_empty_state_for_unknown_material(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('dashboard', ['code' => 'UNKNOWN-MATERIAL']))
            ->assertOk()
            ->assertSee('Material tidak ditemukan');
    }

    public function test_admin_imports_material_and_po_workbooks_and_sees_latest_price(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->postJson(route('master-materials.import'), [
            'rows' => [[
                'Material' => 'MAT-001',
                'Short Text' => 'Pompa industri',
                'Base Unit of Measure' => 'EA',
                'Material Group' => 'POMPA',
                'Material Type' => 'RAW',
                'Valuation Class' => '3000',
            ]],
        ])->assertOk()->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('master_materials', [
            'material_code' => 'MAT-001',
            'short_text' => 'Pompa industri',
            'uom' => 'EA',
        ]);

        $this->postJson(route('po-history.import'), [
            'rows' => [
                [
                    'Purchasing Document' => 'PO-001',
                    'Document Date' => '2026-01-01',
                    'Material' => 'MAT-001',
                    'Short Text' => 'Pompa industri',
                    'Supplier/Supplying Plant' => 'Vendor Lama',
                    'Order Quantity' => 2,
                    'Order Unit' => 'EA',
                    'Net Price' => 100000,
                ],
                [
                    'Purchasing Document' => 'PO-002',
                    'Document Date' => '2026-02-01',
                    'Material' => 'MAT-001',
                    'Short Text' => 'Pompa industri',
                    'Supplier/Supplying Plant' => 'Vendor Baru',
                    'Order Quantity' => 2,
                    'Order Unit' => 'EA',
                    'Net Price' => 125000,
                ],
            ],
        ])->assertOk()->assertJsonPath('imported', 2);

        $this->get(route('price-check', ['code' => 'MAT-001']))
            ->assertOk()
            ->assertSee('Vendor Baru')
            ->assertSee('125.000');

        $this->assertSame(2, PoHistory::count());
    }

    public function test_staff_cannot_import_reference_workbooks(): void
    {
        $staff = User::factory()->create(['role' => 'User']);

        $this->actingAs($staff)
            ->postJson(route('master-materials.import'), ['rows' => [['Material' => 'MAT-001']]])
            ->assertForbidden();

        $this->assertDatabaseCount('master_materials', 0);
    }
}
