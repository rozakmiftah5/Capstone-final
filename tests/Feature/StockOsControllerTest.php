<?php

namespace Tests\Feature;

use App\Models\StockOsItem;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockOsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_os_page_has_no_excel_import_and_import_endpoint_is_removed(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        Warehouse::create(['warehouse_code' => 'WH-01', 'warehouse_name' => 'Gudang Utama']);
        StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
            'stock_qty' => 10,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertDontSee('Impor Excel')
            ->assertDontSee('data-excel-import', false)
            ->assertSee('Tambah Stock Bahan Kimia (OS)')
            ->assertSee('Ekspor Excel')
            ->assertSee('Catatan Pemakaian')
            ->assertSee('Filter Stock Bahan Kimia (OS)')
            ->assertSee('Stok (kg)')
            ->assertSee('<option value="Gudang Utama"', false);

        $page = $response->getContent();
        $exportPosition = strpos($page, 'Ekspor Excel');
        $filterPosition = strpos($page, 'Filter Stock Bahan Kimia (OS)');
        $this->assertNotFalse($exportPosition);
        $this->assertLessThan($filterPosition, $exportPosition);

        $this->post('/stock-os/import', ['rows' => [['Material' => 'Resin']]])
            ->assertStatus(405);
    }

    public function test_stock_os_top_export_card_preserves_active_filters(): void
    {
        StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
            'stock_qty' => 1,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('stock-os.index', [
                'search' => 'Resin',
                'warehouse' => 'Gudang Utama',
                'critical' => 1,
                'sort' => 'stock',
            ]))
            ->assertOk()
            ->assertSee(route('stock-os.export', [
                'search' => 'Resin',
                'warehouse' => 'Gudang Utama',
                'critical' => 1,
                'sort' => 'stock',
            ]));
    }

    public function test_stock_os_can_only_be_created_with_a_managed_warehouse(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        Warehouse::create(['warehouse_code' => 'WH-01', 'warehouse_name' => 'Gudang Utama']);

        $this->actingAs($admin)
            ->post(route('stock-os.store'), [
                'material_name' => 'Resin',
                'warehouse_name' => 'Gudang Utama',
                'stock_qty' => 10,
                'outstanding_qty' => 0,
                'safety_stock' => 2,
            ])
            ->assertRedirectToRoute('stock-os.index');

        $this->assertDatabaseHas('stock_os_items', [
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
        ]);

        $this->from(route('stock-os.index'))
            ->post(route('stock-os.store'), [
                'material_name' => 'Additive',
                'warehouse_name' => 'Gudang Tidak Terdaftar',
                'stock_qty' => 0,
                'outstanding_qty' => 0,
                'safety_stock' => 0,
            ])
            ->assertSessionHasErrors('warehouse_name');

        $this->assertDatabaseMissing('stock_os_items', [
            'material_name' => 'Additive',
        ]);
    }

    public function test_stock_os_edit_form_uses_managed_warehouses(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $warehouse = Warehouse::create(['warehouse_code' => 'WH-01', 'warehouse_name' => 'Gudang Utama']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
            'stock_qty' => 10,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('stock-os.edit', $item))
            ->assertOk()
            ->assertSee('<option value="'.$warehouse->warehouse_name.'" selected>', false);

        $this->put(route('stock-os.update', $item), [
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Tidak Terdaftar',
            'stock_qty' => 10,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ])->assertSessionHasErrors('warehouse_name');

        $this->assertDatabaseHas('stock_os_items', [
            'id' => $item->id,
            'warehouse_name' => 'Gudang Utama',
        ]);
    }

    public function test_stock_os_quantities_show_kg_without_fixed_trailing_zeroes(): void
    {
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
            'stock_qty' => 1.5,
            'outstanding_qty' => 2,
            'safety_stock' => 0.25,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertSee('1,5 kg')
            ->assertSee('2 kg')
            ->assertSee('0,25 kg')
            ->assertDontSee('1,500')
            ->assertDontSee('2,000');
    }

    public function test_stock_os_excel_export_is_a_formatted_workbook_with_all_filtered_rows(): void
    {
        $usageItem = null;
        for ($index = 1; $index <= 21; $index++) {
            $item = StockOsItem::create([
                'material_name' => sprintf('Resin %02d', $index),
                'warehouse_name' => 'Gudang Utama',
                'stock_qty' => 1,
                'outstanding_qty' => 0,
                'safety_stock' => 2,
                'notes' => $index === 1 ? '=HYPERLINK("https://example.com","klik")' : null,
            ]);
            if ($index === 1) {
                $usageItem = $item;
            }
        }
        StockOsItem::create([
            'material_name' => 'Additive',
            'warehouse_name' => 'Gudang Lain',
            'stock_qty' => 0,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ]);
        $user = User::factory()->create(['username' => 'Operator Laporan']);
        $usageItem->transactions()->create([
            'user_id' => $user->user_id,
            'type' => 'OUT',
            'qty' => 0.5,
            'transaction_date' => '2026-10-19',
            'notes' => 'Pemakaian untuk uji produksi',
        ]);
        $usageItem->transactions()->create([
            'user_id' => $user->user_id,
            'type' => 'IN',
            'qty' => 2,
            'transaction_date' => '2026-10-19',
            'supplier' => 'Pemasok uji',
            'notes' => 'Penerimaan tidak termasuk catatan pemakaian',
        ]);

        DB::connection()->enableQueryLog();

        $response = $this->actingAs(User::factory()->create())
            ->get(route('stock-os.export', [
                'warehouse' => 'Gudang Utama',
                'critical' => 1,
                'sort' => 'stock',
            ]));
        $workbookContents = $response->streamedContent();
        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $usageQuery = collect($queries)->first(
            fn (array $query): bool => str_contains($query['query'], 'stock_transactions')
                && str_contains($query['query'], 'stock_os_item_id'),
        );
        $this->assertNotNull($usageQuery);
        $this->assertDoesNotMatchRegularExpression(
            '/stock_os_item_id.*in\\s*\\([^)]*select[^)]*limit/i',
            $usageQuery['query'],
        );

        $workbookPath = tempnam(sys_get_temp_dir(), 'stock-os-test-');
        file_put_contents($workbookPath, $workbookContents);
        $workbook = new \ZipArchive;

        $this->assertSame(true, $workbook->open($workbookPath));
        $this->assertNotFalse($workbook->getFromName('xl/tables/table1.xml'));
        $this->assertNotFalse($workbook->getFromName('xl/tables/table2.xml'));
        $this->assertNotFalse($workbook->getFromName('xl/styles.xml'));

        $sheet = $workbook->getFromName('xl/worksheets/sheet1.xml');
        $usageSheet = $workbook->getFromName('xl/worksheets/sheet2.xml');
        $table = $workbook->getFromName('xl/tables/table1.xml');
        $usageTable = $workbook->getFromName('xl/tables/table2.xml');
        $workbookDefinition = $workbook->getFromName('xl/workbook.xml');
        $styles = $workbook->getFromName('xl/styles.xml');
        $this->assertSame(13, $workbook->numFiles);
        for ($index = 0; $index < $workbook->numFiles; $index++) {
            $entryName = $workbook->getNameIndex($index);
            if (str_ends_with($entryName, '.xml')) {
                $this->assertNotFalse(simplexml_load_string($workbook->getFromIndex($index)));
            }
        }
        $workbook->close();
        unlink($workbookPath);

        $this->assertStringContainsString('Resin 21', $sheet);
        $this->assertStringNotContainsString('Additive', $sheet);
        $this->assertSame(22, substr_count($sheet, '<row '));
        $this->assertStringContainsString('TableStyleMedium2', $table);
        $this->assertStringContainsString('name="StockOSReport"', $table);
        $this->assertStringContainsString('ref="A1:H22"', $table);
        $this->assertStringContainsString('ySplit="1"', $sheet);
        $this->assertStringContainsString('=HYPERLINK', $sheet);
        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertStringContainsString('Stock Bahan Kimia (OS)', $workbookDefinition);
        $this->assertStringContainsString('Catatan Pemakaian', $workbookDefinition);
        $this->assertStringContainsString('Pemakaian untuk uji produksi', $usageSheet);
        $this->assertStringContainsString($user->email, $usageSheet);
        $this->assertStringNotContainsString('Penerimaan tidak termasuk catatan pemakaian', $usageSheet);
        $this->assertStringContainsString('name="StockOSUsageReport"', $usageTable);
        $this->assertStringContainsString('#,##0.### &quot;kg&quot;', $styles);
    }

    public function test_stock_os_excel_export_requires_authentication(): void
    {
        $this->get(route('stock-os.export'))
            ->assertRedirectToRoute('login');
    }

    public function test_usage_history_and_excel_export_work_when_users_table_has_no_username_column(): void
    {
        $user = User::factory()->create();
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Gudang Utama',
            'stock_qty' => 10,
            'outstanding_qty' => 0,
            'safety_stock' => 2,
        ]);
        $item->transactions()->create([
            'user_id' => $user->user_id,
            'type' => 'OUT',
            'qty' => 1,
            'transaction_date' => '2026-10-19',
            'notes' => 'Catatan aman dari QueryException',
        ]);
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
        });

        $this->actingAs($user)
            ->get(route('stock-os.history', $item))
            ->assertOk()
            ->assertSee('Catatan aman dari QueryException')
            ->assertSee($user->email);

        $export = $this->get(route('stock-os.export'));
        $export->assertOk();

        $workbook = new \ZipArchive;
        $workbookPath = tempnam(sys_get_temp_dir(), 'stock-os-no-user-name-');
        file_put_contents($workbookPath, $export->streamedContent());
        $this->assertSame(true, $workbook->open($workbookPath));
        $usageSheet = $workbook->getFromName('xl/worksheets/sheet2.xml');
        $workbook->close();
        unlink($workbookPath);

        $this->assertStringContainsString($user->email, $usageSheet);
    }

    public function test_valid_usage_reduces_stock_and_creates_a_transaction(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Krenceng',
            'stock_qty' => 10,
            'outstanding_qty' => 5,
            'safety_stock' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertSee('Catat transaksi')
            ->assertSee(route('stock-os.use', $item), false)
            ->assertSee(route('stock-os.receive', $item), false);

        $this->actingAs($user)
            ->post(route('stock-os.use', $item), [
                'qty' => 3.5,
                'transaction_date' => '2026-10-19',
                'notes' => 'Pemakaian produksi',
            ])
            ->assertRedirectToRoute('stock-os.history', $item);

        $this->assertDatabaseHas('stock_os_items', ['id' => $item->id, 'stock_qty' => 6.5]);
        $this->assertDatabaseHas('stock_transactions', [
            'stock_os_item_id' => $item->id,
            'type' => 'OUT',
            'qty' => 3.5,
            'notes' => 'Pemakaian produksi',
        ]);

        $this->get(route('stock-os.history', $item))
            ->assertOk()
            ->assertSee('Pemakaian stok berhasil dicatat.')
            ->assertSee('Pemakaian produksi');
    }

    public function test_usage_greater_than_stock_does_not_change_stock_or_create_a_transaction(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Krenceng',
            'stock_qty' => 2,
            'outstanding_qty' => 0,
            'safety_stock' => 1,
        ]);

        $this->actingAs($user)
            ->from(route('stock-os.index'))
            ->post(route('stock-os.use', $item), [
                'qty' => 3,
                'transaction_date' => '2026-10-19',
            ])
            ->assertSessionHasErrors('qty');

        $this->assertDatabaseHas('stock_os_items', ['id' => $item->id, 'stock_qty' => 2]);
        $this->assertSame(0, StockTransaction::count());
    }

    public function test_user_cannot_record_stock_usage_or_receipts(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Krenceng',
            'stock_qty' => 10,
            'outstanding_qty' => 8,
            'safety_stock' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertDontSee('Kurangi Stok')
            ->assertDontSee('Catat penerimaan')
            ->assertDontSee('Tambah Stok');

        $this->actingAs($user)
            ->post(route('stock-os.use', $item), [
                'qty' => 3,
                'transaction_date' => '2026-10-19',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('stock-os.receive', $item), [
                'qty' => 3,
                'supplier' => 'PT. Pemasok',
                'transaction_date' => '2026-10-19',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('stock_os_items', [
            'id' => $item->id,
            'stock_qty' => 10,
            'outstanding_qty' => 8,
        ]);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_warehouse_staff_can_record_stock_operations_but_cannot_manage_stock_items(): void
    {
        $warehouseStaff = User::factory()->create(['role' => 'Warehouse Staff']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Krenceng',
            'stock_qty' => 10,
            'outstanding_qty' => 8,
            'safety_stock' => 2,
        ]);

        $this->actingAs($warehouseStaff)
            ->get(route('stock-os.index'))
            ->assertOk()
            ->assertSee('Catat transaksi')
            ->assertSee('Kurangi Stok')
            ->assertSee('Catat Penerimaan')
            ->assertSee('Tambah Stok')
            ->assertDontSee('Tambah Stock Bahan Kimia (OS)')
            ->assertDontSee(route('stock-os.edit', $item), false);

        $this->post(route('stock-os.store'), [])->assertForbidden();
        $this->get(route('stock-os.edit', $item))->assertForbidden();
        $this->put(route('stock-os.update', $item), [])->assertForbidden();
        $this->delete(route('stock-os.destroy', $item))->assertForbidden();

        $this->post(route('stock-os.use', $item), [
            'qty' => 2.5,
            'transaction_date' => '2026-10-19',
        ])->assertRedirectToRoute('stock-os.history', $item);

        $this->post(route('stock-os.receive', $item), [
            'qty' => 3,
            'supplier' => 'PT. Pemasok',
            'transaction_date' => '2026-10-20',
        ])->assertRedirectToRoute('stock-os.history', $item);

        $this->assertDatabaseHas('stock_os_items', [
            'id' => $item->id,
            'stock_qty' => 10.5,
            'outstanding_qty' => 5,
        ]);
        $this->assertDatabaseCount('stock_transactions', 2);
    }

    public function test_receipt_increases_stock_and_reduces_outstanding_purchase_quantity(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $item = StockOsItem::create([
            'material_name' => 'Resin',
            'warehouse_name' => 'Krenceng',
            'stock_qty' => 10,
            'outstanding_qty' => 8,
            'safety_stock' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('stock-os.receive', $item), [
                'qty' => 3,
                'supplier' => 'PT. Pemasok',
                'transaction_date' => '2026-10-19',
                'notes' => 'Surat jalan 01',
            ])
            ->assertRedirectToRoute('stock-os.history', $item);

        $this->assertDatabaseHas('stock_os_items', [
            'id' => $item->id,
            'stock_qty' => 13,
            'outstanding_qty' => 5,
        ]);
        $this->assertDatabaseHas('stock_transactions', [
            'stock_os_item_id' => $item->id,
            'type' => 'IN',
            'supplier' => 'PT. Pemasok',
        ]);

        $this->get(route('stock-os.history', $item))
            ->assertOk()
            ->assertSee('Penerimaan barang berhasil dicatat.')
            ->assertSee('Surat jalan 01');
    }
}
