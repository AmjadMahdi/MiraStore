@php
    $vendor = auth()->user();
    $productCount = $vendor->products()->count();
    $limit = $vendor->max_products_limit;
    $percent = $limit ? min(100, (int) round($productCount / $limit * 100)) : 0;
@endphp

<x-layouts.app title="لوحة تحكم التاجر">
    <div class="mx-auto max-w-2xl p-6 sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $vendor->store_name }}</h1>

        <div class="mt-6 rounded-lg border border-line-medium p-4">
            <p class="text-sm font-medium text-ink-soft">حصة المنتجات</p>

            @if ($limit)
                <div class="mt-2 h-2 w-full rounded-full bg-surface" dir="ltr">
                    <div class="h-2 rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                </div>
                <p class="mt-1 text-xs text-muted">تم استخدام {{ $productCount }} / {{ $limit }} منتج</p>
            @else
                <p class="mt-1 text-xs text-muted">{{ $productCount }} منتج — غير محدود (بريميوم)</p>
            @endif

            @if ($vendor->is_verified)
                <span class="mt-2 inline-block rounded bg-primary px-2 py-0.5 text-xs text-white">بائع موثّق</span>
            @endif
        </div>

        @if ($vendor->slug)
            <a href="{{ route('store.show', $vendor) }}" class="mt-3 block text-center text-sm text-muted underline">
                عرض متجري العام
            </a>
        @endif
    </div>
</x-layouts.app>
