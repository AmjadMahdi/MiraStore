@php
    $pendingCount = \App\Models\Product::where('status', 'pending')->count();
    $vendorCount = \App\Models\User::where('role', 'vendor')->count();
    $openCartCount = \App\Models\SheinCart::where('status', 'open')->count();
@endphp

<x-layouts.app title="لوحة تحكم المسؤول">
    <div class="mx-auto max-w-2xl p-6 sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-ink">المسؤول</h1>

        <div class="mt-6 grid grid-cols-3 gap-3">
            <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-line-medium p-4 text-center">
                <p class="text-2xl font-semibold text-primary">{{ $pendingCount }}</p>
                <p class="text-xs text-muted">منتجات قيد المراجعة</p>
            </a>
            <a href="{{ route('admin.vendors.index') }}" class="rounded-lg border border-line-medium p-4 text-center">
                <p class="text-2xl font-semibold text-primary">{{ $vendorCount }}</p>
                <p class="text-xs text-muted">التجّار</p>
            </a>
            <a href="{{ route('admin.carts.index') }}" class="rounded-lg border border-line-medium p-4 text-center">
                <p class="text-2xl font-semibold text-primary">{{ $openCartCount }}</p>
                <p class="text-xs text-muted">سلال شي إن المفتوحة</p>
            </a>
        </div>
    </div>
</x-layouts.app>
