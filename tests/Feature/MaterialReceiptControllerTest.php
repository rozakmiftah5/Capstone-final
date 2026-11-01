<?php

namespace Tests\Feature;

use App\Models\MasterMaterial;
use App\Models\Material;
use App\Models\MaterialReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_authenticated_user_can_view_material_receipts(): void
    {
        $this->get(route('material-receipts.index'))
            ->assertRedirectToRoute('login');

        $material = $this->createMaterial();
        $catalogMaterial = $this->createCatalogMaterial();
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-001',
            'suppllier_name' => 'Vendor Kimia',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'Admin']))
            ->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Penerimaan Barang Non-OS')
            ->assertSee('Vendor Kimia')
            ->assertSee($catalogMaterial->short_text)
            ->assertDontSee('Stok:')
            ->assertSee('Jumlah diterima')
            ->assertSee('name="supplier_name"', false)
            ->assertSee('list="receipt-suppliers"', false)
            ->assertSee('name="warehouse_id"', false)
            ->assertSee('Gudang Penerimaan');
    }

    public function test_user_cannot_access_or_record_material_receipts(): void
    {
        $material = $this->createMaterial(['material_stock' => 5]);
        $catalogMaterial = $this->createCatalogMaterial();
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-STAFF',
            'suppllier_name' => 'Vendor User',
        ]);
        $user = User::factory()->create(['role' => 'User']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('material-receipts.index'), false)
            ->assertDontSee('Penerimaan Barang');

        $this->get(route('material-receipts.index'))
            ->assertForbidden();

        $this->post(route('material-receipts.store'), [
            'master_material_id' => $catalogMaterial->id,
            'warehouse_id' => $material->warehouse_id,
            'supplier_name' => $supplier->suppllier_name,
            'quantity' => 3,
            'received_at' => '2026-10-19',
        ])->assertForbidden();

        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 5,
        ]);
        $this->assertDatabaseCount('material_receipts', 0);
    }

    public function test_warehouse_staff_can_access_and_record_material_receipts(): void
    {
        $warehouseStaff = User::factory()->create(['role' => 'Warehouse Staff']);
        $material = $this->createMaterial(['material_stock' => 5]);
        $catalogMaterial = $this->createCatalogMaterial();

        $this->actingAs($warehouseStaff)
            ->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Penerimaan Barang Non-OS');

        $this->post(route('material-receipts.store'), [
            'master_material_id' => $catalogMaterial->id,
            'warehouse_id' => $material->warehouse_id,
            'supplier_name' => 'Vendor Petugas Gudang',
            'quantity' => 2,
            'received_at' => '2026-10-19',
        ])->assertRedirectToRoute('material-receipts.index')
            ->assertSessionHas('success', 'Penerimaan barang berhasil dicatat dan stok telah diperbarui.');

        $this->assertDatabaseHas('material_receipts', [
            'material_code' => $catalogMaterial->material_code,
            'supplier_name' => 'Vendor Petugas Gudang',
            'received_by' => $warehouseStaff->user_id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 7,
        ]);
    }

    public function test_receipt_records_vendor_and_quantity_and_increases_material_stock(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $material = $this->createMaterial(['material_stock' => 5]);
        $catalogMaterial = $this->createCatalogMaterial();
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-002',
            'suppllier_name' => 'PT Vendor Penerimaan',
        ]);

        $this->actingAs($user)
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $material->warehouse_id,
                'supplier_name' => $supplier->suppllier_name,
                'quantity' => 7,
                'received_at' => '2026-10-19 00:00:00',
                'purchase_order_number' => 'PO-RECEIPT-001',
                'delivery_note_number' => 'SJ-RECEIPT-001',
                'notes' => 'Barang diterima dalam kondisi baik',
                'received_by' => 999999,
            ])
            ->assertRedirectToRoute('material-receipts.index')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 12,
        ]);
        $receipt = MaterialReceipt::query()->sole();
        $this->assertModelExists($receipt);
        $this->assertSame($material->material_id, $receipt->material_id);
        $this->assertSame($material->warehouse_id, $receipt->warehouse_id);
        $this->assertSame($catalogMaterial->material_code, $receipt->material_code);
        $this->assertSame($catalogMaterial->short_text, $receipt->material_name);
        $this->assertSame($supplier->supplier_id, $receipt->supplier_id);
        $this->assertSame('PT Vendor Penerimaan', $receipt->supplier_name);
        $this->assertSame($user->user_id, $receipt->received_by);
        $this->assertSame(7, $receipt->quantity);
        $this->assertSame('kg', $receipt->unit);
        $this->assertSame('2026-10-19', $receipt->received_at->toDateString());
        $this->assertSame('PO-RECEIPT-001', $receipt->purchase_order_number);
        $this->assertSame('SJ-RECEIPT-001', $receipt->delivery_note_number);
        $this->assertSame('Barang diterima dalam kondisi baik', $receipt->notes);

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Penerimaan barang berhasil dicatat')
            ->assertSee('PT Vendor Penerimaan')
            ->assertSee('PO-RECEIPT-001')
            ->assertSee('SJ-RECEIPT-001')
            ->assertSee('Gudang Penerimaan')
            ->assertSee('7 kg');
    }

    public function test_invalid_material_or_missing_vendor_name_does_not_change_stock_or_create_a_receipt(): void
    {
        $material = $this->createMaterial(['material_stock' => 4]);
        $catalogMaterial = $this->createCatalogMaterial();
        $user = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($user)
            ->from(route('material-receipts.index'))
            ->post(route('material-receipts.store'), [
                'master_material_id' => 999999,
                'warehouse_id' => $material->warehouse_id,
                'supplier_name' => 'Vendor Valid',
                'quantity' => 2,
                'received_at' => '2026-10-19',
            ])
            ->assertSessionHasErrors('master_material_id');

        $this->from(route('material-receipts.index'))
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $material->warehouse_id,
                'quantity' => 2,
                'received_at' => '2026-10-19',
            ])
            ->assertSessionHasErrors('supplier_name');

        $this->from(route('material-receipts.index'))
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => 999999,
                'supplier_name' => 'Vendor Valid',
                'quantity' => 2,
                'received_at' => '2026-10-19',
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 4,
        ]);
        $this->assertSame(0, MaterialReceipt::count());
    }

    public function test_vendor_name_longer_than_255_characters_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $material = $this->createMaterial(['material_stock' => 4]);
        $catalogMaterial = $this->createCatalogMaterial();

        $this->actingAs($user)
            ->from(route('material-receipts.index'))
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $material->warehouse_id,
                'supplier_name' => str_repeat('V', 256),
                'quantity' => 2,
                'received_at' => '2026-10-19',
            ])
            ->assertSessionHasErrors('supplier_name');

        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 4,
        ]);
        $this->assertDatabaseCount('material_receipts', 0);
    }

    public function test_catalog_material_without_data_material_match_can_be_received_without_changing_stock(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $catalogMaterial = $this->createCatalogMaterial([
            'material_code' => 'CATALOG-ONLY',
            'short_text' => 'Material katalog saja',
            'uom' => 'L',
        ]);
        $supplier = Supplier::create([
            'supplier_code' => 'SUP-004',
            'suppllier_name' => 'Vendor Katalog',
        ]);
        $warehouse = $this->createWarehouse();

        $this->actingAs($user)
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $warehouse->warehouse_id,
                'supplier_name' => $supplier->suppllier_name,
                'quantity' => 3,
                'received_at' => '2026-10-19',
            ])
            ->assertRedirectToRoute('material-receipts.index')
            ->assertSessionHas('success', 'Penerimaan berhasil dicatat. Stok Data Material tidak berubah karena kode ini belum terdaftar pada gudang yang dipilih.');

        $receipt = MaterialReceipt::query()->sole();
        $this->assertNull($receipt->material_id);
        $this->assertSame('CATALOG-ONLY', $receipt->material_code);
        $this->assertSame('Material katalog saja', $receipt->material_name);
        $this->assertSame($warehouse->warehouse_id, $receipt->warehouse_id);
        $this->assertSame('L', $receipt->unit);

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Gudang Penerimaan');
    }

    public function test_receipt_accepts_a_manually_entered_vendor_name_without_a_supplier_record(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $material = $this->createMaterial(['material_stock' => 5]);
        $catalogMaterial = $this->createCatalogMaterial();

        $this->actingAs($user)
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $material->warehouse_id,
                'supplier_name' => 'Vendor Baru Manual',
                'quantity' => 3,
                'received_at' => '2026-10-19',
            ])
            ->assertRedirectToRoute('material-receipts.index')
            ->assertSessionHas('success', 'Penerimaan barang berhasil dicatat dan stok telah diperbarui.');

        $receipt = MaterialReceipt::query()->sole();
        $this->assertModelExists($receipt);
        $this->assertNull($receipt->supplier_id);
        $this->assertSame('Vendor Baru Manual', $receipt->supplier_name);
        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 8,
        ]);

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Vendor Baru Manual');
    }

    public function test_receipt_in_a_different_warehouse_does_not_increase_stock_in_another_warehouse(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $material = $this->createMaterial(['material_stock' => 5]);
        $catalogMaterial = $this->createCatalogMaterial();
        $otherWarehouse = $this->createWarehouse([
            'warehouse_code' => 'WH-OTHER',
            'warehouse_name' => 'Gudang Lain',
        ]);

        $this->actingAs($user)
            ->post(route('material-receipts.store'), [
                'master_material_id' => $catalogMaterial->id,
                'warehouse_id' => $otherWarehouse->warehouse_id,
                'supplier_name' => 'Vendor Gudang Lain',
                'quantity' => 3,
                'received_at' => '2026-10-19',
            ])
            ->assertRedirectToRoute('material-receipts.index')
            ->assertSessionHas('success', 'Penerimaan berhasil dicatat. Stok Data Material tidak berubah karena kode ini belum terdaftar pada gudang yang dipilih.');

        $receipt = MaterialReceipt::query()->sole();
        $this->assertNull($receipt->material_id);
        $this->assertSame($otherWarehouse->warehouse_id, $receipt->warehouse_id);
        $this->assertDatabaseHas('materials', [
            'material_id' => $material->material_id,
            'material_stock' => 5,
        ]);

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertSee('Gudang Lain');
    }

    private function createMaterial(array $attributes = []): Material
    {
        $warehouse = $this->createWarehouse();

        return Material::create([
            'material_code' => 'MAT-RECEIPT',
            'material_name' => 'Bahan Kimia Non-OS',
            'material_stock' => 0,
            'sisa_po' => 0,
            'safety_stock' => 0,
            'category' => 'Kimia',
            'unit' => 'kg',
            'warehouse_id' => $warehouse->warehouse_id,
            ...$attributes,
        ]);
    }

    private function createWarehouse(array $attributes = []): Warehouse
    {
        return Warehouse::create([
            'warehouse_code' => 'WH-RECEIPT',
            'warehouse_name' => 'Gudang Penerimaan',
            ...$attributes,
        ]);
    }

    private function createCatalogMaterial(array $attributes = []): MasterMaterial
    {
        return MasterMaterial::create([
            'material_code' => 'MAT-RECEIPT',
            'short_text' => 'Bahan Kimia Non-OS',
            'uom' => 'kg',
            ...$attributes,
        ]);
    }
}
