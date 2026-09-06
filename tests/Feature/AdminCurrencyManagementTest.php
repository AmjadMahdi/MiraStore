<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCurrencyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_three_default_currencies_exist_after_migrating(): void
    {
        $this->assertDatabaseHas('currencies', ['code' => 'YER', 'name' => 'الريال اليمني']);
        $this->assertDatabaseHas('currencies', ['code' => 'SAR', 'name' => 'الريال السعودي']);
        $this->assertDatabaseHas('currencies', ['code' => 'USD', 'name' => 'الدولار الأمريكي']);
    }

    public function test_admin_can_edit_a_currency(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $currency = Currency::where('code', 'USD')->first();

        Livewire::actingAs($admin)
            ->test('admin.currency-management')
            ->call('startEdit', $currency->id)
            ->set('editingName', 'دولار أمريكي')
            ->set('editingCode', 'USD')
            ->set('editingSymbol', 'US$')
            ->call('updateCurrency');

        $currency->refresh();
        $this->assertSame('دولار أمريكي', $currency->name);
        $this->assertSame('US$', $currency->symbol);
    }

    public function test_admin_can_toggle_a_currency_enabled_state(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $currency = Currency::where('code', 'SAR')->first();

        Livewire::actingAs($admin)
            ->test('admin.currency-management')
            ->call('toggleEnabled', $currency->id);

        $this->assertFalse($currency->fresh()->is_enabled);
    }

    public function test_admin_can_delete_a_currency_not_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $currency = Currency::where('code', 'USD')->first();

        Livewire::actingAs($admin)
            ->test('admin.currency-management')
            ->call('deleteCurrency', $currency->id);

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_admin_cannot_delete_a_currency_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();
        $currency = Currency::where('code', 'YER')->first();

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'name' => 'Item',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/x.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.currency-management')
            ->call('deleteCurrency', $currency->id)
            ->assertSet('deleteBlockedMessage', fn ($message) => str_contains($message, $currency->name));

        $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
    }

    public function test_currency_management_appears_on_the_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSeeText('العملات')
            ->assertSeeText('الريال اليمني');
    }

    public function test_supervisor_can_manage_currencies(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_vendor_cannot_access_currency_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_vendor_can_create_a_product_priced_in_a_chosen_currency(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();
        $sar = Currency::where('code', 'SAR')->first();

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Riyal Item')
            ->set('category_id', (string) $category->id)
            ->set('currency_id', (string) $sar->id)
            ->set('description', 'desc')
            ->set('price', '50')
            ->set('newImages', [\Illuminate\Http\UploadedFile::fake()->image('x.jpg')])
            ->call('save');

        $product = Product::where('name', 'Riyal Item')->first();
        $this->assertSame($sar->id, $product->currency_id);
        $this->assertSame('ر.س', $product->currency->symbol);
    }

    public function test_product_creation_requires_a_currency(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        Livewire::actingAs($vendor)
            ->test('vendor.product-form')
            ->set('name', 'Item')
            ->set('category_id', (string) $category->id)
            ->set('description', 'desc')
            ->set('price', '10')
            ->set('newImages', [\Illuminate\Http\UploadedFile::fake()->image('x.jpg')])
            ->call('save')
            ->assertHasErrors('currency_id');
    }

    public function test_disabled_currency_still_shows_on_a_product_that_already_uses_it(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();
        $usd = Currency::where('code', 'USD')->first();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'currency_id' => $usd->id,
            'name' => 'Old Item',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/x.jpg',
        ]);

        $usd->update(['is_enabled' => false]);

        Livewire::actingAs($vendor)
            ->test('vendor.product-form', ['product' => $product])
            ->assertSee($usd->name);
    }
}
