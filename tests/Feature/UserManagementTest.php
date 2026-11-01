<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_create_and_edit_forms(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create(['username' => 'Operator Lama']);

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Tambah Pengguna')
            ->assertSee('name="username"', false)
            ->assertSee('value="User"', false)
            ->assertDontSee('value="Staff"', false)
            ->assertSee('value="Warehouse Staff"', false);

        $this->get(route('users.edit', $user->user_id))
            ->assertOk()
            ->assertSee('Edit Pengguna')
            ->assertSee('Operator Lama')
            ->assertSee('value="User"', false)
            ->assertDontSee('value="Staff"', false)
            ->assertSee('value="Warehouse Staff"', false);
    }

    public function test_user_list_displays_admin_accounts_before_user_accounts(): void
    {
        $admin = User::factory()->create([
            'username' => 'Zeta Admin',
            'role' => 'Admin',
        ]);
        $staff = User::factory()->create([
            'username' => 'Alpha User',
            'role' => 'User',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();

        $adminPosition = strpos($response->getContent(), 'Zeta Admin');
        $userPosition = strpos($response->getContent(), 'Alpha User');

        $this->assertNotFalse($adminPosition);
        $this->assertNotFalse($userPosition);
        $this->assertLessThan($userPosition, $adminPosition);
    }

    public function test_admin_can_create_a_user_using_the_active_username_column(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'Operator Baru',
                'email' => 'operator.baru@example.test',
                'password' => 'password123',
                'role' => 'User',
            ])
            ->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'User berhasil ditambahkan.');

        $this->assertDatabaseHas('users', [
            'username' => 'Operator Baru',
            'email' => 'operator.baru@example.test',
            'role' => 'User',
        ]);
        $this->assertTrue(Hash::check('password123', User::query()->where('email', 'operator.baru@example.test')->value('password')));
    }

    public function test_admin_cannot_assign_the_legacy_staff_role(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'Role Lama',
                'email' => 'role.lama@example.test',
                'password' => 'password123',
                'role' => 'Staff',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'role.lama@example.test']);
    }

    public function test_admin_can_create_a_warehouse_staff_user(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'Petugas Gudang',
                'email' => 'petugas.gudang@example.test',
                'password' => 'password123',
                'role' => 'Warehouse Staff',
            ])
            ->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'User berhasil ditambahkan.');

        $this->assertDatabaseHas('users', [
            'username' => 'Petugas Gudang',
            'email' => 'petugas.gudang@example.test',
            'role' => 'Warehouse Staff',
        ]);
    }

    public function test_admin_can_update_a_user_without_changing_password_when_password_is_blank(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create([
            'username' => 'Operator Lama',
            'email' => 'operator@example.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $user->user_id), [
                'username' => 'Operator Diperbarui',
                'email' => 'operator@example.test',
                'password' => '',
                'role' => 'Warehouse Staff',
            ])
            ->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'User berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'user_id' => $user->user_id,
            'username' => 'Operator Diperbarui',
            'email' => 'operator@example.test',
            'role' => 'Warehouse Staff',
        ]);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_user_cannot_create_or_update_users(): void
    {
        $userAccount = User::factory()->create(['role' => 'User']);
        $user = User::factory()->create();

        $this->actingAs($userAccount)
            ->get(route('users.create'))
            ->assertForbidden();

        $this->put(route('users.update', $user->user_id), [
            'username' => 'Tidak Diizinkan',
            'email' => $user->email,
            'role' => 'User',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'Tidak Diizinkan']);
    }

    public function test_legacy_staff_role_migration_renames_existing_accounts_to_user(): void
    {
        $user = User::factory()->create(['role' => 'Staff']);
        $migration = require database_path('migrations/2026_11_01_043055_rename_staff_role_to_user_in_users_table.php');

        $migration->up();

        $this->assertDatabaseHas('users', [
            'user_id' => $user->user_id,
            'role' => 'User',
        ]);
    }
}
