<x-layouts.app :title="$product->name">
    <div class="mx-auto max-w-md pb-24">
        <div class="aspect-square w-full bg-gray-100">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
        </div>

        <div class="p-4">
            <p class="text-sm text-gray-500">
                <a href="{{ route('store.show', $vendor) }}" class="underline">{{ $vendor->store_name }}</a>
                @if ($vendor->is_verified)
                    <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Verified</span>
                @endif
            </p>

            <h1 class="mt-1 text-xl font-semibold text-gray-800">{{ $product->name }}</h1>

            <div class="mt-2 flex items-center gap-2">
                <span class="text-lg font-semibold text-rose-600">{{ number_format($product->price, 2) }}</span>
                @if ($product->compare_at_price)
                    <span class="text-sm text-gray-400 line-through">{{ number_format($product->compare_at_price, 2) }}</span>
                @endif
            </div>

            @if ($product->stock_status === 'pre_order')
                <span class="mt-2 inline-block rounded bg-red-100 px-2 py-0.5 text-xs text-red-700">Pre-order</span>
            @elseif ($product->stock_status === 'out_of_stock')
                <span class="mt-2 inline-block rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Out of stock</span>
            @endif

            @if ($product->options)
                <p class="mt-3 text-sm text-gray-600">{{ $product->options }}</p>
            @endif

            <p class="mt-4 whitespace-pre-line text-sm text-gray-600">{{ $product->description }}</p>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 border-t border-gray-100 bg-white p-4">
        <div class="mx-auto max-w-md">
            <a
                href="{{ route('store.product.contact', [$vendor, $product]) }}"
                class="block w-full rounded-lg bg-emerald-600 py-3 text-center text-sm font-semibold text-white"
            >
                Order via WhatsApp
            </a>
        </div>
    </div>
</x-layouts.app>
