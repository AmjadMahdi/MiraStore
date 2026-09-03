<?php

use App\Models\SheinCart;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $cart_name = 'طلبي من شي إن';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:20|regex:/^(?=.*\d)[0-9+\s-]+$/')]
    public string $customer_phone = '';

    public function save(): void
    {
        $this->validate();

        $cart = SheinCart::createWithUniqueNumber([
            'cart_name' => $this->cart_name,
            'description' => $this->description !== '' ? $this->description : null,
            'customer_phone' => $this->customer_phone,
            'cart_details' => '',
        ]);

        $this->redirect(route('admin.carts.show', $cart), navigate: true);
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">سلة جديدة</h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">اسم السلة</label>
            <input type="text" wire:model="cart_name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('cart_name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">وصف السلة (اختياري)</label>
            <textarea wire:model="description" rows="3" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"></textarea>
            @error('description') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">رقم واتساب العميل</label>
            <input type="text" wire:model="customer_phone" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('customer_phone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            إنشاء السلة
        </button>
    </form>
</div>
