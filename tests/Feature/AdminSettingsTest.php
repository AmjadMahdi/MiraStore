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

    public function test_admin_can_set_the_default_cart_name(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.settings-form')
            ->set('defaultCartName', 'طلب من متجرنا')
            ->call('save')
            ->assertSet('saved', true);

        $this->assertSame('طلب من متجرنا', Setting::get('default_cart_name'));
    }

    public function test_vendor_cannot_access_settings(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }
}
