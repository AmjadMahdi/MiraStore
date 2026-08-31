@php
    $vendor = auth()->user();
    $productCount = $vendor->products()->count();
    $limit = $vendor->max_products_limit;
    $percent = $limit ? min(100, (int) round($productCount / $limit * 100)) : 0;
@endphp

<x-layouts.app title="Vendor Dashboard">
    <div class="mx-auto max-w-2xl p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800">{{ $vendor->store_name }}</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 underline">Log out</button>
            </form>
        </div>

        <div class="mt-6 rounded-lg border border-gray-100 p-4">
            <p class="text-sm font-medium text-gray-700">Product quota</p>

            @if ($limit)
                <div class="mt-2 h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-rose-600" style="width: {{ $percent }}%"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">{{ $productCount }} / {{ $limit }} products used</p>
            @else
                <p class="mt-1 text-xs text-gray-500">{{ $productCount }} products — unlimited (Premium)</p>
            @endif

            @if ($vendor->is_verified)
                <span class="mt-2 inline-block rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Verified Seller</span>
            @endif
        </div>

        @if ($vendor->slug)
            <a href="{{ route('store.show', $vendor) }}" class="mt-3 block text-center text-sm text-gray-500 underline">
                View my public store
            </a>
        @endif
    </div>
</x-layouts.app>
