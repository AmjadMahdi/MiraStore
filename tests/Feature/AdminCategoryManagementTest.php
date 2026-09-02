<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_category(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Livewire::actingAs($admin)
            ->test('admin.category-management')
            ->set('name', 'أحذية')
            ->call('addCategory')
            ->assertSet('name', '')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'أحذية']);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Category::create(['name' => 'أحذية']);

        Livewire::actingAs($admin)
            ->test('admin.category-management')
            ->set('name', 'أحذية')
            ->call('addCategory')
            ->assertHasErrors('name');

        $this->assertSame(1, Category::where('name', 'أحذية')->count());
    }

    public function test_admin_can_edit_a_category(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $category = Category::create(['name' => 'قديم']);

        Livewire::actingAs($admin)
            ->test('admin.category-management')
            ->call('startEdit', $category->id)
            ->assertSet('editingName', 'قديم')
            ->set('editingName', 'جديد')
            ->call('updateCategory')
            ->assertSet('editingId', null);

        $this->assertSame('جديد', $category->fresh()->name);
    }

    public function test_editing_a_category_to_an_existing_name_fails(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Category::create(['name' => 'موجودة']);
        $category = Category::create(['name' => 'قابلة للتعديل']);

        Livewire::actingAs($admin)
            ->test('admin.category-management')
            ->call('startEdit', $category->id)
            ->set('editingName', 'موجودة')
            ->call('updateCategory')
            ->assertHasErrors('editingName');

        $this->assertSame('قابلة للتعديل', $category->fresh()->name);
    }

    public function test_admin_can_delete_a_category_and_its_products_lose_the_category(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $category = Category::create(['name' => 'للحذف']);

        $vendor = User::factory()->create(['role' => 'vendor']);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'description' => 'd',
            'price' => 10,
            'image_path' => 'products/p.jpg',
            'status' => 'approved',
        ]);

        Livewire::actingAs($admin)
            ->test('admin.category-management')
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertNull($product->fresh()->category_id);
    }

    public function test_vendor_cannot_access_category_management(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }
}
