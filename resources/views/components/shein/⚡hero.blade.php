<?php

use App\Support\GuestCart;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:2000')]
    public string $link = '';

    #[Validate('required|integer|min:1|max:99')]
    public string $quantity = '1';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public bool $justAdded = false;

    public function addToCart(): void
    {
        $this->validate();

        GuestCart::add($this->link, (int) $this->quantity, $this->notes !== '' ? $this->notes : null);

        $this->link = '';
        $this->quantity = '1';
        $this->notes = '';
        $this->justAdded = true;
        $this->dispatch('cart-updated');
        $this->dispatch('link-added');
    }
};
?>

@vite('resources/js/nebula-shader.js')

<div class="relative flex min-h-[70vh] items-center overflow-hidden bg-primary px-4 py-12">
    {{-- WebGL nebula background; wire:ignore so Livewire re-renders (typing/submitting below) never tear down the canvas --}}
    <div
        wire:ignore
        class="absolute inset-0 z-0"
        aria-hidden="true"
        x-data
        x-init="
            const cleanup = window.mountNebulaShader($el);
            document.addEventListener('livewire:navigating', cleanup, { once: true });
        "
    ></div>

    {{-- Decorative floating shopping/product icons --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        {{-- Shopping bag --}}
        <svg class="floating-icon absolute start-[6%] top-[18%] h-10 w-10 text-white/70 sm:h-14 sm:w-14"
             style="--drift-y:-16px;--drift-duration:7s;--drift-delay:0s;--drift-rot-from:-4deg;--drift-rot-to:4deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m-3 9h13.5A2.25 2.25 0 0021 17.25V9.75A2.25 2.25 0 0018.75 7.5H5.25A2.25 2.25 0 003 9.75v7.5A2.25 2.25 0 005.25 19.5z" />
        </svg>

        {{-- Price tag --}}
        <svg class="floating-icon absolute end-[8%] top-[12%] h-8 w-8 text-white/70 sm:h-12 sm:w-12"
             style="--drift-y:-10px;--drift-duration:5.5s;--drift-delay:0.6s;--drift-rot-from:6deg;--drift-rot-to:-6deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
        </svg>

        {{-- Gift box --}}
        <svg class="floating-icon absolute start-[10%] bottom-[16%] h-9 w-9 text-white/70 sm:h-12 sm:w-12"
             style="--drift-y:-12px;--drift-duration:6.5s;--drift-delay:1.2s;--drift-rot-from:-3deg;--drift-rot-to:5deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H4.5a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h17.25c.621 0 1.125-.504 1.125-1.125v-2.25c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v2.25c0 .621.504 1.125 1.125 1.125z" />
        </svg>

        {{-- Hanger --}}
        <svg class="floating-icon absolute end-[6%] bottom-[22%] h-10 w-10 text-white/70 sm:h-14 sm:w-14"
             style="--drift-y:-14px;--drift-duration:7.5s;--drift-delay:0.3s;--drift-rot-from:4deg;--drift-rot-to:-4deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.375a1.875 1.875 0 100-3.75 1.875 1.875 0 000 3.75zM12 6.375v2.25m0 0L4.5 15v.75a1.5 1.5 0 001.5 1.5h12a1.5 1.5 0 001.5-1.5V15l-7.5-6.375z" />
        </svg>

        {{-- Star (rating) --}}
        <svg class="floating-icon absolute start-[22%] top-[8%] hidden h-6 w-6 text-white/70 sm:block"
             style="--drift-y:-8px;--drift-duration:5s;--drift-delay:0.9s;--drift-rot-from:0deg;--drift-rot-to:10deg"
             fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.448a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.287-3.958z" />
        </svg>

        {{-- Shopping cart --}}
        <svg class="floating-icon absolute end-[20%] bottom-[10%] hidden h-8 w-8 text-white/70 sm:block"
             style="--drift-y:-10px;--drift-duration:6s;--drift-delay:1.5s;--drift-rot-from:-5deg;--drift-rot-to:3deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
        </svg>

        {{-- Payment card --}}
        <svg class="floating-icon absolute start-[30%] bottom-[9%] hidden h-8 w-8 text-white/70 sm:block"
             style="--drift-y:-9px;--drift-duration:6.8s;--drift-delay:1.8s;--drift-rot-from:5deg;--drift-rot-to:-3deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5z" />
        </svg>

        {{-- Receipt --}}
        <svg class="floating-icon absolute end-[28%] top-[22%] hidden h-8 w-8 text-white/70 sm:block"
             style="--drift-y:-11px;--drift-duration:5.8s;--drift-delay:0.4s;--drift-rot-from:-6deg;--drift-rot-to:2deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-2.25-1.313L15 21.75l-2.25-1.313L10.5 21.75l-2.25-1.313L6 21.75l-2.25-1.313L1.5 21.75V3.257c0-.597.237-1.17.659-1.591A2.25 2.25 0 013.75 1h16.5c.621 0 1.125.504 1.125 1.125v.132zM9.75 9h.008v.008H9.75V9zm4.5 4.5h.008v.008h-.008V13.5z" />
        </svg>

        {{-- Heart (wishlist) --}}
        <svg class="floating-icon absolute start-[3%] top-[46%] h-9 w-9 text-white/70 sm:h-11 sm:w-11"
             style="--drift-y:-13px;--drift-duration:6.2s;--drift-delay:1s;--drift-rot-from:4deg;--drift-rot-to:-4deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>

        {{-- Parcel / delivery box --}}
        <svg class="floating-icon absolute end-[3%] top-[44%] h-9 w-9 text-white/70 sm:h-11 sm:w-11"
             style="--drift-y:-12px;--drift-duration:7.2s;--drift-delay:2.1s;--drift-rot-from:-4deg;--drift-rot-to:4deg"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-8.25-4.5L3.75 7.5m16.5 0l-8.25 4.5m8.25-4.5v9l-8.25 4.5m0-9L3.75 7.5m8.25 4.5v9m0-9L3.75 16.5m0-9v9l8.25 4.5" />
        </svg>
    </div>

    <div class="relative z-10 mx-auto max-w-2xl text-center animate-fade-in-up">
        <div
            x-data="{ titles: ['اطلب أي منتج من Shein بسهولة', 'بدون عمولة إضافية، توصيل حتى بابك'], active: 0 }"
            x-init="setInterval(() => active = (active + 1) % titles.length, 3000)"
            class="grid"
        >
            <template x-for="(title, i) in titles" :key="i">
                <h1
                    :class="active === i ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
                    x-text="title"
                    class="col-start-1 row-start-1 text-4xl font-bold tracking-tight text-white transition-all duration-700 ease-in-out sm:text-5xl"
                ></h1>
            </template>
        </div>
        <p class="mt-3 text-base leading-relaxed text-white/70">أدخل كود المنتج وسنضيفه إلى سلتك لطلبه من Shein نيابة عنك</p>

        <div
            x-data="{ open: false }"
            x-on:link-added.window="open = false"
            x-on:cart-updated.window="window.flyToCart($el.querySelector('button'))"
            class="mt-8"
        >
            <button
                type="button"
                x-on:click="open = true"
                class="mx-auto flex items-center gap-2.5 rounded-full bg-white px-8 py-4 text-base font-semibold text-ink shadow-xl transition hover:scale-[1.02] sm:py-5 sm:text-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                ضع رابط المنتج هنا
            </button>

            <div
                x-show="open"
                x-cloak
                x-on:click.self="open = false"
                x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 text-start shadow-xl sm:p-8" x-on:click.stop>
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-ink">أضف منتجاً إلى سلتك</h2>
                        <button type="button" x-on:click="open = false" class="text-muted hover:text-ink" aria-label="إغلاق">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="addToCart" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-ink-soft">رابط المنتج</label>
                            <input
                                type="text"
                                wire:model="link"
                                dir="ltr"
                                placeholder="https://..."
                                class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @error('link') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">الكمية</label>
                            <input
                                type="number"
                                min="1"
                                wire:model="quantity"
                                class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @error('quantity') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">ملاحظات إضافية (اختياري)</label>
                            <textarea
                                wire:model="notes"
                                rows="2"
                                placeholder="مثال: المقاس M، اللون أزرق..."
                                class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                            ></textarea>
                            @error('notes') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="addToCart"
                            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="addToCart">إضافة إلى السلة</span>
                            <span wire:loading wire:target="addToCart">جارٍ الإضافة...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if ($justAdded)
            <p class="mt-4 text-sm text-white/70" wire:poll.3s="$set('justAdded', false)">
                تمت الإضافة إلى سلتك ✓
            </p>
        @endif
    </div>
</div>
