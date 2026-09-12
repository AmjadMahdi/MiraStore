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
            ->assertSet('confirmedCartNumber', fn ($value) => str_starts_with($value, 'mira-'));

        $this->assertDatabaseHas('shein_carts', [
            'cart_name' => 'Summer Outfits',
            'customer_phone' => '+9677700000',
            'status' => 'open',
        ]);
    }

    public function test_customer_can_track_their_cart_with_just_their_phone_number(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+967 771111111',
            'cart_details' => 'link',
            'status' => 'in_transit_sa',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id);
    }

    public function test_tracking_matches_a_legacy_phone_stored_without_a_space_or_leading_digit(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Legacy Order',
            'customer_phone' => '+96779950249',
            'cart_details' => 'link',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '79950249')
            ->call('track')
            ->assertHasNoErrors()
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id);
    }

    public function test_tracking_matches_a_phone_recorded_on_one_of_the_carts_items(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Shared Open Cart',
            'customer_phone' => '+967 777123456',
            'cart_details' => 'link',
        ]);
        $cart->items()->create([
            'link' => 'https://shein.com/item/1',
            'item_date' => now(),
            'customer_phone' => '+967 775835076',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '775835076')
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id);
    }

    public function test_tracking_shows_a_picker_when_the_phone_matches_multiple_carts(): void
    {
        $first = SheinCart::create(['cart_name' => 'Order One', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);
        $second = SheinCart::create(['cart_name' => 'Order Two', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);

        $component = Livewire::test('shein.track-cart')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('cart', null)
            ->assertSet('notFound', false);

        $matches = $component->get('matches');
        $this->assertCount(2, $matches);

        $component->call('selectCart', $second->id)
            ->assertSet('cart.id', $second->id)
            ->assertSet('matches', null);
    }

    public function test_cannot_select_a_cart_that_was_not_in_the_matched_results(): void
    {
        $first = SheinCart::create(['cart_name' => 'Order One', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);
        SheinCart::create(['cart_name' => 'Order Two', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);
        $unrelated = SheinCart::create(['cart_name' => 'Someone Else', 'customer_phone' => '+967799999999', 'cart_details' => 'link']);

        $component = Livewire::test('shein.track-cart')
            ->set('customer_phone', '771111111')
            ->call('track');

        $component->call('selectCart', $unrelated->id)->assertStatus(403);
    }

    public function test_tracking_fails_with_mismatched_phone(): void
    {
        SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+967 771111111',
            'cart_details' => 'link',
        ]);

        Livewire::test('shein.track-cart')
            ->set('customer_phone', '799999999')
            ->call('track')
            ->assertSet('notFound', true);
    }

    public function test_tracking_is_rate_limited_to_five_attempts_per_minute(): void
    {
        RateLimiter::clear('shein-tracking:127.0.0.1');

        $component = Livewire::test('shein.track-cart')
            ->set('customer_phone', '770000000');

        for ($i = 0; $i < 5; $i++) {
            $component->call('track')->assertSet('notFound', true);
        }

        $component->call('track')->assertHasErrors('customer_phone');
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

    public function test_admin_can_mark_a_cart_as_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $cart = SheinCart::create([
            'cart_name' => 'Spring Order',
            'customer_phone' => '+9677722222',
            'cart_details' => 'link',
            'status' => 'arrived',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('updateStatus', $cart->id, 'delivered');

        $this->assertSame('delivered', $cart->fresh()->status);
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

    public function test_the_list_page_defaults_to_the_all_filter_showing_every_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $open = SheinCart::create(['cart_name' => 'Open', 'customer_phone' => '1', 'cart_details' => 'x', 'status' => 'open']);
        $arrived = SheinCart::create(['cart_name' => 'Arrived', 'customer_phone' => '2', 'cart_details' => 'y', 'status' => 'arrived']);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->assertSet('statusFilter', 'all')
            ->assertSee($open->cart_name)
            ->assertSee($arrived->cart_name);
    }

    public function test_filtering_by_a_specific_status_narrows_the_list(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $open = SheinCart::create(['cart_name' => 'Open', 'customer_phone' => '1', 'cart_details' => 'x', 'status' => 'open']);
        $arrived = SheinCart::create(['cart_name' => 'Arrived', 'customer_phone' => '2', 'cart_details' => 'y', 'status' => 'arrived']);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->set('statusFilter', 'open')
            ->assertSee($open->cart_name)
            ->assertDontSee($arrived->cart_name);
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

    public function test_admin_can_activate_a_cart_from_the_list_page_with_no_conflict(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'وحيدة', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('toggleAcceptsSubmissions', $cart->id)
            ->assertSet('confirmingActivationForCartId', null);

        $this->assertTrue($cart->fresh()->accepts_submissions);
    }

    public function test_activating_a_cart_from_the_list_page_while_another_is_active_requires_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activeCart = SheinCart::create(['cart_name' => 'النشطة', 'customer_phone' => '1', 'cart_details' => '']);
        $activeCart->enableSubmissions();

        $newCart = SheinCart::create(['cart_name' => 'الجديدة', 'customer_phone' => '2', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('toggleAcceptsSubmissions', $newCart->id)
            ->assertSet('confirmingActivationForCartId', $newCart->id)
            ->assertSeeText('النشطة');

        $this->assertTrue($activeCart->fresh()->accepts_submissions);
        $this->assertFalse($newCart->fresh()->accepts_submissions);
    }

    public function test_activation_from_the_list_page_succeeds_after_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activeCart = SheinCart::create(['cart_name' => 'النشطة', 'customer_phone' => '1', 'cart_details' => '']);
        $activeCart->enableSubmissions();

        $newCart = SheinCart::create(['cart_name' => 'الجديدة', 'customer_phone' => '2', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-management')
            ->call('toggleAcceptsSubmissions', $newCart->id)
            ->call('confirmActivation')
            ->assertSet('confirmingActivationForCartId', null);

        $this->assertFalse($activeCart->fresh()->accepts_submissions);
        $this->assertTrue($newCart->fresh()->accepts_submissions);
    }

    public function test_vendor_cannot_access_cart_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.carts.index'))
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
            ->set('customer_country_code', '+966')
            ->set('customer_phone', '511234567')
            ->call('save')
            ->assertRedirect();

        $this->assertDatabaseHas('shein_carts', [
            'cart_name' => 'طلب هاتفي',
            'description' => 'طلب من زبون عبر الهاتف',
            'customer_phone' => '+966 511234567',
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

    public function test_cart_detail_shows_each_items_customer_phone(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $item = $cart->items()->create([
            'name' => 'فستان أزرق',
            'item_date' => now(),
            'customer_phone' => '+967 771234567',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->assertSee('+967 771234567')
            ->assertSee('https://wa.me/967771234567', false);
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

    public function test_admin_can_set_an_items_customer_phone_when_adding_it(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->set('itemLink', 'https://shein.com/item/1')
            ->set('itemDate', '2026-09-01')
            ->set('itemCustomerPhone', '+967 771234567')
            ->call('addItem');

        $this->assertDatabaseHas('shein_cart_items', [
            'shein_cart_id' => $cart->id,
            'customer_phone' => '+967 771234567',
        ]);
    }

    public function test_admin_can_edit_an_items_customer_phone(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $item = $cart->items()->create([
            'link' => 'https://old.example',
            'item_date' => '2026-09-01',
            'customer_phone' => '+967 771111111',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('startEditItem', $item->id)
            ->assertSet('itemCustomerPhone', '+967 771111111')
            ->set('itemCustomerPhone', '+967 772222222')
            ->call('updateItem');

        $this->assertSame('+967 772222222', $item->fresh()->customer_phone);
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
            ->set('editCustomerCountryCode', '+966')
            ->set('editCustomerPhone', '511234567')
            ->call('updateCartDetails')
            ->assertSet('editingCartDetails', false);

        $cart->refresh();
        $this->assertSame('اسم جديد', $cart->cart_name);
        $this->assertSame('وصف جديد', $cart->description);
        $this->assertSame('+966 511234567', $cart->customer_phone);
    }

    public function test_editing_cart_phone_correctly_parses_the_existing_country_code(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'قديمة', 'customer_phone' => '+966 511234567', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('startEditCartDetails')
            ->assertSet('editCustomerCountryCode', '+966')
            ->assertSet('editCustomerPhone', '511234567');
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

    public function test_admin_can_toggle_accepts_submissions_from_the_cart_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('toggleAcceptsSubmissions');

        $this->assertTrue($cart->fresh()->accepts_submissions);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('toggleAcceptsSubmissions');

        $this->assertFalse($cart->fresh()->accepts_submissions);
    }

    public function test_activating_a_cart_while_another_is_active_requires_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activeCart = SheinCart::create(['cart_name' => 'النشطة حالياً', 'customer_phone' => '1', 'cart_details' => '']);
        $activeCart->enableSubmissions();

        $newCart = SheinCart::create(['cart_name' => 'الجديدة', 'customer_phone' => '2', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $newCart])
            ->call('toggleAcceptsSubmissions')
            ->assertSet('confirmingActivation', true);

        // Clicking the button alone must not have switched anything yet.
        $this->assertTrue($activeCart->fresh()->accepts_submissions);
        $this->assertFalse($newCart->fresh()->accepts_submissions);
    }

    public function test_activation_confirmation_shows_the_currently_active_carts_name(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activeCart = SheinCart::create(['cart_name' => 'سلة العيد', 'customer_phone' => '1', 'cart_details' => '']);
        $activeCart->enableSubmissions();

        $newCart = SheinCart::create(['cart_name' => 'سلة الجديدة', 'customer_phone' => '2', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $newCart])
            ->call('toggleAcceptsSubmissions')
            ->assertSeeText('سلة العيد');
    }

    public function test_activation_succeeds_after_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $activeCart = SheinCart::create(['cart_name' => 'النشطة', 'customer_phone' => '1', 'cart_details' => '']);
        $activeCart->enableSubmissions();

        $newCart = SheinCart::create(['cart_name' => 'الجديدة', 'customer_phone' => '2', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $newCart])
            ->call('toggleAcceptsSubmissions')
            ->call('confirmActivation')
            ->assertSet('confirmingActivation', false);

        $this->assertFalse($activeCart->fresh()->accepts_submissions);
        $this->assertTrue($newCart->fresh()->accepts_submissions);
    }

    public function test_activating_a_cart_when_no_other_cart_is_active_needs_no_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $cart = SheinCart::create(['cart_name' => 'وحيدة', 'customer_phone' => '1', 'cart_details' => '']);

        Livewire::actingAs($admin)
            ->test('admin.cart-detail', ['cart' => $cart])
            ->call('toggleAcceptsSubmissions')
            ->assertSet('confirmingActivation', false);

        $this->assertTrue($cart->fresh()->accepts_submissions);
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
        SheinCart::create(['cart_name' => 'Taken', 'customer_phone' => '1', 'cart_details' => 'x', 'cart_number' => 'mira-11111']);

        $colliding = new class extends SheinCart
        {
            protected $table = 'shein_carts';

            private static int $calls = 0;

            public static function generateCartNumber(): string
            {
                static::$calls++;

                return static::$calls === 1 ? 'mira-11111' : 'mira-22222';
            }
        };

        $cart = $colliding::createWithUniqueNumber([
            'cart_name' => 'Retry Test', 'customer_phone' => '2', 'cart_details' => 'y',
        ]);

        $this->assertSame('mira-22222', $cart->cart_number);
        $this->assertSame(2, SheinCart::count());
    }

    public function test_cart_numbers_are_sequential_starting_at_mira_10(): void
    {
        $first = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => 'x']);
        $second = SheinCart::create(['cart_name' => 'B', 'customer_phone' => '2', 'cart_details' => 'y']);
        $third = SheinCart::create(['cart_name' => 'C', 'customer_phone' => '3', 'cart_details' => 'z']);

        $this->assertSame('mira-10', $first->cart_number);
        $this->assertSame('mira-11', $second->cart_number);
        $this->assertSame('mira-12', $third->cart_number);
    }

    public function test_order_status_badge_shows_status_and_view_link_when_public_link_is_enabled(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+967 771111111',
            'cart_details' => 'link',
            'status' => 'in_transit_sa',
        ]);
        $cart->enablePublicLink();

        Livewire::test('shein.order-status')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id)
            ->assertSee('الطلب في الطريق إلى السعودية')
            ->assertSee(route('shein.public-cart', $cart->public_token));
    }

    public function test_order_status_badge_hides_view_link_when_public_link_is_disabled(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+967 771111111',
            'cart_details' => 'link',
            'status' => 'ordered',
        ]);

        Livewire::test('shein.order-status')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSee('تم استلام الطلبات')
            ->assertDontSee(route('shein.public-cart', 'anything-that-should-not-render'));
    }

    public function test_order_status_badge_matches_a_phone_recorded_on_one_of_the_carts_items(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Shared Open Cart',
            'customer_phone' => '+967 777123456',
            'cart_details' => 'link',
        ]);
        $cart->items()->create([
            'link' => 'https://shein.com/item/1',
            'item_date' => now(),
            'customer_phone' => '+967 775835076',
        ]);

        Livewire::test('shein.order-status')
            ->set('customer_phone', '775835076')
            ->call('track')
            ->assertSet('notFound', false)
            ->assertSet('cart.id', $cart->id);
    }

    public function test_order_status_badge_reports_not_found_for_unknown_cart(): void
    {
        Livewire::test('shein.order-status')
            ->set('customer_phone', '799999999')
            ->call('track')
            ->assertSet('notFound', true);
    }

    public function test_order_status_badge_reset_clears_the_tracked_cart(): void
    {
        $cart = SheinCart::create([
            'cart_name' => 'Winter Gear',
            'customer_phone' => '+967 771111111',
            'cart_details' => 'link',
        ]);

        Livewire::test('shein.order-status')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('cart.id', $cart->id)
            ->call('reset_')
            ->assertSet('cart', null)
            ->assertSet('customer_phone', '');
    }

    public function test_order_status_badge_shows_a_picker_when_the_phone_matches_multiple_carts(): void
    {
        SheinCart::create(['cart_name' => 'Order One', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);
        $second = SheinCart::create(['cart_name' => 'Order Two', 'customer_phone' => '+967771111111', 'cart_details' => 'link']);

        Livewire::test('shein.order-status')
            ->set('customer_phone', '771111111')
            ->call('track')
            ->assertSet('cart', null)
            ->assertSee('Order One')
            ->assertSee('Order Two')
            ->call('selectCart', $second->id)
            ->assertSet('cart.id', $second->id)
            ->assertSet('matches', null);
    }
}
