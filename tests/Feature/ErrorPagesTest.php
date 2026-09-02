<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    public function test_404_page_is_branded(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('MiraStore');
    }

    public function test_403_page_is_branded_and_shows_the_custom_message(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('MiraStore');
    }

    public function test_suspended_vendor_sees_the_custom_suspension_message(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'is_active' => false]);

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertForbidden()
            ->assertSee('تم إيقاف حسابك');
    }
}
