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

    public function test_hero_adds_a_code_to_the_session_cart(): void
    {
        Livewire::test('shein.hero')
            ->set('code', 'ABC123')
            ->call('addToCart')
            ->assertSet('code', '')
            ->assertSet('justAdded', true);

        $this->assertCount(1, GuestCart::items());
        $this->assertSame('ABC123', GuestCart::items()[0]['code']);
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
            ->set('customer_phone', '+9677700000')
            ->call('confirmOrder')
            ->assertSet('confirmedCartNumber', fn ($value) => str_starts_with($value, 'MIRA-'));

        $this->assertDatabaseHas('shein_carts', [
            'customer_phone' => '+9677700000',
            'cart_details' => "ABC123\nDEF456",
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
