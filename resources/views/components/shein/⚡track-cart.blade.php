<?php

use App\Models\SheinCart;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:20')]
    public string $customer_phone = '';

    #[Validate('required|string|max:20')]
    public string $cart_number = '';

    public ?SheinCart $cart = null;

    public bool $notFound = false;

    public function track(): void
    {
        $this->validate();

        $key = 'shein-tracking:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('cart_number', 'محاولات كثيرة جداً. يرجى المحاولة مرة أخرى بعد دقيقة.');

            return;
        }

        RateLimiter::hit($key, 60);

        $this->cart = SheinCart::where('cart_number', $this->cart_number)
            ->where('customer_phone', $this->customer_phone)
            ->first();

        $this->notFound = $this->cart === null;
    }

    public function reset_(): void
    {
        $this->reset(['cart', 'notFound', 'customer_phone', 'cart_number']);
    }
};
?>

<div class="mx-auto max-w-md p-6 sm:p-8">
    @if ($cart)
        @php
            $steps = \App\Models\SheinCart::STATUSES;
            $stepLabels = [
                'open' => 'مفتوحة',
                'ordered' => 'تم الطلب',
                'in_transit' => 'في الطريق',
                'arrived' => 'تم الوصول',
            ];
            $currentIndex = array_search($cart->status, $steps);
        @endphp

        <div>
            <p class="text-sm text-muted">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>

            <div class="mt-4 flex items-center justify-between">
                @foreach ($steps as $i => $step)
                    <div class="flex flex-1 flex-col items-center">
                        <div @class([
                            'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-primary text-white' => $i <= $currentIndex,
                            'bg-line-medium text-disabled' => $i > $currentIndex,
                        ])>
                            {{ $i + 1 }}
                        </div>
                        <p class="mt-1 text-center text-xs text-muted">{{ $stepLabels[$step] }}</p>
                    </div>

                    @if (! $loop->last)
                        <div @class([
                            'h-0.5 flex-1',
                            'bg-primary' => $i < $currentIndex,
                            'bg-line-medium' => $i >= $currentIndex,
                        ])></div>
                    @endif
                @endforeach
            </div>
        </div>

        <button type="button" wire:click="reset_" class="mt-6 text-sm text-muted underline">
            تتبع سلة أخرى
        </button>
    @else
        <form wire:submit="track" class="space-y-4">
            <h2 class="text-lg font-semibold text-ink">تتبع طلبك</h2>

            <div>
                <label class="block text-sm font-medium text-ink-soft">رقم الهاتف</label>
                <input type="text" wire:model="customer_phone" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('customer_phone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">رقم السلة</label>
                <input type="text" wire:model="cart_number" placeholder="MIRA-12345" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black" dir="ltr">
                @error('cart_number') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            @if ($notFound)
                <p class="text-sm text-discount">لم يتم العثور على طلب مطابق.</p>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="track"
                class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="track">تتبع</span>
                <span wire:loading wire:target="track">جارٍ التتبع...</span>
            </button>
        </form>
    @endif
</div>
