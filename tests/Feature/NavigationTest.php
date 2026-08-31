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
            ->assertSee('Analytics')
            ->assertSee('Products');

        $this->actingAs($vendor)
            ->get(route('home'))
            ->assertDontSee('Analytics');
    }

    public function test_admin_sub_nav_appears_on_admin_pages_only(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('SHEIN carts')
            ->assertSee('Activity');

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertDontSee('SHEIN carts');
    }

    public function test_guest_sees_no_sub_nav(): void
    {
        $this->get(route('home'))
            ->assertDontSee('Overview')
            ->assertSee('Sign in');
    }
}
