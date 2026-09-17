<?php

namespace Tests\Feature;

use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSiteStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_a_page_records_a_site_visit(): void
    {
        $this->get('/');

        $this->assertDatabaseHas('site_visits', ['path' => '/']);
    }

    public function test_robots_and_sitemap_requests_are_not_tracked_as_visits(): void
    {
        $this->get('/robots.txt');
        $this->get('/sitemap.xml');

        $this->assertSame(0, SiteVisit::count());
    }

    public function test_admin_can_view_total_visits_for_the_default_seven_day_range(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        SiteVisit::create(['path' => '/', 'ip_address' => '1.1.1.1', 'created_at' => now()]);
        SiteVisit::create(['path' => '/shein', 'ip_address' => '1.1.1.1', 'created_at' => now()->subDays(3)]);
        SiteVisit::create(['path' => '/old', 'ip_address' => '1.1.1.1', 'created_at' => now()->subDays(20)]);

        Livewire::actingAs($admin)
            ->test('admin.site-statistics')
            ->assertSee('إجمالي الزيارات')
            ->assertSee('>2</p>', false);
    }

    public function test_admin_can_switch_to_the_thirty_day_preset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        SiteVisit::create(['path' => '/', 'ip_address' => '1.1.1.1', 'created_at' => now()]);
        SiteVisit::create(['path' => '/old', 'ip_address' => '1.1.1.1', 'created_at' => now()->subDays(20)]);

        Livewire::actingAs($admin)
            ->test('admin.site-statistics')
            ->call('applyPreset', '30days')
            ->assertSee('>2</p>', false);
    }

    public function test_admin_can_set_a_custom_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        SiteVisit::create(['path' => '/', 'ip_address' => '1.1.1.1', 'created_at' => '2026-01-10 10:00:00']);
        SiteVisit::create(['path' => '/', 'ip_address' => '1.1.1.1', 'created_at' => '2026-01-15 10:00:00']);
        SiteVisit::create(['path' => '/', 'ip_address' => '1.1.1.1', 'created_at' => '2026-02-01 10:00:00']);

        Livewire::actingAs($admin)
            ->test('admin.site-statistics')
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-01-31')
            ->assertSee('>2</p>', false);
    }

    public function test_vendor_cannot_access_site_statistics(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.statistics.index'))
            ->assertForbidden();
    }
}
