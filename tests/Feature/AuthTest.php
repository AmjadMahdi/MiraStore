<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_register_and_lands_on_vendor_dashboard(): void
    {
        Livewire::test('auth.register')
            ->set('name', 'Amina')
            ->set('store_name', "Amina's Bakes")
            ->set('email', 'amina@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('vendor.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'amina@example.com',
            'role' => 'vendor',
            'max_products_limit' => 5,
        ]);

        $this->assertAuthenticated();
    }

    public function test_vendor_login_redirects_to_vendor_dashboard(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'password' => bcrypt('secret123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $vendor->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('vendor.dashboard'));

        $this->assertAuthenticatedAs($vendor);
    }

    public function test_super_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'password' => bcrypt('secret123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $admin->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_suspended_vendor_cannot_log_in(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'is_active' => false,
            'password' => bcrypt('secret123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $vendor->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_vendor_cannot_access_admin_dashboard(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_vendor_dashboard(): void
    {
        $this->get(route('vendor.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_editing_an_approved_product_reverts_it_to_pending(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'A bag',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'approved',
        ]);

        $product->update(['price' => 12]);

        $this->assertSame('pending', $product->fresh()->status);
    }

    public function test_editing_a_rejected_product_reverts_it_to_pending(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'A bag',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'rejected',
            'rejection_reason' => 'Image quality too low',
        ]);

        $product->update(['image_path' => 'products/tote-v2.jpg']);

        $product->refresh();
        $this->assertSame('pending', $product->status);
        $this->assertNull($product->rejection_reason);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $vendor = User::factory()->create(['password' => bcrypt('correct-password')]);

        $component = Livewire::test('auth.login')->set('email', $vendor->email);

        for ($i = 0; $i < 5; $i++) {
            $component->set('password', 'wrong-password')->call('login');
        }

        $component->set('password', 'correct-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
