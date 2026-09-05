<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
