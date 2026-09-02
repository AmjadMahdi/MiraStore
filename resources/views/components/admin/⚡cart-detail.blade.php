<?php

use App\Models\SheinCart;
use App\Models\SheinCartItem;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public SheinCart $cart;

    #[Validate('required|string|max:255')]
    public string $itemName = '';

    #[Validate('nullable|string|max:2000')]
    public string $itemLink = '';

    #[Validate('required|date')]
    public string $itemDate = '';

    public function mount(SheinCart $cart): void
    {
        $this->cart = $cart;
        $this->itemDate = now()->format('Y-m-d');
    }

    public function addItem(): void
    {
        abort_if($this->cart->is_locked, 403);

        $this->validate();

        $this->cart->items()->create([
            'name' => $this->itemName,
            'link' => $this->itemLink !== '' ? $this->itemLink : null,
            'item_date' => $this->itemDate,
        ]);

        $this->itemName = '';
        $this->itemLink = '';
        $this->itemDate = now()->format('Y-m-d');
    }

    public function deleteItem(SheinCartItem $item): void
    {
        abort_if($this->cart->is_locked, 403);
        abort_unless($item->shein_cart_id === $this->cart->id, 403);

        $item->delete();
    }

    public function toggleLock(): void
    {
        $this->cart->update(['is_locked' => ! $this->cart->is_locked]);
        $this->cart->refresh();
    }

    public function updateStatus(string $status): void
    {
        abort_unless(in_array($status, SheinCart::STATUSES, true), 422);

        $this->cart->update(['status' => $status]);
    }

    public function with(): array
    {
        return [
            'items' => $this->cart->items()->orderByDesc('item_date')->get(),
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    @php
        $statusLabels = ['open' => 'مفتوحة', 'ordered' => 'تم الطلب', 'in_transit' => 'في الطريق', 'arrived' => 'تم الوصول'];
    @endphp

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">
                {{ $cart->cart_name }}
                @if ($cart->is_locked)
                    <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">مقفلة</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-muted">{{ $cart->cart_number }} &middot; {{ $cart->customer_phone }}</p>
        </div>
        <a href="{{ route('admin.carts.index') }}" class="flex-shrink-0 text-sm text-primary underline">رجوع للسلال</a>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <select
            wire:change="updateStatus($event.target.value)"
            class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black"
        >
            @foreach (\App\Models\SheinCart::STATUSES as $status)
                <option value="{{ $status }}" @selected($cart->status === $status)>{{ $statusLabels[$status] }}</option>
            @endforeach
        </select>

        <button
            type="button"
            wire:click="toggleLock"
            @class([
                'rounded-lg border px-3 py-1.5 text-sm font-medium',
                'border-discount text-discount' => ! $cart->is_locked,
                'border-line-medium text-ink-soft' => $cart->is_locked,
            ])
        >
            {{ $cart->is_locked ? 'إلغاء القفل' : 'قفل السلة' }}
        </button>
    </div>

    @if ($cart->cart_details)
        <div class="mt-6 rounded-lg border border-line-medium p-3">
            <p class="text-xs font-semibold text-muted">الطلب الأصلي من العميل</p>
            <p class="mt-1 whitespace-pre-line text-sm text-ink-soft" dir="ltr">{{ $cart->cart_details }}</p>
        </div>
    @endif

    <div class="mt-6">
        <h2 class="text-sm font-semibold text-ink-soft">العناصر ({{ $items->count() }})</h2>

        <div class="mt-2 space-y-2">
            @forelse ($items as $item)
                <div class="flex items-center justify-between gap-3 rounded-lg border border-line-medium p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-ink">{{ $item->name }}</p>
                        @if ($item->link)
                            <p class="truncate text-xs text-muted" dir="ltr">{{ $item->link }}</p>
                        @endif
                        <p class="text-xs text-disabled">{{ $item->item_date->format('Y-m-d') }}</p>
                    </div>

                    @unless ($cart->is_locked)
                        <div x-data="{ confirming: false }" class="contents">
                            <button
                                type="button"
                                x-on:click="confirming = true"
                                class="flex-shrink-0 rounded-lg border border-discount px-2.5 py-1 text-xs font-medium text-discount"
                            >
                                حذف
                            </button>

                            <div
                                x-show="confirming"
                                x-cloak
                                x-on:click.self="confirming = false"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                            >
                                <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                    <p class="text-base font-medium text-ink">حذف "{{ $item->name }}"؟</p>
                                    <div class="mt-4 flex gap-2">
                                        <button
                                            type="button"
                                            x-on:click="confirming = false; $wire.deleteItem({{ $item->id }})"
                                            class="flex-1 rounded-lg bg-discount py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                        >
                                            حذف
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="confirming = false"
                                            class="flex-1 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink"
                                        >
                                            إلغاء
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endunless
                </div>
            @empty
                <p class="py-6 text-center text-sm text-disabled">لا توجد عناصر بعد.</p>
            @endforelse
        </div>
    </div>

    @unless ($cart->is_locked)
        <form wire:submit="addItem" class="mt-6 space-y-3 border-t border-line-medium pt-4">
            <h2 class="text-sm font-semibold text-ink-soft">إضافة عنصر</h2>

            <div>
                <label class="block text-sm font-medium text-ink-soft">الاسم</label>
                <input type="text" wire:model="itemName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('itemName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">الرابط أو الكود (اختياري)</label>
                <input type="text" wire:model="itemLink" dir="ltr" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('itemLink') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">التاريخ</label>
                <input type="date" wire:model="itemDate" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('itemDate') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="addItem"
                class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
            >
                + إضافة العنصر
            </button>
        </form>
    @endunless
</div>
