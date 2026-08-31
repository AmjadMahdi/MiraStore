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
            $this->addError('cart_number', 'Too many attempts. Please try again in a minute.');

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

<div class="mx-auto max-w-md p-6">
    @if ($cart)
        @php
            $steps = \App\Models\SheinCart::STATUSES;
            $currentIndex = array_search($cart->status, $steps);
        @endphp

        <div>
            <p class="text-sm text-gray-500">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>

            <div class="mt-4 flex items-center justify-between">
                @foreach ($steps as $i => $step)
                    <div class="flex flex-1 flex-col items-center">
                        <div @class([
                            'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-rose-600 text-white' => $i <= $currentIndex,
                            'bg-gray-200 text-gray-400' => $i > $currentIndex,
                        ])>
                            {{ $i + 1 }}
                        </div>
                        <p class="mt-1 text-center text-xs capitalize text-gray-500">{{ str_replace('_', ' ', $step) }}</p>
                    </div>

                    @if (! $loop->last)
                        <div @class([
                            'h-0.5 flex-1',
                            'bg-rose-600' => $i < $currentIndex,
                            'bg-gray-200' => $i >= $currentIndex,
                        ])></div>
                    @endif
                @endforeach
            </div>
        </div>

        <button type="button" wire:click="reset_" class="mt-6 text-sm text-gray-500 underline">
            Track another cart
        </button>
    @else
        <form wire:submit="track" class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-800">Track your order</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700">Phone number</label>
                <input type="text" wire:model="customer_phone" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cart number</label>
                <input type="text" wire:model="cart_number" placeholder="MIRA-12345" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                @error('cart_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($notFound)
                <p class="text-sm text-red-600">No matching order found.</p>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="track"
                class="w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="track">Track</span>
                <span wire:loading wire:target="track">Tracking...</span>
            </button>
        </form>
    @endif
</div>
