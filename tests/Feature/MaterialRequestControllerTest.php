<?php

namespace Tests\Feature;

use App\Models\MaterialRequest;
use App\Models\User;
use App\Notifications\MaterialRequestCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_lppm_submission_is_saved_for_the_authenticated_requester(): void
    {
        $staff = User::factory()->create(['username' => 'Staff Uji']);

        $response = $this->actingAs($staff)->post(route('material-requests.store'), [
            'letter_number' => '001/LPPM',
            'requesting_division' => 'Operasi',
            'requesting_department' => 'Produksi',
            'request_date' => '2026-10-19',
            'materials' => [[
                'mtart' => 'ERSA',
                'werks' => 'KTI1',
                'lgort' => '1000',
                'maktx' => 'Seal pompa',
                'meins' => 'EA',
                'purchasingGroup' => '001',
                'matkl' => 'PUMP',
                'bklas' => '3000',
                'peinh' => 1,
                'verpr' => 120000,
                'longText' => 'Seal untuk pompa transfer.',
            ]],
        ]);

        $response->assertRedirectToRoute('material-requests.create');
        $this->assertDatabaseHas('material_requests', [
            'user_id' => $staff->user_id,
            'letter_number' => '001/LPPM',
            'requesting_division' => 'Operasi',
        ]);

        $request = MaterialRequest::query()->with('items')->firstOrFail();
        $this->assertSame('Seal pompa', $request->items->first()->material_description);
        $this->assertSame('3000', $request->items->first()->details['bklas']);

        $this->actingAs($staff)
            ->get(route('material-requests.show', $request))
            ->assertOk()
            ->assertSee('Seal pompa');

        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('material-requests.index'))
            ->assertOk()
            ->assertSee(route('material-requests.show', $request), false)
            ->assertDontSee(route('material-requests.download', $request), false)
            ->assertSee('Lihat detail');

        $this->get(route('material-requests.show', $request))
            ->assertOk()
            ->assertSee(route('material-requests.download', $request), false)
            ->assertSee('Detail Form Pengajuan Penambahan Kode Material')
            ->assertSee('001/LPPM')
            ->assertSee('Operasi')
            ->assertSee('Produksi')
            ->assertSee('Seal pompa')
            ->assertSee('Material Type (MTART)')
            ->assertSee('ERSA')
            ->assertSee('Plant (WERKS)')
            ->assertSee('KTI1')
            ->assertSee('Storage Location (LGORT)')
            ->assertSee('1000')
            ->assertSee('Base Unit of Measure (MEINS)')
            ->assertSee('Purchasing Group')
            ->assertSee('Material Group (MATKL)')
            ->assertSee('PUMP')
            ->assertSee('Valuation Class (BKLAS)')
            ->assertSee('3000')
            ->assertSee('Moving Average Price (VERPR)')
            ->assertSee('120000')
            ->assertSee('Long Text Material')
            ->assertSee('Seal untuk pompa transfer.');

        $download = $this->get(route('material-requests.download', $request))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($download->getContent(), 0, 5));
    }

    public function test_admin_can_complete_request_with_sap_codes_and_requester_receives_a_notification(): void
    {
        $requester = User::factory()->create(['username' => 'Pengaju']);
        $admin = User::factory()->create(['role' => 'Admin']);
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-ABC123',
            'user_id' => $requester->user_id,
            'status' => 'new',
        ]);
        $firstItem = $materialRequest->items()->create([
            'material_description' => 'Seal pompa',
            'details' => ['maktx' => 'Seal pompa'],
        ]);
        $secondItem = $materialRequest->items()->create([
            'material_description' => 'Bearing motor',
            'details' => ['maktx' => 'Bearing motor'],
        ]);

        $this->actingAs($admin)
            ->get(route('material-requests.show', $materialRequest))
            ->assertOk()
            ->assertSee('Input Nomor Kode Material SAP')
            ->assertSee('Selesai')
            ->assertSee('name="sap_codes['.$firstItem->id.']"', false)
            ->assertSee('name="sap_codes['.$secondItem->id.']"', false);

        $this->post(route('material-requests.complete', $materialRequest), [
            'sap_codes' => [
                $firstItem->id => 'SAP-10001',
                $secondItem->id => 'SAP-10002',
            ],
        ])->assertRedirectToRoute('material-requests.show', $materialRequest)
            ->assertSessionHas('success', "Pengajuan {$materialRequest->request_number} selesai diproses.");

        $this->assertDatabaseHas('material_requests', [
            'id' => $materialRequest->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($materialRequest->fresh()->completed_at);
        $this->assertDatabaseHas('material_request_items', [
            'id' => $firstItem->id,
            'sap_material_code' => 'SAP-10001',
        ]);
        $this->assertDatabaseHas('material_request_items', [
            'id' => $secondItem->id,
            'sap_material_code' => 'SAP-10002',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $requester->user_id,
            'type' => MaterialRequestCompleted::class,
        ]);
        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($admin)
            ->post(route('material-requests.complete', $materialRequest), [
                'sap_codes' => [
                    $firstItem->id => 'SAP-OVERWRITE',
                    $secondItem->id => 'SAP-OVERWRITE',
                ],
            ])
            ->assertRedirectToRoute('material-requests.show', $materialRequest);
        $this->assertDatabaseHas('material_request_items', [
            'id' => $firstItem->id,
            'sap_material_code' => 'SAP-10001',
        ]);
        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($admin)
            ->get(route('material-requests.index'))
            ->assertOk()
            ->assertSee('Feedback / Nomor Kode Material')
            ->assertSee('SAP-10001')
            ->assertSee('SAP-10002');

        $this->actingAs($requester)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi pengajuan selesai')
            ->assertSee($materialRequest->request_number)
            ->assertSee('SAP-10001')
            ->assertSee('SAP-10002');

        $this->get(route('material-requests.show', $materialRequest))
            ->assertOk()
            ->assertSee('SAP-10001')
            ->assertSee('SAP-10002')
            ->assertSee('Kembali ke Dashboard')
            ->assertDontSee('href="'.route('material-requests.index').'"', false);

        $this->assertSame(0, $requester->unreadNotifications()->count());
    }

    public function test_request_cannot_be_completed_until_every_sap_material_code_is_provided(): void
    {
        $requester = User::factory()->create();
        $admin = User::factory()->create(['role' => 'Admin']);
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-DEF456',
            'user_id' => $requester->user_id,
        ]);
        $firstItem = $materialRequest->items()->create([
            'material_description' => 'Seal pompa',
            'details' => ['maktx' => 'Seal pompa'],
        ]);
        $secondItem = $materialRequest->items()->create([
            'material_description' => 'Bearing motor',
            'details' => ['maktx' => 'Bearing motor'],
        ]);

        $this->actingAs($admin)
            ->from(route('material-requests.show', $materialRequest))
            ->post(route('material-requests.complete', $materialRequest), [
                'sap_codes' => [
                    $firstItem->id => 'SAP-20001',
                ],
            ])
            ->assertSessionHasErrors("sap_codes.{$secondItem->id}");

        $this->assertDatabaseHas('material_requests', [
            'id' => $materialRequest->id,
            'status' => 'new',
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('material_request_items', [
            'id' => $firstItem->id,
            'sap_material_code' => null,
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_non_admin_cannot_complete_a_material_request(): void
    {
        $requester = User::factory()->create();
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-GHI789',
            'user_id' => $requester->user_id,
        ]);
        $materialRequest->items()->create([
            'material_description' => 'Seal pompa',
            'details' => ['maktx' => 'Seal pompa'],
        ]);

        $this->actingAs($requester)
            ->post(route('material-requests.complete', $materialRequest), [
                'sap_codes' => [1 => 'SAP-30001'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('material_requests', [
            'id' => $materialRequest->id,
            'status' => 'new',
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_only_admin_can_read_the_material_request_inbox(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff)->get(route('material-requests.index'))->assertForbidden();
    }

    public function test_requester_can_download_their_material_request_as_a_pdf(): void
    {
        $requester = User::factory()->create(['username' => 'Pemohon PDF']);
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-PDF123',
            'user_id' => $requester->user_id,
            'letter_number' => 'PDF/001',
            'requesting_division' => 'Produksi',
            'status' => 'completed',
        ]);
        $materialRequest->items()->create([
            'material_description' => 'Seal PDF test',
            'details' => [
                'mtart' => 'ERSA',
                'maktx' => 'Seal PDF test',
                'longText' => 'Seal untuk pengujian PDF.',
            ],
            'sap_material_code' => 'SAP-PDF-100',
        ]);

        $response = $this->actingAs($requester)
            ->get(route('material-requests.download', $materialRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=Form-LPPM-LPPM-20261101-PDF123.pdf');

        $this->assertSame('%PDF-', substr($response->getContent(), 0, 5));
    }

    public function test_user_cannot_download_another_users_material_request(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-PRIVATE',
            'user_id' => $owner->user_id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('material-requests.download', $materialRequest))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_downloading_a_material_request(): void
    {
        $requester = User::factory()->create();
        $materialRequest = MaterialRequest::create([
            'request_number' => 'LPPM-20261101-GUEST1',
            'user_id' => $requester->user_id,
        ]);

        $this->get(route('material-requests.download', $materialRequest))
            ->assertRedirectToRoute('login');
    }
}
