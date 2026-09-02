<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    /** @var array<int, int> */
    public array $orderedIds = [];

    public function mount(): void
    {
        $this->refreshOrderedIds();
    }

    public function togglePin(Product $product): void
    {
        $product->update(['is_pinned' => ! $product->is_pinned]);

        $this->refreshOrderedIds();
    }

    protected function refreshOrderedIds(): void
    {
        $this->orderedIds = Product::query()
            ->where('status', 'approved')
            ->orderByDesc('is_pinned')
            ->orderBy('display_order')
            ->latest()
            ->pluck('id')
            ->all();
    }

    public function moveItem(int $from, int $to): void
    {
        if ($from === $to || ! array_key_exists($from, $this->orderedIds) || ! array_key_exists($to, $this->orderedIds)) {
            return;
        }

        $ids = $this->orderedIds;
        $moved = array_splice($ids, $from, 1);
        array_splice($ids, $to, 0, $moved);

        $this->orderedIds = $ids;

        foreach ($this->orderedIds as $index => $id) {
            Product::whereKey($id)->update(['display_order' => $index]);
        }
    }

    public function with(): array
    {
        $products = Product::with('vendor')->whereIn('id', $this->orderedIds)->get()->keyBy('id');

        return [
            'products' => collect($this->orderedIds)->map(fn ($id) => $products->get($id))->filter()->values(),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">ترتيب عرض المنتجات</h1>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-primary underline">رجوع لمراجعة المنتجات</a>
    </div>

    <p class="mt-2 text-sm text-muted">
        اسحب المنتجات لتغيير ترتيب ظهورها في الصفحة الرئيسية وفي صفحة كل متجر. المنتجات في الأعلى تظهر أولاً.
    </p>

    <div class="mt-6 space-y-2" x-data="{ dragIndex: null }">
        @forelse ($products as $index => $product)
            <div
                draggable="true"
                x-on:dragstart="dragIndex = {{ $index }}"
                x-on:dragover.prevent
                x-on:drop="if (dragIndex !== null) { $wire.moveItem(dragIndex, {{ $index }}); dragIndex = null }"
                @class([
                    'flex cursor-move items-center gap-3 rounded-lg border p-3',
                    'border-primary bg-surface' => $product->is_pinned,
                    'border-line-medium bg-white' => ! $product->is_pinned,
                ])
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-disabled" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z" />
                </svg>

                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-lg bg-surface">
                    <img src="{{ Storage::url($product->image_path) }}" class="h-full w-full object-cover">
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink">
                        {{ $product->name }}
                        @if ($product->is_pinned)
                            <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">مثبّت</span>
                        @endif
                    </p>
                    <p class="truncate text-xs text-muted">{{ $product->vendor->store_name }}</p>
                </div>

                <button
                    type="button"
                    wire:click="togglePin({{ $product->id }})"
                    @class([
                        'flex-shrink-0 rounded-lg border px-2.5 py-1 text-xs font-medium',
                        'border-primary bg-primary text-white' => $product->is_pinned,
                        'border-line-medium text-ink-soft' => ! $product->is_pinned,
                    ])
                >
                    {{ $product->is_pinned ? 'إلغاء التثبيت' : 'تثبيت في الأعلى' }}
                </button>

                <span class="flex-shrink-0 rounded bg-surface px-2 py-0.5 text-xs font-medium text-muted">{{ $index + 1 }}</span>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد منتجات معتمدة لترتيبها.</p>
        @endforelse
    </div>
</div>
