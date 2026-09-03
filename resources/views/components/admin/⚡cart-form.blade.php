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

    public string $customer_country_code = '+967';

    #[Validate('required|string|max:15|regex:/^[0-9\s-]+$/')]
    public string $customer_phone = '';

    public function save(): void
    {
        $this->validate();

        $cart = SheinCart::createWithUniqueNumber([
            'cart_name' => $this->cart_name,
            'description' => $this->description !== '' ? $this->description : null,
            'customer_phone' => $this->customer_country_code.' '.$this->customer_phone,
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
            <div class="mt-1.5 flex gap-2" dir="ltr">
                <div class="relative flex-shrink-0" x-data="{ ccOpen: false }">
                    <button
                        type="button"
                        x-on:click="ccOpen = !ccOpen"
                        x-on:click.outside="ccOpen = false"
                        class="flex items-center gap-1.5 rounded-lg border border-line-medium px-2 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                    >
                        @if ($customer_country_code === '+966')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#006C35" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                        @endif
                        <span>{{ $customer_country_code }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div
                        x-show="ccOpen"
                        x-cloak
                        class="absolute z-10 mt-1 w-28 overflow-hidden rounded-lg border border-line-medium bg-white shadow-lg"
                    >
                        <button type="button" wire:click="$set('customer_country_code', '+967')" x-on:click="ccOpen = false" class="flex w-full items-center gap-1.5 px-2 py-2 text-sm hover:bg-surface">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                            +967
                        </button>
                        <button type="button" wire:click="$set('customer_country_code', '+966')" x-on:click="ccOpen = false" class="flex w-full items-center gap-1.5 px-2 py-2 text-sm hover:bg-surface">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#006C35" /></svg>
                            +966
                        </button>
                    </div>
                </div>
                <input type="text" wire:model="customer_phone" placeholder="7xxxxxxxx" class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            </div>
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
