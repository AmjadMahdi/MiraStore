<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\SheinCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_the_support_whatsapp_link(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('support_whatsapp_link', 'https://wa.me/967777123456')
            ->call('save')
            ->assertSet('justSaved', true);

        $this->assertSame('https://wa.me/967777123456', Setting::get('support_whatsapp_link'));
    }

    public function test_support_whatsapp_link_must_be_a_valid_url(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('support_whatsapp_link', 'not-a-url')
            ->call('save')
            ->assertHasErrors('support_whatsapp_link');
    }

    public function test_supervisor_can_also_access_settings(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_vendor_cannot_access_settings(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_support_whatsapp_link_shows_on_the_vendor_application_status_page(): void
    {
        Setting::set('support_whatsapp_link', 'https://wa.me/967777123456');

        $vendor = User::factory()->create(['role' => 'vendor', 'application_status' => 'pending']);

        $this->actingAs($vendor)
            ->get(route('vendor.status'))
            ->assertSee('https://wa.me/967777123456', false);
    }

    public function test_admin_can_set_multiple_hero_titles_and_the_subtitle_and_button_text(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('hero_titles', ['العنوان الأول', 'العنوان الثاني'])
            ->set('hero_subtitle', 'نص فرعي جديد')
            ->set('hero_button_text', 'اضغط هنا')
            ->call('save')
            ->assertSet('justSaved', true);

        $this->assertSame(['العنوان الأول', 'العنوان الثاني'], Setting::getArray('hero_titles'));
        $this->assertSame('نص فرعي جديد', Setting::get('hero_subtitle'));
        $this->assertSame('اضغط هنا', Setting::get('hero_button_text'));
    }

    public function test_admin_can_add_and_remove_hero_titles(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $component = Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->call('addTitle')
            ->assertCount('hero_titles', 2);

        $component->call('removeTitle', 0)
            ->assertCount('hero_titles', 1);
    }

    public function test_hero_titles_require_at_least_one_non_empty_entry(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('hero_titles', [''])
            ->call('save')
            ->assertHasErrors(['hero_titles.0' => 'required']);
    }

    public function test_hero_shows_configured_titles_subtitle_and_button_text(): void
    {
        Setting::setArray('hero_titles', ['عنوان مخصص أول', 'عنوان مخصص ثاني']);
        Setting::set('hero_subtitle', 'نص فرعي مخصص');
        Setting::set('hero_button_text', 'زر مخصص');

        $cart = SheinCart::create(['cart_name' => 'سلة', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        $html = Livewire::test('shein.hero')->html();

        // The titles are passed to Alpine via @js(), which JSON.parse()-wraps
        // and escapes unicode twice — so check for that encoded form, not the
        // literal text.
        $this->assertStringContainsString(str_replace('\\', '\\\\', trim(json_encode('عنوان مخصص أول'), '"')), $html);
        $this->assertStringContainsString(str_replace('\\', '\\\\', trim(json_encode('عنوان مخصص ثاني'), '"')), $html);
        $this->assertStringContainsString('نص فرعي مخصص', $html);
        $this->assertStringContainsString('زر مخصص', $html);
    }

    public function test_admin_can_upload_multiple_hero_background_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('newHeroBackgroundImages', [
                UploadedFile::fake()->image('one.jpg', 1920, 1080),
                UploadedFile::fake()->image('two.jpg', 1920, 1080),
            ])
            ->call('uploadHeroBackgroundImages')
            ->assertHasNoErrors()
            ->assertCount('hero_background_images', 2);

        $stored = Setting::getArray('hero_background_images');
        $this->assertCount(2, $stored);

        foreach ($stored as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_admin_can_remove_a_hero_background_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('hero/existing.jpg', 'fake-image-content');
        Setting::setArray('hero_background_images', ['hero/existing.jpg']);

        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->call('removeHeroBackgroundImage', 0)
            ->assertCount('hero_background_images', 0);

        $this->assertSame([], Setting::getArray('hero_background_images'));
        Storage::disk('public')->assertMissing('hero/existing.jpg');
    }

    public function test_hero_shows_uploaded_background_images_instead_of_the_default_effect(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('hero/bg.jpg', 'fake-image-content');
        Setting::setArray('hero_background_images', ['hero/bg.jpg']);

        $cart = SheinCart::create(['cart_name' => 'سلة', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        $html = Livewire::test('shein.hero')->html();

        // The image URL is passed to Alpine via @js(), which JSON-encodes
        // and escapes it for safe attribute embedding — so just check the
        // filename made it into the payload rather than matching the exact
        // escaped string.
        $this->assertStringContainsString('bg.jpg', $html);
        $this->assertStringNotContainsString('mountNebulaShader', $html);
    }

    public function test_hero_uses_the_default_effect_when_no_background_images_are_set(): void
    {
        $cart = SheinCart::create(['cart_name' => 'سلة', 'customer_phone' => '1', 'cart_details' => '']);
        $cart->enableSubmissions();

        $html = Livewire::test('shein.hero')->html();

        $this->assertStringContainsString('mountNebulaShader', $html);
    }
}
