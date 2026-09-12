<x-layouts.app
    title="{{ $vendor->store_name }} - متجر إلكتروني في تعز | ميرا ستور"
    description="تسوقي من متجر {{ $vendor->store_name }} على ميرا ستور، منصة تجار تعز الإلكترونية. تواصل مباشر عبر واتساب وتوصيل داخل اليمن."
    ogType="website"
>
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center gap-2 p-6 pb-0">
            <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $vendor->store_name }}</h1>
            @if ($vendor->is_verified)
                <span class="rounded bg-primary px-2 py-0.5 text-xs font-medium text-white">بائع موثّق</span>
            @endif
        </div>

        <livewire:store.product-grid :vendor="$vendor" />
    </div>
</x-layouts.app>
