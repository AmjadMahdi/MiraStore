<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_registration_starts_pending_and_cannot_reach_the_vendor_dashboard(): void
    {
        Livewire::test('auth.register')
            ->set('name', 'Amina')
            ->set('store_name', "Amina's Bakes")
            ->set('email', 'amina@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $vendor = User::where('email', 'amina@example.com')->first();
        $this->assertSame('pending', $vendor->application_status);

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertRedirect(route('vendor.status'));

        $this->actingAs($vendor)
            ->get(route('vendor.status'))
            ->assertOk()
            ->assertSeeText('شكراً لانضمامك')
            ->assertSeeText('مجاني');
    }

    public function test_admin_can_approve_a_pending_vendor_who_can_then_reach_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'application_status' => 'pending']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('approveApplication', $vendor->id);

        $vendor->refresh();
        $this->assertSame('approved', $vendor->application_status);

        $this->actingAs($vendor)->get(route('vendor.dashboard'))->assertOk();
    }

    public function test_admin_can_reject_a_pending_vendor_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'application_status' => 'pending']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('startRejectApplication', $vendor->id)
            ->set('rejectionReason', 'بيانات غير مكتملة')
            ->call('confirmRejectApplication');

        $vendor->refresh();
        $this->assertSame('rejected', $vendor->application_status);
        $this->assertSame('بيانات غير مكتملة', $vendor->application_rejection_reason);

        $this->actingAs($vendor)
            ->get(route('vendor.dashboard'))
            ->assertRedirect(route('vendor.status'));

        $this->actingAs($vendor)
            ->get(route('vendor.status'))
            ->assertOk()
            ->assertSeeText('بيانات غير مكتملة');
    }

    public function test_rejection_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'application_status' => 'pending']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('startRejectApplication', $vendor->id)
            ->set('rejectionReason', '')
            ->call('confirmRejectApplication')
            ->assertHasErrors('rejectionReason');

        $this->assertSame('pending', $vendor->fresh()->application_status);
    }

    public function test_admin_can_re_approve_a_previously_rejected_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'application_status' => 'rejected',
            'application_rejection_reason' => 'قديم',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->call('approveApplication', $vendor->id);

        $vendor->refresh();
        $this->assertSame('approved', $vendor->application_status);
        $this->assertNull($vendor->application_rejection_reason);
    }

    public function test_vendor_management_shows_a_pending_count_badge_and_can_filter_to_it(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'vendor', 'application_status' => 'pending', 'store_name' => 'Pending Shop']);
        User::factory()->create(['role' => 'vendor', 'application_status' => 'approved', 'store_name' => 'Approved Shop']);

        $component = Livewire::actingAs($admin)
            ->test('admin.vendor-management')
            ->assertSee('Pending Shop')
            ->assertSee('Approved Shop');

        $component->set('statusFilter', 'pending')
            ->assertSee('Pending Shop')
            ->assertDontSee('Approved Shop');
    }

    public function test_a_pending_vendor_can_log_in_but_lands_on_the_status_page(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'application_status' => 'pending', 'password' => bcrypt('secret123')]);

        Livewire::test('auth.login')
            ->set('email', $vendor->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('vendor.status'));

        $this->assertAuthenticatedAs($vendor);
    }

    public function test_a_rejected_vendor_can_log_in_but_lands_on_the_status_page(): void
    {
        $vendor = User::factory()->create([
            'role' => 'vendor', 'application_status' => 'rejected',
            'application_rejection_reason' => 'صور غير واضحة', 'password' => bcrypt('secret123'),
        ]);

        Livewire::test('auth.login')
            ->set('email', $vendor->email)
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect(route('vendor.status'));

        $this->actingAs($vendor)
            ->get(route('vendor.status'))
            ->assertSeeText('صور غير واضحة');
    }

    public function test_admin_created_vendors_are_approved_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.vendor-form')
            ->set('name', 'Omar')
            ->set('store_name', "Omar's Shop")
            ->set('email', 'omar@example.com')
            ->set('whatsapp_number', '+9677700000')
            ->set('password', 'password123')
            ->call('save');

        $this->assertSame('approved', User::where('email', 'omar@example.com')->first()->application_status);
    }
}
