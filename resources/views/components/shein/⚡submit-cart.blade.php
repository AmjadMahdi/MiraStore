<?php

use App\Models\SheinCart;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $cart_name = 'طلبي من شي إن';

    #[Validate('required|string|max:2000')]
    public string $cart_details = '';

    #[Validate('required|string|max:20|regex:/^(?=.*\d)[0-9+\s-]+$/')]
    public string $customer_phone = '';

    public ?string $confirmedCartNumber = null;

    public function submit(): void
    {
        $this->validate();

        $cart = SheinCart::createWithUniqueNumber([
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

<div class="mx-auto max-w-md p-6 sm:p-8">
    @if ($confirmedCartNumber)
        <div class="rounded-lg bg-green-50 p-4 text-center">
            <p class="text-sm text-success">تم إرسال السلة! احتفظ برقم السلة لمتابعة طلبك:</p>
            <p class="mt-2 text-2xl font-bold text-green-800">{{ $confirmedCartNumber }}</p>
            <button type="button" wire:click="$set('confirmedCartNumber', null)" class="mt-3 text-sm text-success underline">
                إرسال سلة أخرى
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink-soft">اسم السلة</label>
                <input type="text" wire:model="cart_name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('cart_name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">كود سلة شي إن أو الروابط</label>
                <textarea wire:model="cart_details" rows="4" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"></textarea>
                @error('cart_details') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">رقم واتساب الخاص بك</label>
                <input type="text" wire:model="customer_phone" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('customer_phone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="submit">إرسال السلة</span>
                <span wire:loading wire:target="submit">جارٍ الإرسال...</span>
            </button>
        </form>
    @endif
</div>
