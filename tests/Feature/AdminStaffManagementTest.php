<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_supervisor_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.staff-form')
            ->set('name', 'Sara')
            ->set('email', 'sara@example.com')
            ->set('password', 'password123')
            ->set('role', 'supervisor')
            ->call('save')
            ->assertRedirect(route('admin.staff.index'));

        $supervisor = User::where('email', 'sara@example.com')->first();

        $this->assertNotNull($supervisor);
        $this->assertSame('supervisor', $supervisor->role);
        $this->assertTrue(Hash::check('password123', $supervisor->password));
    }

    public function test_super_admin_can_create_another_super_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.staff-form')
            ->set('name', 'Omar')
            ->set('email', 'omar@example.com')
            ->set('password', 'password123')
            ->set('role', 'super_admin')
            ->call('save');

        $this->assertSame('super_admin', User::where('email', 'omar@example.com')->first()->role);
    }

    public function test_supervisor_cannot_access_staff_management_routes(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)->get(route('admin.staff.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('admin.staff.create'))->assertForbidden();
    }

    public function test_supervisor_can_access_other_admin_routes(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($supervisor)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('admin.vendors.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('admin.carts.index'))->assertOk();
        $this->actingAs($supervisor)->get(route('admin.activity.index'))->assertOk();
    }

    public function test_super_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.staff-management')
            ->call('toggleActive', $admin->id)
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.staff-management')
            ->call('deleteStaff', $admin->id)
            ->assertForbidden();

        $this->assertNotNull($admin->fresh());
    }

    public function test_super_admin_can_deactivate_another_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        Livewire::actingAs($admin)
            ->test('admin.staff-management')
            ->call('toggleActive', $supervisor->id);

        $this->assertFalse($supervisor->fresh()->is_active);
    }

    public function test_super_admin_can_delete_another_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        Livewire::actingAs($admin)
            ->test('admin.staff-management')
            ->call('deleteStaff', $supervisor->id);

        $this->assertSoftDeleted($supervisor);
    }

    public function test_editing_own_account_cannot_change_own_role_or_active_state(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test('admin.staff-form', ['staff' => $admin])
            ->set('name', 'Updated Name')
            ->set('role', 'supervisor')
            ->set('is_active', false)
            ->call('save');

        $admin->refresh();
        $this->assertSame('Updated Name', $admin->name);
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_vendor_cannot_access_any_admin_route(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.staff.index'))->assertForbidden();
    }
}
