<?php

use App\Models\SheinCart;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $customerCountryCode = '+967';

    #[Validate('required|string|max:15|regex:/^[0-9\s-]+$/')]
    public string $customerPhone = '';

    #[Validate('required|string|max:2000')]
    public string $link = '';

    #[Validate('required|integer|min:1|max:99')]
    public string $quantity = '1';

    #[Validate('nullable|string|max:1000')]
    public string $specifications = '';

    public bool $justAdded = false;

    /**
     * Resolved fresh on every request (mount, and again on every subsequent
     * interaction) rather than cached once — so if the admin switches which
     * cart accepts submissions while a customer already has the page open,
     * their next submission still lands in whichever cart is active *now*,
     * not whichever was active when the page first loaded.
     */
    protected function activeCart(): ?SheinCart
    {
        return SheinCart::where('accepts_submissions', true)->first();
    }

    public function addToCart(): void
    {
        $cart = $this->activeCart();

        abort_unless($cart, 404);
        abort_if($cart->is_locked, 403);

        $this->validate();

        $cart->items()->create([
            'name' => $this->specifications !== '' ? $this->specifications : null,
            'link' => $this->link,
            'quantity' => (int) $this->quantity,
            'customer_phone' => $this->customerCountryCode.' '.$this->customerPhone,
            'item_date' => now(),
        ]);

        $this->link = '';
        $this->quantity = '1';
        $this->specifications = '';
        $this->justAdded = true;
        $this->dispatch('link-added');
    }

    public function with(): array
    {
        $cart = $this->activeCart();

        return [
            'activeCart' => $cart,
            'adminWhatsappLink' => $cart ? 'https://wa.me/'.preg_replace('/\D/', '', $cart->customer_phone) : null,
        ];
    }
};
?>

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
        <p class="mt-3 text-base leading-relaxed text-white/70">أدخل رابط المنتج وسنطلبه لك من Shein نيابة عنك</p>

        @if ($activeCart)
            <div
                x-data="{ open: false }"
                x-on:link-added.window="window.flyToCart($el.querySelector('button'))"
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
                            <h2 class="text-lg font-bold text-ink">أضف منتجاً</h2>
                            <button type="button" x-on:click="open = false" class="text-muted hover:text-ink" aria-label="إغلاق">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 rounded-lg bg-surface p-3 text-sm text-ink-soft">
                            الإضافة إلى: <span class="font-semibold text-ink">{{ $activeCart->cart_name }}</span>
                        </div>

                        <div
                            class="mt-4 space-y-4"
                            x-data="{
                                confirming: false,
                                pLink: @js($link),
                                pQuantity: @js($quantity),
                                pSpecifications: @js($specifications),
                                pPhone: @js($customerPhone),
                            }"
                        >
                        @if ($justAdded)
                            <div class="space-y-4">
                                <div class="rounded-lg border border-green-300 bg-green-50 p-3">
                                    <p class="text-sm font-semibold text-green-700">تمت الإضافة إلى السلة بنجاح ✓</p>
                                </div>

                                @if ($adminWhatsappLink)
                                    <div class="rounded-lg border border-line-medium bg-surface p-3">
                                        <p class="text-sm text-ink-soft">لأي استفسار بخصوص هذا المنتج</p>
                                        <a
                                            href="{{ $adminWhatsappLink }}"
                                            target="_blank"
                                            class="mt-1.5 flex items-center justify-center gap-1.5 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink transition hover:border-black"
                                        >
                                            تواصل معنا عبر واتساب
                                        </a>
                                    </div>
                                @endif

                                <button
                                    type="button"
                                    wire:click="$set('justAdded', false)"
                                    x-on:click="confirming = false"
                                    class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover"
                                >
                                    إضافة منتج آخر
                                </button>
                            </div>
                        @else
                        @if ($errors->any())
                            <div class="rounded-lg border border-discount bg-discount-light p-3">
                                <p class="text-sm font-semibold text-discount">تعذّرت الإضافة، يرجى تصحيح ما يلي:</p>
                                <ul class="mt-1 list-inside list-disc text-sm text-discount">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            @if ($adminWhatsappLink)
                                <div class="rounded-lg border border-line-medium bg-surface p-3">
                                    <p class="text-sm text-ink-soft">هل واجهت مشكلة أثناء الإضافة؟</p>
                                    <a
                                        href="{{ $adminWhatsappLink }}"
                                        target="_blank"
                                        class="mt-1.5 flex items-center justify-center gap-1.5 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink transition hover:border-black"
                                    >
                                        تواصل معنا عبر واتساب
                                    </a>
                                </div>
                            @endif
                        @endif

                        <div x-show="!confirming" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-ink-soft">رابط المنتج</label>
                                <input
                                    type="text"
                                    wire:model="link"
                                    x-model="pLink"
                                    dir="ltr"
                                    placeholder="https://..."
                                    @class([
                                        'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black',
                                        'border-discount' => $errors->has('link'),
                                        'border-line-medium' => ! $errors->has('link'),
                                    ])
                                >
                                @error('link') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-ink-soft">الكمية</label>
                                <input
                                    type="number"
                                    min="1"
                                    wire:model="quantity"
                                    x-model="pQuantity"
                                    @class([
                                        'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black',
                                        'border-discount' => $errors->has('quantity'),
                                        'border-line-medium' => ! $errors->has('quantity'),
                                    ])
                                >
                                @error('quantity') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-ink-soft">مواصفات المنتج (اختياري)</label>
                                <textarea
                                    wire:model="specifications"
                                    x-model="pSpecifications"
                                    rows="2"
                                    placeholder="مثال: المقاس M، اللون أزرق..."
                                    @class([
                                        'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black',
                                        'border-discount' => $errors->has('specifications'),
                                        'border-line-medium' => ! $errors->has('specifications'),
                                    ])
                                ></textarea>
                                @error('specifications') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-ink-soft">رقم واتساب للتواصل معك</label>
                                <div class="mt-1.5 flex gap-2" dir="ltr">
                                    <div class="flex flex-shrink-0 items-center gap-1.5 rounded-lg border border-line-medium bg-surface px-2 py-2.5 text-base text-ink-soft">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                                        <span>+967</span>
                                    </div>
                                    <input
                                        type="text"
                                        wire:model="customerPhone"
                                        x-model="pPhone"
                                        placeholder="7xxxxxxxx"
                                        @class([
                                            'w-full rounded-lg border px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black',
                                            'border-discount' => $errors->has('customerPhone'),
                                            'border-line-medium' => ! $errors->has('customerPhone'),
                                        ])
                                    >
                                </div>
                                @error('customerPhone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>

                            <button
                                type="button"
                                x-on:click="confirming = true"
                                class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover"
                            >
                                إضافة إلى السلة
                            </button>
                        </div>

                        <div x-show="confirming" x-cloak class="space-y-3">
                            <p class="text-sm font-semibold text-ink">تأكد من صحة المعلومات قبل الإضافة:</p>

                            <div class="space-y-2 rounded-lg bg-surface p-3 text-sm">
                                <p class="text-ink-soft">الرابط: <span class="break-all font-medium text-ink" dir="ltr" x-text="pLink"></span></p>
                                <p class="text-ink-soft">الكمية: <span class="font-medium text-ink" x-text="pQuantity"></span></p>
                                <p class="text-ink-soft" x-show="pSpecifications">المواصفات: <span class="font-medium text-ink" x-text="pSpecifications"></span></p>
                                <p class="text-ink-soft">رقم واتساب: <span class="font-medium text-ink" dir="ltr" x-text="'+967 ' + pPhone"></span></p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    x-on:click="$wire.addToCart().then(() => { confirming = false })"
                                    wire:loading.attr="disabled"
                                    wire:target="addToCart"
                                    class="flex-1 rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="addToCart">نعم، المعلومات صحيحة</span>
                                    <span wire:loading wire:target="addToCart">جارٍ الإضافة...</span>
                                </button>
                                <button
                                    type="button"
                                    x-on:click="confirming = false"
                                    class="flex-1 rounded-lg border border-line-medium py-3 text-base font-semibold text-ink"
                                >
                                    رجوع للتعديل
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
