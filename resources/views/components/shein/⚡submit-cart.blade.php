<?php

use App\Models\SheinCart;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $cart_name = 'My SHEIN Order';

    #[Validate('required|string|max:2000')]
    public string $cart_details = '';

    #[Validate('required|string|max:20|regex:/^[0-9+\s-]+$/')]
    public string $customer_phone = '';

    public ?string $confirmedCartNumber = null;

    public function submit(): void
    {
        $this->validate();

        $cart = SheinCart::create([
            'cart_name' => $this->cart_name,
            'cart_details' => $this->cart_details,
            'customer_phone' => $this->customer_phone,
        ]);

        $this->confirmedCartNumber = $cart->cart_number;
        $this->reset(['cart_details', 'customer_phone']);
        $this->cart_name = 'My SHEIN Order';
    }
};
?>

<div class="mx-auto max-w-md p-6">
    @if ($confirmedCartNumber)
        <div class="rounded-lg bg-emerald-50 p-4 text-center">
            <p class="text-sm text-emerald-700">Cart submitted! Save your cart number to track your order:</p>
            <p class="mt-2 text-2xl font-bold text-emerald-800">{{ $confirmedCartNumber }}</p>
            <button type="button" wire:click="$set('confirmedCartNumber', null)" class="mt-3 text-sm text-emerald-700 underline">
                Submit another cart
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cart name</label>
                <input type="text" wire:model="cart_name" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                @error('cart_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">SHEIN cart code or links</label>
                <textarea wire:model="cart_details" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                @error('cart_details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Your WhatsApp number</label>
                <input type="text" wire:model="customer_phone" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="submit">Submit cart</span>
                <span wire:loading wire:target="submit">Submitting...</span>
            </button>
        </form>
    @endif
</div>
