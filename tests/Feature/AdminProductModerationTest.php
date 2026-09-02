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

    protected function approvedProduct(): Product
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        return Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Cute Tote',
            'description' => 'desc',
            'price' => 10,
            'image_path' => 'products/tote.jpg',
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_open_and_close_a_product_preview(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('openPreview', $product->id)
            ->assertSet('previewingProductId', $product->id)
            ->assertSee($product->description)
            ->call('closePreview')
            ->assertSet('previewingProductId', null);
    }

    public function test_admin_can_approve_from_within_the_preview(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('openPreview', $product->id)
            ->call('approve', $product->id)
            ->assertSet('previewingProductId', null);

        $this->assertSame('approved', $product->fresh()->status);
    }

    public function test_admin_can_reject_from_within_the_preview(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('openPreview', $product->id)
            ->call('startReject', $product->id)
            ->set('rejectionReason', 'Image quality too low')
            ->call('confirmReject')
            ->assertSet('previewingProductId', null);

        $product->refresh();
        $this->assertSame('rejected', $product->status);
        $this->assertSame('Image quality too low', $product->rejection_reason);
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

    public function test_admin_can_reject_an_already_approved_product(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->approvedProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('startReject', $product->id)
            ->set('rejectionReason', 'No longer meets our standards')
            ->call('confirmReject');

        $product->refresh();
        $this->assertSame('rejected', $product->status);
        $this->assertSame('No longer meets our standards', $product->rejection_reason);
    }

    public function test_admin_can_delete_a_product_of_any_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $approved = $this->approvedProduct();
        $pending = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('deleteProduct', $approved->id);

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('deleteProduct', $pending->id);

        $this->assertSoftDeleted($approved);
        $this->assertSoftDeleted($pending);
    }

    public function test_deleting_from_within_the_preview_closes_it(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->approvedProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('openPreview', $product->id)
            ->call('deleteProduct', $product->id)
            ->assertSet('previewingProductId', null);

        $this->assertSoftDeleted($product);
    }

    public function test_vendor_cannot_access_moderation_queue(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_can_edit_any_product_without_reverting_its_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->approvedProduct();
        $category = \App\Models\Category::factory()->create();

        \Livewire\Livewire::actingAs($admin)
            ->test('vendor.product-form', ['product' => $product])
            ->set('name', 'Renamed Tote')
            ->set('category_id', (string) $category->id)
            ->set('description', $product->description)
            ->set('price', '15')
            ->set('stock_status', 'in_stock')
            ->call('save');

        $product->refresh();
        $this->assertSame('Renamed Tote', $product->name);
        $this->assertSame('approved', $product->status);
    }

    public function test_vendor_cannot_edit_another_vendors_product(): void
    {
        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $product = $this->pendingProduct();

        \Livewire\Livewire::actingAs($otherVendor)
            ->test('vendor.product-form', ['product' => $product])
            ->assertForbidden();
    }

    public function test_admin_can_select_all_and_bulk_approve(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $one = $this->pendingProduct();
        $two = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('toggleSelectAll')
            ->assertSet('selected', [$one->id, $two->id])
            ->call('bulkApprove')
            ->assertSet('selected', []);

        $this->assertSame('approved', $one->fresh()->status);
        $this->assertSame('approved', $two->fresh()->status);
    }

    public function test_admin_can_bulk_reject_selected_products_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $one = $this->pendingProduct();
        $two = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->set('selected', [$one->id, $two->id])
            ->call('startBulkReject')
            ->set('bulkRejectionReason', 'Batch quality issue')
            ->call('confirmBulkReject')
            ->assertSet('selected', [])
            ->assertSet('bulkRejecting', false);

        $one->refresh();
        $two->refresh();
        $this->assertSame('rejected', $one->status);
        $this->assertSame('Batch quality issue', $one->rejection_reason);
        $this->assertSame('rejected', $two->status);
    }

    public function test_bulk_rejection_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->set('selected', [$product->id])
            ->call('startBulkReject')
            ->set('bulkRejectionReason', '')
            ->call('confirmBulkReject')
            ->assertHasErrors('bulkRejectionReason');

        $this->assertSame('pending', $product->fresh()->status);
    }

    public function test_toggle_select_all_deselects_when_all_already_selected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $one = $this->pendingProduct();
        $two = $this->pendingProduct();

        Livewire::actingAs($admin)
            ->test('admin.product-moderation')
            ->call('toggleSelectAll')
            ->assertSet('selected', [$one->id, $two->id])
            ->call('toggleSelectAll')
            ->assertSet('selected', []);
    }
}
