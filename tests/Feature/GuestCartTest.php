<?php

namespace Tests\Feature;

use App\Models\SheinCart;
use App\Support\GuestCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_shows_the_cart_marked_as_accepting_submissions(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة الزوار', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        Livewire::test('shein.hero')
            ->assertSee('سلة الزوار');
    }

    public function test_hero_writes_the_submitted_link_directly_into_the_designated_cart(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة الزوار', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        Livewire::test('shein.hero')
            ->set('link', 'https://shein.com/item/1')
            ->set('quantity', '2')
            ->set('specifications', 'المقاس M')
            ->set('customerPhone', '511234567')
            ->call('addToCart')
            ->assertSet('link', '')
            ->assertSet('justAdded', true);

        $this->assertDatabaseHas('shein_cart_items', [
            'shein_cart_id' => $cart->id,
            'link' => 'https://shein.com/item/1',
            'quantity' => 2,
            'name' => 'المقاس M',
            'customer_phone' => '+967 511234567',
        ]);
    }

    public function test_hero_customer_country_code_cannot_be_changed_to_saudi(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة الزوار', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test('shein.hero')->set('customerCountryCode', '+966');
    }

    public function test_hero_defaults_the_country_code_to_yemen(): void
    {
        Livewire::test('shein.hero')
            ->assertSet('customerCountryCode', '+967');
    }

    public function test_hero_submits_to_whichever_cart_is_active_at_submit_time_not_page_load_time(): void
    {
        $oldCart = SheinCart::create(['cart_name' => 'القديمة', 'customer_phone' => '1', 'cart_details' => '']);
        $oldCart->enableSubmissions();

        // The component "loads" while $oldCart is active...
        $component = Livewire::test('shein.hero')
            ->assertSee('القديمة');

        // ...then the admin switches which cart accepts submissions, without
        // the customer ever refreshing the already-open page.
        $newCart = SheinCart::create(['cart_name' => 'الجديدة', 'customer_phone' => '2', 'cart_details' => '']);
        $newCart->enableSubmissions();

        $component
            ->set('link', 'https://shein.com/item/1')
            ->set('customerPhone', '700000000')
            ->call('addToCart');

        $this->assertSame(0, $oldCart->items()->count());
        $this->assertSame(1, $newCart->items()->count());
    }

    public function test_hero_shows_friendly_arabic_error_messages_not_raw_field_names(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة الزوار', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        $component = Livewire::test('shein.hero')
            ->set('link', '')
            ->set('customerPhone', 'no-digits-here')
            ->call('addToCart');

        $errors = $component->errors()->all();

        $this->assertContains('حقل رابط المنتج مطلوب.', $errors);
        $this->assertNotContains('حقل link مطلوب.', $errors);
    }

    public function test_hero_hides_the_button_when_no_cart_accepts_submissions(): void
    {
        Livewire::test('shein.hero')
            ->assertDontSee('ضع رابط المنتج هنا');
    }

    public function test_submitting_a_link_fails_when_no_cart_accepts_submissions(): void
    {
        Livewire::test('shein.hero')
            ->set('link', 'https://shein.com/item/1')
            ->set('customerPhone', '+9677700000')
            ->call('addToCart')
            ->assertNotFound();
    }

    public function test_submitting_a_link_fails_when_the_designated_cart_is_locked(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة الزوار', 'customer_phone' => '1', 'cart_details' => '', 'is_locked' => true]);
        $cart->enableSubmissions();

        Livewire::test('shein.hero')
            ->set('link', 'https://shein.com/item/1')
            ->set('customerPhone', '+9677700000')
            ->call('addToCart')
            ->assertForbidden();
    }

    public function test_enabling_submissions_on_one_cart_disables_it_on_others(): void
    {
        $one = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);
        $two = SheinCart::create(['cart_name' => 'B', 'customer_phone' => '2', 'cart_details' => '']);

        $one->enableSubmissions();
        $this->assertTrue($one->fresh()->accepts_submissions);

        $two->enableSubmissions();

        $this->assertFalse($one->fresh()->accepts_submissions);
        $this->assertTrue($two->fresh()->accepts_submissions);
    }

    public function test_cart_badge_reflects_item_count(): void
    {
        GuestCart::add('ABC123');
        GuestCart::add('DEF456');

        Livewire::test('shein.cart-badge')
            ->assertSee('2');
    }

    public function test_cart_review_lists_items_and_allows_removal(): void
    {
        GuestCart::add('ABC123');
        GuestCart::add('DEF456');

        $items = GuestCart::items();

        $component = Livewire::test('shein.cart-review')
            ->assertSee('ABC123')
            ->assertSee('DEF456');

        $component->call('removeItem', $items[0]['id']);

        $this->assertCount(1, GuestCart::items());
        $this->assertSame('DEF456', GuestCart::items()[0]['code']);
    }

    public function test_confirming_the_cart_creates_a_shein_cart_and_clears_the_session(): void
    {
        GuestCart::add('ABC123');
        GuestCart::add('DEF456');

        Livewire::test('shein.cart-review')
            ->set('cart_name', 'طلبي من Shein')
            ->set('customer_phone', '+9677700000')
            ->call('confirmOrder')
            ->assertSet('confirmedCartNumber', fn ($value) => str_starts_with($value, 'MIRA-'));

        $this->assertDatabaseHas('shein_carts', [
            'cart_name' => 'طلبي من Shein',
            'customer_phone' => '+9677700000',
            'cart_details' => "ABC123 (الكمية: 1)\nDEF456 (الكمية: 1)",
        ]);

        $this->assertCount(0, GuestCart::items());
    }

    public function test_confirming_an_empty_cart_does_nothing(): void
    {
        Livewire::test('shein.cart-review')
            ->set('customer_phone', '+9677700000')
            ->call('confirmOrder')
            ->assertSet('confirmedCartNumber', null);

        $this->assertSame(0, SheinCart::count());
    }

    public function test_cart_page_is_publicly_accessible(): void
    {
        $this->get(route('shein.cart'))->assertOk();
    }
}
