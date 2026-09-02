<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::query()
                ->select('products.*')
                ->join('users as vendor_tier', 'vendor_tier.id', '=', 'products.vendor_id')
                ->where('products.status', 'approved')
                ->where('vendor_tier.is_active', true)
                ->whereNull('vendor_tier.deleted_at')
                ->when($this->search, fn ($query) => $query->where('products.name', 'like', "%{$this->search}%"))
                ->when($this->categoryId, fn ($query) => $query->where('products.category_id', $this->categoryId))
                ->with(['vendor', 'images' => fn ($query) => $query->orderBy('sort_order')])
                ->orderByDesc('products.is_pinned')
                ->orderByRaw('case when vendor_tier.is_platform_store then 0 when vendor_tier.is_verified then 1 else 2 end')
                ->orderBy('products.display_order')
                ->orderByDesc('products.created_at')
                ->paginate(12),
        ];
    }
};
?>

<div>
    <div class="sticky top-0 z-10 space-y-2 bg-white/90 backdrop-blur px-4 py-3 shadow-sm">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="ابحث عن منتج..."
            class="w-full rounded-lg border border-line-medium px-4 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
        >
        <select
            wire:model.live="categoryId"
            class="w-full rounded-lg border border-line-medium px-4 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
        >
            <option value="">كل الفئات</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-lg bg-surface mx-4 mt-4 p-4 text-sm text-ink">
        اطلب سلة شي إن بدون عمولة اليوم! <a href="{{ route('shein.index') }}" class="font-semibold underline">ابدأ الآن &larr;</a>
    </div>

    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-4" wire:loading.class="opacity-50">
        @forelse ($products as $product)
            <div
                class="product-card animate-fade-in-up overflow-hidden rounded-xl border border-line-medium bg-white"
                style="animation-delay: {{ min($loop->index * 60, 300) }}ms"
            >
                @php
                    $imageUrls = $product->images->isNotEmpty()
                        ? $product->images->map(fn ($image) => Storage::url($image->path))->all()
                        : [Storage::url($product->image_path)];
                @endphp

                <a
                    href="{{ route('store.product', [$product->vendor, $product]) }}"
                    class="block"
                    @if (count($imageUrls) > 1)
                        x-data="{ images: @js($imageUrls), active: 0 }"
                        x-init="setInterval(() => active = (active + 1) % images.length, 3000)"
                    @endif
                >
                    <div class="relative aspect-square w-full overflow-hidden bg-surface">
                        @if (count($imageUrls) > 1)
                            <template x-for="(image, i) in images" :key="i">
                                <img :src="image" x-show="active === i" x-transition.opacity.duration.500ms alt="{{ $product->name }}" class="product-card-image absolute inset-0 h-full w-full object-cover">
                            </template>

                            <div class="absolute inset-x-0 bottom-2 flex items-center justify-center gap-1">
                                <template x-for="(image, i) in images" :key="i">
                                    <span class="h-1.5 w-1.5 rounded-full shadow-sm" :class="active === i ? 'bg-white' : 'bg-white/50'"></span>
                                </template>
                            </div>
                        @else
                            <img src="{{ $imageUrls[0] }}" alt="{{ $product->name }}" class="product-card-image h-full w-full object-cover">
                        @endif
                    </div>
                </a>
                <div class="border-t border-line-medium p-3">
                    <a href="{{ route('store.product', [$product->vendor, $product]) }}">
                        <p class="line-clamp-2 text-sm font-medium leading-snug text-ink">{{ $product->name }}</p>
                    </a>
                    <p class="mt-1 truncate text-xs text-muted">{{ $product->vendor->store_name }}</p>

                    <div class="mt-2 flex items-center justify-between gap-1">
                        <div class="flex items-center gap-1.5">
                            <p class="text-base font-semibold text-primary">{{ number_format($product->price, 2) }}</p>
                            @if ($product->compare_at_price)
                                <p class="text-xs text-disabled line-through">{{ number_format($product->compare_at_price, 2) }}</p>
                            @endif
                        </div>
                        @if ($product->stock_status === 'pre_order')
                            <span class="inline-block flex-shrink-0 rounded bg-discount-light px-2 py-0.5 text-xs font-medium text-discount">طلب مسبق</span>
                        @elseif ($product->stock_status === 'out_of_stock')
                            <span class="inline-block flex-shrink-0 rounded bg-surface px-2 py-0.5 text-xs font-medium text-muted">نفدت الكمية</span>
                        @endif
                    </div>

                    <a
                        href="{{ route('store.product.contact', [$product->vendor, $product]) }}"
                        class="mt-3 flex items-center justify-center gap-1.5 rounded-lg bg-primary py-2 text-xs font-semibold text-white transition hover:bg-primary-hover"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm0 18.1a8.2 8.2 0 01-4.18-1.14l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 01-1.26-4.37c0-4.53 3.69-8.21 8.24-8.21 2.2 0 4.27.86 5.82 2.41a8.15 8.15 0 012.41 5.81c0 4.53-3.69 8.21-8.24 8.21zm4.52-6.16c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.13-.17.25-.64.81-.78.97-.15.17-.29.19-.54.06-.25-.12-1.04-.38-1.99-1.22-.73-.65-1.23-1.46-1.37-1.71-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.42-.14-.01-.31-.01-.47-.01a.9.9 0 00-.65.31c-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.57.12.17 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.23-.17-.48-.29z" />
                        </svg>
                        تواصل عبر واتساب
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center py-16 text-center">
                <svg class="h-10 w-10 text-disabled" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mt-3 text-sm text-disabled">
                    @if ($search)
                        لا توجد منتجات مطابقة لـ "{{ $search }}".
                    @else
                        لا توجد منتجات بعد — تابعنا قريباً.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <div class="px-4 pb-4">
        {{ $products->links() }}
    </div>
</div>
