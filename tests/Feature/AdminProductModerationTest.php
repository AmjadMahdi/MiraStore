<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function pendingProduct(): Product
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        return Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_a_pending_product(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('approve', $product->id);

        $this->assertSame('approved', $product->fresh()->status);
    }

    public function test_admin_can_reject_a_pending_product_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('startReject', $product->id)
            ->set('rejectionReason', 'Image quality too low')
            ->call('confirmReject');

        $product->refresh();
        $this->assertSame('rejected', $product->status);
        $this->assertSame('Image quality too low', $product->rejection_reason);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('startReject', $product->id)
            ->set('rejectionReason', '')
            ->call('confirmReject')
            ->assertHasErrors('rejectionReason');

        $this->assertSame('pending', $product->fresh()->status);
    }

    public function test_vendor_cannot_access_moderation_queue(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }
}
