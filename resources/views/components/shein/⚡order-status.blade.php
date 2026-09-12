<?php

use App\Models\SheinCart;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|regex:/^[0-9]{7,9}$/')]
    public string $customer_phone = '';

    public ?SheinCart $cart = null;

    /** @var \Illuminate\Support\Collection<int, SheinCart>|null */
    public $matches = null;

    public bool $notFound = false;

    public function track(): void
    {
        $this->validate();

        $key = 'shein-order-status:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('customer_phone', 'محاولات كثيرة جداً. يرجى المحاولة مرة أخرى بعد دقيقة.');

            return;
        }

        RateLimiter::hit($key, 60);

        // The country code is fixed in the UI (the customer only types the
        // local 9 digits), but phones are stored in a handful of formats
        // ("+967 7xxxxxxxx", "+9677xxxxxxxx", ...) — so match on digits
        // only rather than the exact string.
        $normalizedInput = '967'.$this->customer_phone;

        // A visitor who added a link via the homepage "Add Link" flow into
        // a shared open cart recorded their own phone on that item, not on
        // the cart itself (the cart's phone belongs to whoever the cart was
        // created for) — so a match on either one counts. There's no
        // cart_number to filter by, so every cart is checked.
        $matches = SheinCart::with('items')->get()->filter(fn (SheinCart $cart) => preg_replace('/\D/', '', $cart->customer_phone) === $normalizedInput
            || $cart->items->contains(fn ($item) => $item->customer_phone && preg_replace('/\D/', '', $item->customer_phone) === $normalizedInput)
        )->sortByDesc('updated_at')->values();

        if ($matches->isEmpty()) {
            $this->notFound = true;

            return;
        }

        if ($matches->count() === 1) {
            $this->cart = $matches->first();

            return;
        }

        $this->matches = $matches;
    }

    public function selectCart(int $cartId): void
    {
        // Only allow picking a cart that this phone number's own search
        // just matched — not an arbitrary id someone might pass in.
        abort_unless($this->matches && $this->matches->contains('id', $cartId), 403);

        $this->cart = SheinCart::with('items')->find($cartId);
        $this->matches = null;
    }

    public function reset_(): void
    {
        $this->reset(['cart', 'matches', 'notFound', 'customer_phone']);
    }
};
?>

