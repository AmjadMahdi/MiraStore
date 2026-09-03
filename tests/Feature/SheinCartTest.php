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

    public function test_cart_list_links_the_cart_name_to_its_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $cart = SheinCart::create([
            'cart_name' => 'Spring Order',
            'customer_phone' => '+9677722222',
            'cart_details' => 'link',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->assertSee(route('admin.carts.show', $cart), false);
    }

    public function test_admin_can_delete_a_cart_from_the_list_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $cart = SheinCart::create([
            'cart_name' => 'Spring Order',
            'customer_phone' => '+9677722222',
            'cart_details' => 'link',
        ]);
        $item = $cart->items()->create(['name' => 'شيء', 'item_date' => now()]);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('deleteCart', $cart->id);

        $this->assertDatabaseMissing('shein_carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('shein_cart_items', ['id' => $item->id]);
    }

    public function test_vendor_cannot_access_cart_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.carts.index'))
            ->assertForbidden();
    }

    public function test_main_cart_combines_codes_from_every_open_cart_only(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        SheinCart::create([
            'cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => "code-1\ncode-2", 'status' => 'open',
        ]);
        SheinCart::create([
            'cart_name' => 'B', 'customer_phone' => '2', 'cart_details' => 'code-3', 'status' => 'open',
        ]);
        SheinCart::create([
            'cart_name' => 'C', 'customer_phone' => '3', 'cart_details' => 'code-4', 'status' => 'ordered',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.shein-main-cart')
            ->assertSee('code-1')
            ->assertSee('code-2')
            ->assertSee('code-3')
            ->assertDontSee('code-4');
    }

    public function test_admin_can_mark_all_open_carts_as_ordered_from_the_main_cart(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $one = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => 'code-1', 'status' => 'open']);
        $two = SheinCart::create(['cart_name' => 'B', 'customer_phone' => '2', 'cart_details' => 'code-2', 'status' => 'open']);
        $untouched = SheinCart::create(['cart_name' => 'C', 'customer_phone' => '3', 'cart_details' => 'code-3', 'status' => 'in_transit']);

        Livewire::actingAs($admin)
            ->test('admin.shein-main-cart')
            ->call('markAllOrdered');

        $this->assertSame('ordered', $one->fresh()->status);
        $this->assertSame('ordered', $two->fresh()->status);
        $this->assertSame('in_transit', $untouched->fresh()->status);
    }

    public function test_vendor_cannot_access_the_main_cart(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.carts.main'))
            ->assertForbidden();
    }

    public function test_vendor_cannot_create_or_view_admin_carts(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        $this->actingAs($vendor)->get(route('admin.carts.create'))->assertForbidden();
        $this->actingAs($vendor)->get(route('admin.carts.show', $cart))->assertForbidden();
    }

    public function test_admin_can_create_a_new_cart_from_scratch(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.cart-form')
            ->set('cart_name', 'طلب هاتفي')
            ->set('description', 'طلب من زبون عبر الهاتف')
            ->set('customer_phone', '+9677700000')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('shein_carts', [
            'cart_name' => 'طلب هاتفي',
            'description' => 'طلب من زبون عبر الهاتف',
            'customer_phone' => '+9677700000',
        ]);
    }

    public function test_admin_can_add_an_item_with_no_description(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->set('itemName', '')
            ->set('itemLink', 'https://shein.com/item/1')
            ->set('itemDate', '2026-09-01')
            ->call('addItem')
            ->assertHasNoErrors();

        $item = $cart->items()->first();
        $this->assertNull($item->name);
        $this->assertSame('https://shein.com/item/1', $item->link);
    }

    public function test_the_link_or_code_is_required_when_adding_an_item(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->set('itemName', 'وصف')
            ->set('itemLink', '')
            ->set('itemDate', '2026-09-01')
            ->call('addItem')
            ->assertHasErrors('itemLink');

        $this->assertSame(0, $cart->items()->count());
    }

    public function test_admin_can_add_and_delete_items_on_a_cart(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        $component = Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->set('itemName', 'فستان أزرق')
            ->set('itemLink', 'https://shein.com/item/1')
            ->set('itemQuantity', '3')
            ->set('itemDate', '2026-09-01')
            ->call('addItem');

        $this->assertDatabaseHas('shein_cart_items', [
            'shein_cart_id' => $cart->id,
            'name' => 'فستان أزرق',
            'link' => 'https://shein.com/item/1',
            'quantity' => 3,
        ]);

        $item = $cart->items()->first();

        $component->call('deleteItem', $item->id);

        $this->assertDatabaseMissing('shein_cart_items', ['id' => $item->id]);
    }

    public function test_admin_can_edit_an_item(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $item = $cart->items()->create(['name' => 'قديم', 'link' => 'https://old.example', 'item_date' => '2026-09-01']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('startEditItem', $item->id)
            ->assertSet('itemName', 'قديم')
            ->set('itemName', 'جديد')
            ->set('itemLink', 'https://new.example')
            ->set('itemDate', '2026-09-05')
            ->call('updateItem')
            ->assertSet('editingItemId', null);

        $item->refresh();
        $this->assertSame('جديد', $item->name);
        $this->assertSame('https://new.example', $item->link);
        $this->assertSame('2026-09-05', $item->item_date->format('Y-m-d'));
    }

    public function test_editing_an_item_is_blocked_when_cart_is_locked(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '', 'is_locked' => true]);
        $item = $cart->items()->create(['name' => 'قديم', 'item_date' => now()]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('startEditItem', $item->id)
            ->assertForbidden();
    }

    public function test_admin_can_edit_cart_name_and_phone(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'قديمة', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('startEditCartDetails')
            ->set('editCartName', 'اسم جديد')
            ->set('editDescription', 'وصف جديد')
            ->set('editCustomerPhone', '+9677711111')
            ->call('updateCartDetails')
            ->assertSet('editingCartDetails', false);

        $cart->refresh();
        $this->assertSame('اسم جديد', $cart->cart_name);
        $this->assertSame('وصف جديد', $cart->description);
        $this->assertSame('+9677711111', $cart->customer_phone);
    }

    public function test_admin_can_delete_a_cart_entirely(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $item = $cart->items()->create(['name' => 'شيء', 'item_date' => now()]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('deleteCart')
            ->assertRedirect(route('admin.carts.index'));

        $this->assertDatabaseMissing('shein_carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('shein_cart_items', ['id' => $item->id]);
    }

    public function test_locking_a_cart_prevents_adding_or_deleting_items(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $item = $cart->items()->create(['name' => 'قديم', 'item_date' => now()]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('toggleLock');

        $this->assertTrue($cart->fresh()->is_locked);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->set('itemName', 'جديد')
            ->set('itemDate', '2026-09-01')
            ->call('addItem')
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('deleteItem', $item->id)
            ->assertForbidden();

        $this->assertDatabaseHas('shein_cart_items', ['id' => $item->id]);
        $this->assertSame(1, $cart->items()->count());
    }

    public function test_unlocking_a_cart_allows_items_again(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '', 'is_locked' => true]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('toggleLock');

        $this->assertFalse($cart->fresh()->is_locked);
    }

    public function test_admin_can_enable_a_public_link_and_it_shows_the_cart(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'رحلتي', 'customer_phone' => '+9677712345', 'cart_details' => '']);
        $cart->items()->create(['name' => 'فستان', 'item_date' => now()]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('togglePublicLink');

        $cart->refresh();
        $this->assertNotNull($cart->public_token);

        $this->get(route('shein.public-cart', $cart->public_token))
            ->assertOk()
            ->assertSee('رحلتي')
            ->assertSee($cart->cart_number)
            ->assertSee('فستان')
            ->assertDontSee('+9677712345');
    }

    public function test_disabling_the_public_link_makes_it_404(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enablePublicLink();
        $token = $cart->public_token;

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('togglePublicLink');

        $this->assertNull($cart->fresh()->public_token);

        $this->get(route('shein.public-cart', $token))->assertNotFound();
    }

    public function test_an_unknown_public_token_is_404(): void
    {
        $this->get(route('shein.public-cart', 'nonexistent-token'))->assertNotFound();
    }

    public function test_customer_phone_must_contain_at_least_one_digit(): void
    {
        Livewire::test('shein.submit-cart')
            ->set('cart_name', 'Test')
            ->set('cart_details', 'link')
            ->set('customer_phone', '+ - -')
            ->call('submit')
            ->assertHasErrors('customer_phone');

        $this->assertDatabaseMissing('shein_carts', ['cart_name' => 'Test']);
    }

    public function test_create_with_unique_number_retries_past_a_cart_number_collision(): void
    {
        SheinCart::create(['cart_name' => 'Taken', 'customer_phone' => '1', 'cart_details' => 'x', 'cart_number' => 'MIRA-11111']);

        $colliding = new class extends SheinCart
        {
            protected $table = 'shein_carts';

            private static int $calls = 0;

            public static function generateCartNumber(): string
            {
                static::$calls++;

                return static::$calls === 1 ? 'MIRA-11111' : 'MIRA-22222';
            }
        };

        $cart = $colliding::createWithUniqueNumber([
            'cart_name' => 'Retry Test', 'customer_phone' => '2', 'cart_details' => 'y',
        ]);

        $this->assertSame('MIRA-22222', $cart->cart_number);
        $this->assertSame(2, SheinCart::count());
    }
}
