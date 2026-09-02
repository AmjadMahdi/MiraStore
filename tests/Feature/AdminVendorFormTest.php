<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVendorFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form')
            ->set('name', 'Amina')
            ->set('store_name', "Amina's Bakes")
            ->set('email', 'amina@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->call('save')
            ->assertRedirect(route('admin.vendors.index'));

        $vendor = User::where('email', 'amina@example.com')->first();

        $this->assertNotNull($vendor);
        $this->assertSame('vendor', $vendor->role);
        $this->assertSame(5, $vendor->max_products_limit);
        $this->assertTrue(Hash::check('password123', $vendor->password));
    }

    public function test_admin_can_create_an_unlimited_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form')
            ->set('name', 'Amina')
            ->set('store_name', "Amina's Bakes")
            ->set('email', 'amina@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->set('unlimited_products', true)
            ->call('save')
            ->assertRedirect(route('admin.vendors.index'));

        $vendor = User::where('email', 'amina@example.com')->first();

        $this->assertNull($vendor->max_products_limit);
    }

    public function test_vendor_creation_requires_a_unique_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'vendor', 'email' => 'taken@example.com']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form')
            ->set('name', 'Amina')
            ->set('store_name', "Amina's Bakes")
            ->set('email', 'taken@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors('email');
    }

    public function test_admin_can_edit_a_vendors_data_without_changing_password(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'store_name' => 'Old Name',
            'whatsapp_number' => '+9677700000',
            'password' => Hash::make('original-password'),
        ]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form', ['vendor' => $vendor])
            ->set('store_name', 'New Name')
            ->set('is_verified', true)
            ->call('save')
            ->assertRedirect(route('admin.vendors.index'));

        $vendor->refresh();

        $this->assertSame('New Name', $vendor->store_name);
        $this->assertTrue($vendor->is_verified);
        $this->assertTrue(Hash::check('original-password', $vendor->password));
    }

    public function test_admin_can_reset_a_vendors_password(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create([
            'role' => 'vendor', 'store_name' => 'Bakes', 'whatsapp_number' => '+9677700000',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form', ['vendor' => $vendor])
            ->set('password', 'brand-new-password')
            ->call('save')
            ->assertRedirect(route('admin.vendors.index'));

        $this->assertTrue(Hash::check('brand-new-password', $vendor->fresh()->password));
    }

    public function test_editing_email_keeps_uniqueness_check_but_allows_own_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'email' => 'vendor@example.com']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form', ['vendor' => $vendor])
            ->set('email', 'vendor@example.com')
            ->call('save')
            ->assertHasNoErrors('email');
    }

    public function test_vendor_form_requires_super_admin(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.vendors.create'))
            ->assertForbidden();
    }
}
