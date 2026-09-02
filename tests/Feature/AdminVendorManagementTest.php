<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVendorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_list_shows_person_name_and_store_name(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create([
            'role' => 'vendor', 'name' => 'Amina Ali', 'store_name' => "Amina's Bakes",
        ]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->assertSee('Amina Ali')
            ->assertSee("Amina's Bakes");
    }

    public function test_admin_can_suspend_and_reactivate_a_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('toggleActive', $vendor->id);

        $this->assertFalse($vendor->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('toggleActive', $vendor->id);

        $this->assertTrue($vendor->fresh()->is_active);
    }

    public function test_admin_can_mark_a_vendor_verified(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'is_verified' => false]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('toggleVerified', $vendor->id);

        $this->assertTrue($vendor->fresh()->is_verified);
    }

    public function test_admin_can_upgrade_vendor_to_unlimited_premium(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => 5]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('upgradeToPremium', $vendor->id);

        $this->assertNull($vendor->fresh()->max_products_limit);
    }

    public function test_admin_can_downgrade_vendor_to_basic(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'max_products_limit' => null]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('downgradeToBasic', $vendor->id);

        $this->assertSame(5, $vendor->fresh()->max_products_limit);
    }

    public function test_admin_cannot_manage_a_super_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherAdmin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('toggleActive', $otherAdmin->id)
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('deleteVendor', $vendor->id);

        $this->assertSoftDeleted($vendor);
    }

    public function test_deleted_vendors_store_is_no_longer_publicly_accessible(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('deleteVendor', $vendor->id);

        $this->get(route('store.show', $vendor))->assertNotFound();
    }

    public function test_admin_cannot_delete_a_super_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherAdmin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('deleteVendor', $otherAdmin->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($otherAdmin);
    }

    public function test_admin_can_mark_a_vendor_as_the_platform_store(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'is_platform_store' => false]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('togglePlatformStore', $vendor->id);

        $this->assertTrue($vendor->fresh()->is_platform_store);
    }

    public function test_vendor_cannot_access_vendor_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.vendors.index'))
            ->assertForbidden();
    }
}
