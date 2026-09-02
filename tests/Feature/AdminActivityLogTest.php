<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_edits_by_admin_appear_in_the_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $product->update(['status' => 'approved']);

        Livewire::actingAs($admin)
            ->test('admin.activity-log')
            ->assertSee('Product')
            ->assertSee('عدّل');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'causer_id' => $admin->id,
            'event' => 'updated',
        ]);
    }

    public function test_vendor_suspension_appears_in_the_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor', 'is_active' => true]);

        $this->actingAs($admin);
        $vendor->update(['is_active' => false]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $vendor->id,
            'causer_id' => $admin->id,
            'event' => 'updated',
        ]);
    }

    public function test_event_filter_narrows_the_list(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $vendor = User::factory()->create(['role' => 'vendor']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Filtered Item',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/x.jpg',
        ]);
        $product->delete();

        Livewire::actingAs($admin)
            ->test('admin.activity-log')
            ->set('eventFilter', 'deleted')
            ->assertSee('حذف');
    }

    public function test_vendor_cannot_access_activity_log(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.activity.index'))
            ->assertForbidden();
    }
}
