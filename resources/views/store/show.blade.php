<x-layouts.app :title="$vendor->store_name">
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center gap-2 p-6 pb-0">
            <h1 class="text-xl font-semibold text-gray-800">{{ $vendor->store_name }}</h1>
            @if ($vendor->is_verified)
                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Verified Seller</span>
            @endif
        </div>

        <livewire:store.product-grid :vendor="$vendor" />
    </div>
</x-layouts.app>
