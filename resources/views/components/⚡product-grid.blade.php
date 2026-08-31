<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->where('status', 'approved')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->with('vendor')
                ->latest()
                ->paginate(12),
        ];
    }
};
?>

<div>
    <div class="sticky top-0 z-10 bg-white/90 backdrop-blur px-4 py-3 shadow-sm">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search products..."
            class="w-full rounded-lg border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500"
        >
    </div>

    <div class="rounded-lg bg-rose-50 mx-4 mt-4 p-4 text-sm text-rose-700">
        Order your SHEIN cart with zero commission today! <a href="{{ route('shein.index') }}" class="font-semibold underline">Start now &rarr;</a>
    </div>

    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-4" wire:loading.class="opacity-50">
        @forelse ($products as $product)
            <a href="{{ route('store.product', [$product->vendor, $product]) }}" class="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm">
                <div class="aspect-square w-full bg-gray-100">
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                </div>
                <div class="p-2">
                    <p class="line-clamp-2 text-sm font-medium text-gray-800">{{ $product->name }}</p>
                    <p class="mt-1 text-sm font-semibold text-rose-600">{{ number_format($product->price, 2) }}</p>
                    @if ($product->stock_status === 'pre_order')
                        <span class="mt-1 inline-block rounded bg-red-100 px-2 py-0.5 text-xs text-red-700">Pre-order</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full flex flex-col items-center py-16 text-center">
                <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mt-3 text-sm text-gray-400">
                    @if ($search)
                        No products match "{{ $search }}".
                    @else
                        No products yet — check back soon.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <div class="px-4 pb-4">
        {{ $products->links() }}
    </div>
</div>
