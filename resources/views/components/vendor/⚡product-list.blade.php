<?php

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public function delete(Product $product): void
    {
        abort_unless($product->vendor_id === Auth::id(), 403);

        $product->delete();
    }

    public function with(): array
    {
        $vendor = Auth::user();

        return [
            'vendor' => $vendor,
            'products' => $vendor->products()->latest()->get(),
            'atLimit' => $vendor->max_products_limit !== null
                && $vendor->products()->count() >= $vendor->max_products_limit,
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">My products</h1>

        @if (! $atLimit)
            <a href="{{ route('vendor.products.create') }}" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">
                Add product
            </a>
        @endif
    </div>

    @if ($atLimit)
        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-700">
            You've reached your {{ $vendor->max_products_limit }}-product limit. Upgrade to Premium for unlimited uploads.
        </p>
    @endif

    <div class="mt-6 space-y-3">
        @forelse ($products as $product)
            <div class="flex items-center gap-3 rounded-lg border border-gray-100 p-3">
                <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                    <img src="{{ Storage::url($product->image_path) }}" class="h-full w-full object-cover">
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-800">{{ $product->name }}</p>
                    <p class="text-sm text-gray-500">{{ number_format($product->price, 2) }}</p>
                </div>

                <span @class([
                    'rounded px-2 py-0.5 text-xs font-medium',
                    'bg-emerald-100 text-emerald-700' => $product->status === 'approved',
                    'bg-amber-100 text-amber-700' => $product->status === 'pending',
                    'bg-red-100 text-red-700' => $product->status === 'rejected',
                ])>
                    {{ ucfirst($product->status) }}
                </span>

                <a href="{{ route('vendor.products.edit', $product) }}" class="text-sm text-gray-500 underline">Edit</a>

                <button
                    type="button"
                    wire:click="delete({{ $product->id }})"
                    wire:confirm="Delete this product?"
                    class="text-sm text-red-500 underline"
                >
                    Delete
                </button>
            </div>

            @if ($product->status === 'rejected' && $product->rejection_reason)
                <p class="-mt-2 pl-3 text-xs text-red-600">Rejected: {{ $product->rejection_reason }}</p>
            @endif
        @empty
            <div class="flex flex-col items-center py-16 text-center">
                <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mt-3 text-sm text-gray-400">You haven't added any products yet.</p>
                <a href="{{ route('vendor.products.create') }}" class="mt-3 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">
                    Add your first product
                </a>
            </div>
        @endforelse
    </div>
</div>
