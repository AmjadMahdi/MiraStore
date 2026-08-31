<?php

namespace Tests\Feature;

use App\Models\SheinCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SheinCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_a_cart_and_receives_a_cart_number(): void
    {
        Livewire::test('shein.submit-cart')
            ->set('cart_name', 'Summer Outfits')
            ->set('cart_details', 'https://shein.com/cart/abc123')
            ->set('customer_phone', '+9677700000')
            ->call('submit')
            ->assertSet('confirmedCartNumber', fn ($value) => str_starts_with($value, 'MIRA-'));

        $this->assertDatabaseHas('shein_carts', [
            'cart_name' => 'Summer Outfits',
            'customer_phone' => '+9677700000',
            'status' => 'open',
        ]);
    }

    public function test_customer_can_track_their_cart_with_matching_phone_and_number(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+9677711111',
            'cart_details' => 'link',
            'status' => 'in_transit',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '+9677711111')
            ->set('cart_number', $cart->cart_number)
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id);
    }

    public function test_tracking_fails_with_mismatched_phone(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+9677711111',
            'cart_details' => 'link',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '+9677799999')
            ->set('cart_number', $cart->cart_number)
            ->call('track')
            ->assertSet('notFound', true);
    }

    public function test_tracking_is_rate_limited_to_five_attempts_per_minute(): void
    {
        RateLimiter::clear('shein-tracking:127.0.0.1');

        $component = Livewire::test('shein.track-cart')
            ->set('customer_phone', '+9677700000')
            ->set('cart_number', 'MIRA-00000');

        for ($i = 0; $i < 5; $i++) {
            $component->call('track')->assertSet('notFound', true);
        }

        $component->call('track')->assertHasErrors('cart_number');
    }

    public function test_admin_can_update_cart_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $cart = SheinCart::create([
            'cart_name' => 'Spring Order',
            'customer_phone' => '+9677722222',
            'cart_details' => 'link',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('updateStatus', $cart->id, 'ordered');

        $this->assertSame('ordered', $cart->fresh()->status);
    }

    public function test_vendor_cannot_access_cart_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.carts.index'))
            ->assertForbidden();
    }
}
