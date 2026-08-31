<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVendorManagementTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_vendor_cannot_access_vendor_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.vendors.index'))
            ->assertForbidden();
    }
}
