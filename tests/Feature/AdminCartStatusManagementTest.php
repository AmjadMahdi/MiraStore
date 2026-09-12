<?php

namespace Tests\Feature;

use App\Models\SheinCart;
use App\Models\SheinCartStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCartStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $maxOrderBefore = (int) SheinCartStatus::max('display_order');

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->set('label', 'قيد الفحص')
            ->call('addStatus')
            ->assertSet('label', '')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shein_cart_statuses', [
            'label' => 'قيد الفحص',
            'display_order' => $maxOrderBefore + 1,
        ]);
    }

    public function test_status_label_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $existingLabel = SheinCartStatus::first()->label;

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->set('label', $existingLabel)
            ->call('addStatus')
            ->assertHasErrors('label');

        $this->assertSame(1, SheinCartStatus::where('label', $existingLabel)->count());
    }

    public function test_admin_can_edit_a_status_label(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $status = SheinCartStatus::create(['key' => 'custom_edit_test', 'label' => 'قديم', 'display_order' => 99]);

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('startEdit', $status->id)
            ->assertSet('editingLabel', 'قديم')
            ->set('editingLabel', 'جديد')
            ->call('updateStatus')
            ->assertSet('editingId', null);

        $this->assertSame('جديد', $status->fresh()->label);
        $this->assertSame('custom_edit_test', $status->fresh()->key);
    }

    public function test_admin_can_delete_an_unused_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $status = SheinCartStatus::create(['key' => 'unused_test_status', 'label' => 'غير مستخدمة', 'display_order' => 99]);

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('deleteStatus', $status->id);

        $this->assertDatabaseMissing('shein_cart_statuses', ['id' => $status->id]);
    }

    public function test_admin_cannot_delete_a_status_that_carts_are_using(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $status = SheinCartStatus::create(['key' => 'custom_in_use_status', 'label' => 'حالة مستخدمة', 'display_order' => 99]);
        SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '', 'status' => 'custom_in_use_status']);

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('deleteStatus', $status->id)
            ->assertSet('deleteBlockedMessage', fn ($value) => str_contains($value, 'حالة مستخدمة'));

        $this->assertDatabaseHas('shein_cart_statuses', ['id' => $status->id]);
    }

    public function test_admin_cannot_delete_the_last_remaining_status(): void
    {
        SheinCartStatus::query()->delete();
        $status = SheinCartStatus::create(['key' => 'only_one', 'label' => 'الوحيدة', 'display_order' => 0]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('deleteStatus', $status->id)
            ->assertSet('deleteBlockedMessage', 'يجب أن تبقى حالة واحدة على الأقل.');

        $this->assertDatabaseHas('shein_cart_statuses', ['id' => $status->id]);
    }

    public function test_admin_can_reorder_statuses_by_dragging(): void
    {
        SheinCartStatus::query()->delete();
        $first = SheinCartStatus::create(['key' => 'a', 'label' => 'الأولى', 'display_order' => 0]);
        $second = SheinCartStatus::create(['key' => 'b', 'label' => 'الثانية', 'display_order' => 1]);
        $third = SheinCartStatus::create(['key' => 'c', 'label' => 'الثالثة', 'display_order' => 2]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('moveStatus', 0, 2);

        $ordered = SheinCartStatus::orderBy('display_order')->pluck('id')->all();
        $this->assertSame([$second->id, $third->id, $first->id], $ordered);
    }

    public function test_reordering_statuses_changes_the_pipeline_customers_see(): void
    {
        SheinCartStatus::query()->delete();
        SheinCartStatus::create(['key' => 'open', 'label' => 'مفتوحة', 'display_order' => 0]);
        SheinCartStatus::create(['key' => 'ordered', 'label' => 'تم الطلب', 'display_order' => 1]);

        $this->assertSame(['open', 'ordered'], SheinCart::statuses());

        $admin = User::factory()->create(['role' => 'super_admin']);
        Livewire::actingAs($admin)
            ->test('admin.cart-status-management')
            ->call('moveStatus', 0, 1);

        $this->assertSame(['ordered', 'open'], SheinCart::statuses());
    }

    public function test_vendor_cannot_access_cart_status_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_new_carts_get_the_first_ordered_status_by_default(): void
    {
        SheinCartStatus::query()->delete();
        SheinCartStatus::create(['key' => 'triage', 'label' => 'قيد المراجعة', 'display_order' => 0]);
        SheinCartStatus::create(['key' => 'open', 'label' => 'مفتوحة', 'display_order' => 1]);

        $cart = SheinCart::create(['cart_name' => 'A', 'customer_phone' => '1', 'cart_details' => '']);

        $this->assertSame('triage', $cart->status);
    }
}
