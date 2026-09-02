<x-layouts.app :title="$product->name">
    @php
        $galleryImages = $product->images->isNotEmpty() ? $product->images : collect([(object) ['path' => $product->image_path]]);
    @endphp

    <div class="mx-auto max-w-md pb-24 animate-fade-in-up">
        <div class="relative" x-data="{ active: 0, count: {{ $galleryImages->count() }} }">
            <div
                x-ref="slider"
                x-on:scroll="active = Math.round($refs.slider.scrollLeft / $refs.slider.clientWidth)"
                class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth"
            >
                @foreach ($galleryImages as $galleryImage)
                    <div class="aspect-square w-full flex-shrink-0 snap-center overflow-hidden bg-surface">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($galleryImage->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    </div>
                @endforeach
            </div>

            @if ($galleryImages->count() > 1)
                <button
                    type="button"
                    x-on:click="$refs.slider.scrollBy({ left: -$refs.slider.clientWidth, behavior: 'smooth' })"
                    class="absolute top-1/2 start-2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-primary text-white"
                    aria-label="الصورة السابقة"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L8.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button
                    type="button"
                    x-on:click="$refs.slider.scrollBy({ left: $refs.slider.clientWidth, behavior: 'smooth' })"
                    class="absolute top-1/2 end-2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-primary text-white"
                    aria-label="الصورة التالية"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L11.586 10 7.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5">
                    @foreach ($galleryImages as $i => $galleryImage)
                        <button
                            type="button"
                            x-on:click="$refs.slider.scrollTo({ left: {{ $i }} * $refs.slider.clientWidth, behavior: 'smooth' })"
                            x-bind:class="active === {{ $i }} ? 'bg-white' : 'bg-white/50'"
                            class="h-1.5 w-1.5 rounded-full transition"
                            aria-label="الصورة {{ $i + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="p-4">
            <p class="text-sm text-muted">
                <a href="{{ route('store.show', $vendor) }}" class="underline">{{ $vendor->store_name }}</a>
                @if ($vendor->is_verified)
                    <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">موثّق</span>
                @endif
            </p>

            <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">{{ $product->name }}</h1>

            @if ($product->category)
                <span class="mt-1 inline-block rounded bg-surface px-2 py-0.5 text-xs font-medium text-muted">{{ $product->category->name }}</span>
            @endif

            <div class="mt-2 flex items-center gap-2">
                <span class="text-lg font-semibold text-primary">{{ number_format($product->price, 2) }}</span>
                @if ($product->compare_at_price)
                    <span class="text-sm text-disabled line-through">{{ number_format($product->compare_at_price, 2) }}</span>
                @endif
            </div>

            @if ($product->stock_status === 'pre_order')
                <span class="mt-2 inline-block rounded bg-discount-light px-2 py-0.5 text-xs text-discount">طلب مسبق</span>
            @elseif ($product->stock_status === 'out_of_stock')
                <span class="mt-2 inline-block rounded bg-surface px-2 py-0.5 text-xs text-muted">نفدت الكمية</span>
            @endif

            @if ($product->options)
                <p class="mt-3 text-sm text-muted">{{ $product->options }}</p>
            @endif

            <p class="mt-4 whitespace-pre-line text-justify text-sm text-muted">{{ $product->description }}</p>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 border-t border-line-medium bg-white p-4">
        <div class="mx-auto max-w-md">
            <a
                href="{{ route('store.product.contact', [$vendor, $product]) }}"
                class="block w-full rounded-lg bg-primary py-3 text-center text-sm font-semibold text-white transition-transform duration-150 hover:bg-primary-hover active:scale-95"
            >
                اطلب عبر واتساب
            </a>
        </div>
    </div>
</x-layouts.app>
