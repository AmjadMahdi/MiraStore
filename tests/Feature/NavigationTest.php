<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_sub_nav_appears_on_vendor_pages_only(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertSee('التحليلات')
            ->assertSee('المنتجات');

        $this->actingAs($vendor)
            ->get(route('home'))
            ->assertDontSee('التحليلات');
    }

    public function test_admin_sub_nav_appears_on_admin_pages_only(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('سلال شي إن')
            ->assertSee('سجل النشاط');

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertDontSee('سلال شي إن');
    }

    public function test_guest_sees_no_sub_nav(): void
    {
        $this->get(route('home'))
            ->assertDontSee('نظرة عامة')
            ->assertSee('تسجيل الدخول');
    }

    public function test_logout_button_appears_in_nav_when_authenticated(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('home'))
            ->assertSee('تسجيل الخروج');
    }

    public function test_logout_button_does_not_appear_for_guests(): void
    {
        $this->get(route('home'))
            ->assertDontSee('تسجيل الخروج');
    }

    public function test_logout_button_logs_the_user_out(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
