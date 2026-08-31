@php
    $pendingCount = \App\Models\Product::where('status', 'pending')->count();
    $vendorCount = \App\Models\User::where('role', 'vendor')->count();
    $openCartCount = \App\Models\SheinCart::where('status', 'open')->count();
@endphp

<x-layouts.app title="Admin Dashboard">
    <div class="mx-auto max-w-2xl p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800">Admin</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 underline">Log out</button>
            </form>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3">
            <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-gray-100 p-4 text-center">
                <p class="text-2xl font-semibold text-rose-600">{{ $pendingCount }}</p>
                <p class="text-xs text-gray-500">Pending products</p>
            </a>
            <a href="{{ route('admin.vendors.index') }}" class="rounded-lg border border-gray-100 p-4 text-center">
                <p class="text-2xl font-semibold text-rose-600">{{ $vendorCount }}</p>
                <p class="text-xs text-gray-500">Vendors</p>
            </a>
            <a href="{{ route('admin.carts.index') }}" class="rounded-lg border border-gray-100 p-4 text-center">
                <p class="text-2xl font-semibold text-rose-600">{{ $openCartCount }}</p>
                <p class="text-xs text-gray-500">Open SHEIN carts</p>
            </a>
        </div>
    </div>
</x-layouts.app>
