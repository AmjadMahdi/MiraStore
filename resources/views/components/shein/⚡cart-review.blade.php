<?php

use App\Models\Setting;
use App\Models\SheinCart;
use App\Support\GuestCart;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $cart_name = '';

    #[Validate('required|string|max:20|regex:/^(?=.*\d)[0-9+\s-]+$/')]
    public string $customer_phone = '';

    public ?string $confirmedCartNumber = null;

    public function mount(): void
    {
        $this->cart_name = GuestCart::cartName() ?: Setting::get('default_cart_name', 'طلبي من Shein');
        $this->customer_phone = GuestCart::customerPhone();
    }

    public function removeItem(string $id): void
    {
        GuestCart::remove($id);
        $this->dispatch('cart-updated');
    }

    public function confirmOrder(): void
    {
        $items = GuestCart::items();

        if (empty($items)) {
            return;
        }

        $this->validate();

        $cartDetails = collect($items)->map(function (array $item) {
            $line = $item['code'].' (الكمية: '.($item['quantity'] ?? 1).')';

            if (! empty($item['date'])) {
                $line .= ' — بتاريخ: '.$item['date'];
            }

            if (! empty($item['notes'])) {
                $line .= ' — ملاحظات: '.$item['notes'];
            }

            return $line;
        })->implode("\n");

        $cart = SheinCart::createWithUniqueNumber([
            'cart_name' => $this->cart_name,
            'cart_details' => $cartDetails,
            'customer_phone' => $this->customer_phone,
        ]);

        GuestCart::clear();
        $this->dispatch('cart-updated');

        $this->confirmedCartNumber = $cart->cart_number;
    }

    public function with(): array
    {
        return [
            'items' => GuestCart::items(),
        ];
    }
};
?>

<div class="mx-auto max-w-md p-6 sm:p-8">
    @if ($confirmedCartNumber)
        <div class="rounded-lg bg-green-50 p-4 text-center">
            <p class="text-sm text-success">تم إرسال طلبك! احتفظ برقم السلة لمتابعة طلبك:</p>
            <p class="mt-2 text-2xl font-bold text-green-800">{{ $confirmedCartNumber }}</p>
            <a href="{{ route('home') }}" class="mt-3 inline-block text-sm text-success underline">
                العودة إلى الرئيسية
            </a>
        </div>
    @else
        <h1 class="text-2xl font-bold tracking-tight text-ink">سلتك</h1>

        <div class="mt-4 space-y-2">
            @forelse ($items as $item)
                <div class="flex items-center justify-between gap-3 rounded-lg border border-line-medium p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm text-ink" dir="ltr">{{ $item['code'] }}</p>
                        <p class="text-xs text-muted">الكمية: {{ $item['quantity'] ?? 1 }}</p>
                        @if (! empty($item['date']))
                            <p class="text-xs text-disabled">{{ $item['date'] }}</p>
                        @endif
                        @if (! empty($item['notes']))
                            <p class="truncate text-xs text-disabled">{{ $item['notes'] }}</p>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="removeItem('{{ $item['id'] }}')"
                        class="flex-shrink-0 text-sm text-discount underline"
                    >
                        إزالة
                    </button>
                </div>
            @empty
                <div class="flex flex-col items-center py-16 text-center">
                    <svg class="h-10 w-10 text-disabled" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    <p class="mt-3 text-sm text-disabled">سلتك فارغة.</p>
                    <a href="{{ route('home') }}" class="mt-3 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover">
                        العودة للتسوق
                    </a>
                </div>
            @endforelse
        </div>

        @if (! empty($items))
            <form wire:submit="confirmOrder" class="mt-6 space-y-4 border-t border-line-medium pt-4">
                <div>
                    <label class="block text-sm font-medium text-ink-soft">اسم السلة</label>
                    <input type="text" wire:model="cart_name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                    @error('cart_name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-soft">رقم واتساب الخاص بك</label>
                    <input type="text" wire:model="customer_phone" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                    @error('customer_phone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="confirmOrder"
                    class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="confirmOrder">تأكيد الطلب</span>
                    <span wire:loading wire:target="confirmOrder">جارٍ الإرسال...</span>
                </button>
            </form>
        @endif
    @endif
</div>