<div x-data="{ open: false }" class="relative">
    <button
        type="button"
        x-on:click="open = true"
        class="relative flex h-9 w-9 items-center justify-center rounded-full text-ink transition hover:bg-surface hover:text-primary"
        aria-label="تتبع طلبك"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2.25 2.75a.75.75 0 000 1.5h1.106c.07 0 .13.05.148.118l1.62 6.482a2.75 2.75 0 002.667 2.15h5.318a2.75 2.75 0 002.667-2.15l1.093-4.372a.75.75 0 00-.728-.928H5.51l-.28-1.122a1.75 1.75 0 00-1.698-1.35H2.25z" />
            <path d="M6.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM13.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
        </svg>

        @if ($cart)
            <span class="absolute end-1 top-1 h-2.5 w-2.5 rounded-full bg-primary ring-2 ring-white"></span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-on:click.self="open = false"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-start shadow-2xl ring-1 ring-black/5 sm:p-7" x-on:click.stop>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-ink">تتبع طلبك</h2>
                    <p class="mt-0.5 text-xs text-muted">
                        @if ($cart)
                            آخر تحديث {{ $cart->updated_at->diffForHumans() }}
                        @elseif ($matches)
                            اختر الطلب الذي تريد تتبعه
                        @else
                            أدخل رقم هاتفك لعرض حالة طلبك
                        @endif
                    </p>
                </div>
                <button
                    type="button"
                    x-on:click="open = false"
                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-muted transition hover:bg-surface hover:text-ink"
                    aria-label="إغلاق"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($cart)
                @php
                    $steps = \App\Models\SheinCart::statuses();
                    $currentIndex = array_search($cart->status, $steps);
                    $currentStatus = \App\Models\SheinCartStatus::where('key', $cart->status)->first();
                @endphp

                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-line-medium bg-surface p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="min-w-0 truncate text-sm font-semibold text-ink">{{ $cart->cart_name }}</p>
                            <span class="flex-shrink-0 rounded-md bg-white px-2 py-1 font-mono text-xs font-medium text-muted" dir="ltr">
                                {{ $cart->cart_number }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <div @class([
                            'inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold',
                            $currentStatus?->pillClasses() ?? 'bg-surface text-muted',
                        ])>
                            <span @class(['h-1.5 w-1.5 flex-shrink-0 rounded-full', $currentStatus?->dotClasses() ?? 'bg-disabled'])></span>
                            {{ $currentStatus?->label ?? $cart->status }}
                        </div>

                        <div class="mt-3 flex items-center gap-1" role="presentation">
                            @foreach ($steps as $i => $step)
                                <span @class([
                                    'h-1.5 flex-1 rounded-full transition-colors',
                                    'bg-primary' => $i <= $currentIndex,
                                    'bg-line-medium' => $i > $currentIndex,
                                ])></span>
                            @endforeach
                        </div>
                    </div>

                    @if ($cart->public_token)
                        <a
                            href="{{ route('shein.public-cart', $cart->public_token) }}"
                            class="flex items-center justify-center gap-1.5 rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover"
                        >
                            عرض تفاصيل السلة
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        wire:click="reset_"
                        class="flex w-full items-center justify-center gap-1.5 py-1 text-sm font-medium text-muted transition hover:text-ink"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.13-6.36M20 15a9 9 0 01-14.13 6.36" />
                        </svg>
                        تتبع سلة أخرى
                    </button>
                </div>
            @elseif ($matches)
                <div class="mt-5 space-y-2">
                    @foreach ($matches as $match)
                        @php
                            $matchStatus = \App\Models\SheinCartStatus::where('key', $match->status)->first();
                        @endphp
                        <button
                            type="button"
                            wire:click="selectCart({{ $match->id }})"
                            class="flex w-full items-center justify-between gap-3 rounded-lg border border-line-medium p-3 text-start transition hover:bg-surface"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $match->cart_name }}</p>
                                <div @class([
                                    'mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium',
                                    $matchStatus?->pillClasses() ?? 'bg-surface text-muted',
                                ])>
                                    <span @class(['h-1 w-1 flex-shrink-0 rounded-full', $matchStatus?->dotClasses() ?? 'bg-disabled'])></span>
                                    {{ $matchStatus?->label ?? $match->status }}
                                </div>
                            </div>
                            <span class="flex-shrink-0 rounded-md bg-surface px-2 py-1 font-mono text-xs font-medium text-muted" dir="ltr">
                                {{ $match->cart_number }}
                            </span>
                        </button>
                    @endforeach

                    <button
                        type="button"
                        wire:click="reset_"
                        class="flex w-full items-center justify-center gap-1.5 py-1 text-sm font-medium text-muted transition hover:text-ink"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.13-6.36M20 15a9 9 0 01-14.13 6.36" />
                        </svg>
                        تتبع برقم آخر
                    </button>
                </div>
            @else
                <form wire:submit="track" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-soft">رقم الهاتف</label>
                        <div class="mt-1.5 flex gap-2" dir="ltr">
                            <div class="flex flex-shrink-0 items-center gap-1.5 rounded-lg border border-line-medium bg-surface px-2 py-2.5 text-base text-ink-soft">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                                <span>+967</span>
                            </div>
                            <input
                                type="text"
                                inputmode="numeric"
                                maxlength="9"
                                wire:model="customer_phone"
                                placeholder="7xxxxxxxx"
                                @class([
                                    'w-full rounded-lg border px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black',
                                    'border-discount' => $errors->has('customer_phone'),
                                    'border-line-medium' => ! $errors->has('customer_phone'),
                                ])
                            >
                        </div>
                        @error('customer_phone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                    </div>

                    @if ($notFound)
                        <div class="rounded-lg border border-discount bg-discount-light p-3">
                            <p class="text-sm font-medium text-discount">لم يتم العثور على طلب مرتبط بهذا الرقم.</p>
                        </div>
                    @endif

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="track"
                        class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="track">تتبع</span>
                        <span wire:loading wire:target="track">جارٍ التتبع...</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
