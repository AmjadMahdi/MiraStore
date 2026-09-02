<?php

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editingName = '';

    public function addCategory(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')]]);

        Category::create(['name' => $this->name]);

        $this->name = '';
    }

    public function startEdit(Category $category): void
    {
        $this->editingId = $category->id;
        $this->editingName = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingName = '';
    }

    public function updateCategory(): void
    {
        $category = Category::findOrFail($this->editingId);

        $this->validate([
            'editingName' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update(['name' => $this->editingName]);

        $this->cancelEdit();
    }

    public function deleteCategory(Category $category): void
    {
        // products.category_id has a DB-level nullOnDelete FK, so linked
        // products simply lose their category rather than being blocked/deleted.
        $category->delete();
    }

    public function with(): array
    {
        return [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">الفئات</h1>

    <form wire:submit="addCategory" class="mt-6 flex items-start gap-2">
        <div class="flex-1">
            <input
                type="text"
                wire:model="name"
                placeholder="اسم الفئة الجديدة"
                class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
            >
            @error('name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="addCategory"
            class="flex-shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            + إضافة
        </button>
    </form>

    <div class="mt-6 space-y-2">
        @forelse ($categories as $category)
            <div class="rounded-lg border border-line-medium p-3 transition hover:bg-surface">
                @if ($editingId === $category->id)
                    <form wire:submit="updateCategory" class="flex items-start gap-2">
                        <div class="flex-1">
                            <input
                                type="text"
                                wire:model="editingName"
                                class="w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @error('editingName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="flex-shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-hover">
                            حفظ
                        </button>
                        <button type="button" wire:click="cancelEdit" class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink">
                            إلغاء
                        </button>
                    </form>
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink">{{ $category->name }}</p>
                            <p class="text-xs text-muted">{{ $category->products_count }} منتج</p>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-2">
                            <button
                                type="button"
                                wire:click="startEdit({{ $category->id }})"
                                class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                            >
                                تعديل
                            </button>

                            <div x-data="{ confirming: false }" class="contents">
                                <button
                                    type="button"
                                    x-on:click="confirming = true"
                                    class="rounded-lg border border-discount px-2.5 py-1 text-xs font-medium text-discount"
                                >
                                    حذف
                                </button>

                                <div
                                    x-show="confirming"
                                    x-cloak
                                    x-on:click.self="confirming = false"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                >
                                    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                        <p class="text-base font-medium text-ink">حذف فئة "{{ $category->name }}"؟</p>
                                        @if ($category->products_count > 0)
                                            <p class="mt-1 text-sm text-muted">
                                                {{ $category->products_count }} منتج مرتبط بهذه الفئة سيصبح بدون فئة.
                                            </p>
                                        @endif
                                        <div class="mt-4 flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="confirming = false; $wire.deleteCategory({{ $category->id }})"
                                                class="flex-1 rounded-lg bg-discount py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                            >
                                                حذف
                                            </button>
                                            <button
                                                type="button"
                                                x-on:click="confirming = false"
                                                class="flex-1 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink"
                                            >
                                                إلغاء
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد فئات بعد.</p>
        @endforelse
    </div>
</div>
