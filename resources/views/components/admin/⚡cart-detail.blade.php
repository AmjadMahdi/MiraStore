<?php

use App\Models\SheinCart;
use App\Models\SheinCartItem;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public SheinCart $cart;

    public string $itemName = '';

    public string $itemQuantity = '1';

    public string $itemLink = '';

    public string $itemDate = '';

    public ?int $editingItemId = null;

    public bool $editingCartDetails = false;

    public bool $itemJustAdded = false;

    public string $editCartName = '';

    public string $editDescription = '';

    public string $editCustomerCountryCode = '+967';

    public string $editCustomerPhone = '';

    public function mount(SheinCart $cart): void
    {
        $this->cart = $cart;
        $this->itemDate = now()->format('Y-m-d\TH:i');
    }

    public function addItem(): void
    {
        abort_if($this->cart->is_locked, 403);

        $this->validate([
            'itemName' => ['nullable', 'string', 'max:255'],
            'itemQuantity' => ['required', 'integer', 'min:1'],
            'itemLink' => ['required', 'string', 'max:2000'],
            'itemDate' => ['required', 'date'],
        ]);

        $this->cart->items()->create([
            'name' => $this->itemName !== '' ? $this->itemName : null,
            'quantity' => (int) $this->itemQuantity,
            'link' => $this->itemLink !== '' ? $this->itemLink : null,
            'item_date' => $this->itemDate,
        ]);

        $this->itemName = '';
        $this->itemQuantity = '1';
        $this->itemLink = '';
        $this->itemDate = now()->format('Y-m-d\TH:i');
        $this->itemJustAdded = true;
    }

    public function startEditItem(SheinCartItem $item): void
    {
        abort_if($this->cart->is_locked, 403);
        abort_unless($item->shein_cart_id === $this->cart->id, 403);

        $this->editingItemId = $item->id;
        $this->itemName = (string) $item->name;
        $this->itemQuantity = (string) $item->quantity;
        $this->itemLink = (string) $item->link;
        $this->itemDate = $item->item_date->format('Y-m-d\TH:i');
    }

    public function cancelEditItem(): void
    {
        $this->editingItemId = null;
        $this->itemName = '';
        $this->itemQuantity = '1';
        $this->itemLink = '';
        $this->itemDate = now()->format('Y-m-d\TH:i');
    }

    public function updateItem(): void
    {
        abort_if($this->cart->is_locked, 403);

        $item = SheinCartItem::findOrFail($this->editingItemId);
        abort_unless($item->shein_cart_id === $this->cart->id, 403);

        $this->validate([
            'itemName' => ['nullable', 'string', 'max:255'],
            'itemQuantity' => ['required', 'integer', 'min:1'],
            'itemLink' => ['required', 'string', 'max:2000'],
            'itemDate' => ['required', 'date'],
        ]);

        $item->update([
            'name' => $this->itemName !== '' ? $this->itemName : null,
            'quantity' => (int) $this->itemQuantity,
            'link' => $this->itemLink !== '' ? $this->itemLink : null,
            'item_date' => $this->itemDate,
        ]);

        $this->cancelEditItem();
    }

    public function deleteItem(SheinCartItem $item): void
    {
        abort_if($this->cart->is_locked, 403);
        abort_unless($item->shein_cart_id === $this->cart->id, 403);

        $item->delete();
    }

    public function startEditCartDetails(): void
    {
        $this->editingCartDetails = true;
        $this->editCartName = $this->cart->cart_name;
        $this->editDescription = (string) $this->cart->description;

        $phone = $this->cart->customer_phone;

        if (preg_match('/^(\+967|\+966)\s*(.*)$/', trim($phone), $matches)) {
            $this->editCustomerCountryCode = $matches[1];
            $this->editCustomerPhone = trim($matches[2]);
        } else {
            $this->editCustomerCountryCode = '+967';
            $this->editCustomerPhone = trim($phone);
        }
    }

    public function cancelEditCartDetails(): void
    {
        $this->editingCartDetails = false;
    }

    public function updateCartDetails(): void
    {
        $this->validate([
            'editCartName' => ['required', 'string', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
            'editCustomerCountryCode' => ['required', 'in:+967,+966'],
            'editCustomerPhone' => ['required', 'string', 'max:15', 'regex:/^[0-9\s-]+$/'],
        ]);

        $this->cart->update([
            'cart_name' => $this->editCartName,
            'description' => $this->editDescription !== '' ? $this->editDescription : null,
            'customer_phone' => $this->editCustomerCountryCode.' '.$this->editCustomerPhone,
        ]);

        $this->editingCartDetails = false;
    }

    public function deleteCart(): void
    {
        $this->cart->delete();

        $this->redirect(route('admin.carts.index'), navigate: true);
    }

    public function toggleLock(): void
    {
        $this->cart->update(['is_locked' => ! $this->cart->is_locked]);
        $this->cart->refresh();
    }

    public function togglePublicLink(): void
    {
        if ($this->cart->public_token) {
            $this->cart->disablePublicLink();
        } else {
            $this->cart->enablePublicLink();
        }

        $this->cart->refresh();
    }

    public function toggleAcceptsSubmissions(): void
    {
        if ($this->cart->accepts_submissions) {
            $this->cart->disableSubmissions();
        } else {
            $this->cart->enableSubmissions();
        }

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
            'whatsappLink' => 'https://wa.me/'.preg_replace('/\D/', '', $this->cart->customer_phone),
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
        @if ($editingCartDetails)
            <form wire:submit="updateCartDetails" class="flex-1 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-ink-soft">اسم السلة</label>
                    <input type="text" wire:model="editCartName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                    @error('editCartName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-soft">الوصف (اختياري)</label>
                    <textarea wire:model="editDescription" rows="2" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"></textarea>
                    @error('editDescription') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-ink-soft">رقم واتساب مدير السلة (لشراء وتوصيل الطلبات)</label>
                    <div class="mt-1.5 flex gap-2" dir="ltr">
                        <div class="relative flex-shrink-0" x-data="{ ccOpen: false }">
                            <button
                                type="button"
                                x-on:click="ccOpen = !ccOpen"
                                x-on:click.outside="ccOpen = false"
                                class="flex items-center gap-1.5 rounded-lg border border-line-medium px-2 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
                            >
                                @if ($editCustomerCountryCode === '+966')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#006C35" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                                @endif
                                <span>{{ $editCustomerCountryCode }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </button>

                            <div
                                x-show="ccOpen"
                                x-cloak
                                class="absolute z-10 mt-1 w-28 overflow-hidden rounded-lg border border-line-medium bg-white shadow-lg"
                            >
                                <button type="button" wire:click="$set('editCustomerCountryCode', '+967')" x-on:click="ccOpen = false" class="flex w-full items-center gap-1.5 px-2 py-2 text-sm hover:bg-surface">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#fff" /><rect width="24" height="5.33" fill="#CE1126" /><rect y="10.67" width="24" height="5.33" fill="#000" /></svg>
                                    +967
                                </button>
                                <button type="button" wire:click="$set('editCustomerCountryCode', '+966')" x-on:click="ccOpen = false" class="flex w-full items-center gap-1.5 px-2 py-2 text-sm hover:bg-surface">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 16" class="h-3.5 w-5 flex-shrink-0 rounded-sm"><rect width="24" height="16" fill="#006C35" /></svg>
                                    +966
                                </button>
                            </div>
                        </div>
                        <input type="text" wire:model="editCustomerPhone" placeholder="7xxxxxxxx" class="w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                    </div>
                    @error('editCustomerCountryCode') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                    @error('editCustomerPhone') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-hover">حفظ</button>
                    <button type="button" wire:click="cancelEditCartDetails" class="rounded-lg border border-line-medium px-3 py-1.5 text-sm font-medium text-ink">إلغاء</button>
                </div>
            </form>
        @else
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight text-ink">
                    {{ $cart->cart_name }}
                    @if ($cart->is_locked)
                        <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">مقفلة</span>
                    @endif
                </h1>
                <p class="mt-1 text-sm text-muted">
                    {{ $cart->cart_number }} &middot;
                    <a href="{{ $whatsappLink }}" target="_blank" class="text-primary underline" dir="ltr">{{ $cart->customer_phone }}</a>
                </p>
                <p class="mt-1 text-xs text-disabled">تاريخ الإنشاء: {{ $cart->created_at->format('Y-m-d H:i') }}</p>
                @if ($cart->description)
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-soft">{{ $cart->description }}</p>
                @endif
            </div>

            <div class="flex flex-shrink-0 items-center gap-3">
                <button type="button" wire:click="startEditCartDetails" class="text-sm text-primary underline">تعديل</button>
                <a href="{{ route('admin.carts.index') }}" class="text-sm text-primary underline">رجوع للسلال</a>
            </div>
        @endif
    </div>

    @unless ($editingCartDetails)
        <div x-data="{ confirming: false }" class="mt-3">
            <button
                type="button"
                x-on:click="confirming = true"
                class="rounded-lg border border-discount px-3 py-1.5 text-sm font-medium text-discount"
            >
                حذف السلة نهائياً
            </button>

            <div
                x-show="confirming"
                x-cloak
                x-on:click.self="confirming = false"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                    <p class="text-base font-medium text-ink">حذف سلة "{{ $cart->cart_name }}" نهائياً؟</p>
                    <p class="mt-1 text-sm text-muted">سيتم حذف كل عناصرها ولا يمكن التراجع عن هذا.</p>
                    <div class="mt-4 flex gap-2">
                        <button
                            type="button"
                            wire:click="deleteCart"
                            class="flex-1 rounded-lg bg-discount py-2 text-sm font-semibold text-white transition hover:opacity-90"
                        >
                            حذف نهائياً
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

        <button
            type="button"
            wire:click="togglePublicLink"
            class="rounded-lg border border-line-medium px-3 py-1.5 text-sm font-medium text-ink-soft"
        >
            {{ $cart->public_token ? 'إلغاء الرابط العام' : 'إنشاء رابط عام' }}
        </button>

        <button
            type="button"
            wire:click="toggleAcceptsSubmissions"
            @class([
                'rounded-lg border px-3 py-1.5 text-sm font-medium',
                'border-primary bg-primary text-white' => $cart->accepts_submissions,
                'border-line-medium text-ink-soft' => ! $cart->accepts_submissions,
            ])
        >
            {{ $cart->accepts_submissions ? 'تستقبل روابط الزوار الآن ✓' : 'استقبال روابط الزوار من الصفحة الرئيسية' }}
        </button>
    </div>

    @if ($cart->accepts_submissions)
        <p class="mt-2 text-xs text-muted">هذه السلة الوحيدة التي تستقبل روابط الزوار حالياً — تفعيل سلة أخرى يوقف الاستقبال هنا تلقائياً.</p>
    @endif

    @if ($cart->public_token)
        <div
            x-data="{ copied: false }"
            class="mt-3 flex items-center gap-2 rounded-lg bg-surface p-3"
        >
            <p class="min-w-0 flex-1 truncate text-xs text-ink-soft" dir="ltr">{{ route('shein.public-cart', $cart->public_token) }}</p>
            <button
                type="button"
                x-on:click="
                    navigator.clipboard.writeText(@js(route('shein.public-cart', $cart->public_token)));
                    copied = true;
                    setTimeout(() => copied = false, 1500);
                "
                class="flex-shrink-0 rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
            >
                <span x-show="!copied">نسخ الرابط</span>
                <span x-show="copied" x-cloak>تم النسخ ✓</span>
            </button>
        </div>
    @endif

    @if ($cart->cart_details)
        <div class="mt-6 rounded-lg border border-line-medium p-3">
            <p class="text-xs font-semibold text-muted">الطلب الأصلي من العميل</p>
            <p class="mt-1 whitespace-pre-line text-sm text-ink-soft" dir="ltr">{{ $cart->cart_details }}</p>
        </div>
    @endif

    <div class="mt-6" x-data="{ selected: [], all: @js($items->pluck('id')) }">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-ink-soft">العناصر ({{ $items->count() }})</h2>

            @if ($items->isNotEmpty())
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1.5 text-xs font-medium text-ink-soft">
                        <input
                            type="checkbox"
                            x-on:change="selected = $event.target.checked ? [...all] : []"
                            :checked="selected.length === all.length"
                            class="h-4 w-4 rounded border-line-medium"
                        >
                        تحديد الكل
                    </label>

                    <a
                        :href="`{{ route('admin.carts.export-items', $cart) }}?ids=${selected.join(',')}`"
                        target="_blank"
                        :class="selected.length === 0 ? 'pointer-events-none opacity-40' : ''"
                        class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-hover"
                    >
                        تنزيل Excel (<span x-text="selected.length"></span>)
                    </a>
                </div>
            @endif
        </div>

        <div class="mt-2 space-y-2">
            @forelse ($items as $item)
                <div class="rounded-lg border border-line-medium p-3">
                    @if ($editingItemId === $item->id)
                        <form wire:submit="updateItem" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-ink-soft">الوصف (اختياري)</label>
                                <input type="text" wire:model="itemName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('itemName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ink-soft">الرابط أو الكود</label>
                                <input type="text" wire:model="itemLink" dir="ltr" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('itemLink') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ink-soft">الكمية</label>
                                <input type="number" min="1" wire:model="itemQuantity" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('itemQuantity') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-ink-soft">التاريخ والوقت</label>
                                <input type="datetime-local" wire:model="itemDate" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('itemDate') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-hover">حفظ</button>
                                <button type="button" wire:click="cancelEditItem" class="rounded-lg border border-line-medium px-3 py-1.5 text-xs font-medium text-ink">إلغاء</button>
                            </div>
                        </form>
                    @else
                        <div x-data="{ viewing: false, copied: false }" class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-2">
                                <input
                                    type="checkbox"
                                    value="{{ $item->id }}"
                                    x-model="selected"
                                    class="mt-1 h-4 w-4 flex-shrink-0 rounded border-line-medium"
                                >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">
                                        {{ $item->name ?? 'بدون وصف' }}
                                        <span class="ms-1 rounded bg-surface px-1.5 py-0.5 text-xs font-medium text-muted">الكمية: {{ $item->quantity }}</span>
                                    </p>
                                    @if ($item->link)
                                        <div class="mt-0.5 flex items-center gap-1.5">
                                            <p class="min-w-0 flex-1 truncate text-xs text-muted" dir="ltr">{{ $item->link }}</p>
                                            <button
                                                type="button"
                                                x-on:click="
                                                    navigator.clipboard.writeText(@js($item->link));
                                                    copied = true;
                                                    setTimeout(() => copied = false, 1500);
                                                "
                                                class="flex-shrink-0 text-muted hover:text-ink"
                                                aria-label="نسخ الرابط"
                                            >
                                                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 4h8a2 2 0 012 2v8a2 2 0 01-2 2h-8a2 2 0 01-2-2v-8a2 2 0 012-2z" />
                                                </svg>
                                                <svg x-show="copied" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                    <p class="text-xs text-disabled">{{ $item->item_date->format('Y-m-d H:i') }}</p>
                                </div>
                            </div>

                            <div class="flex flex-shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    x-on:click="viewing = true"
                                    class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                                >
                                    عرض
                                </button>

                                <div
                                    x-show="viewing"
                                    x-cloak
                                    x-on:click.self="viewing = false"
                                    x-on:keydown.escape.window="viewing = false"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                                >
                                    <div class="w-full max-w-md rounded-2xl bg-white p-6 text-start shadow-xl">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-bold text-ink">{{ $item->name ?? 'بدون وصف' }}</h3>
                                            <button type="button" x-on:click="viewing = false" class="text-muted hover:text-ink" aria-label="إغلاق">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="mt-4 space-y-3 text-sm">
                                            @if ($item->link)
                                                <div>
                                                    <p class="text-xs font-medium text-ink-soft">الرابط</p>
                                                    <div class="mt-1 flex items-center gap-2 rounded-lg bg-surface p-2.5">
                                                        <p class="min-w-0 flex-1 break-all text-xs text-ink" dir="ltr">{{ $item->link }}</p>
                                                        <button
                                                            type="button"
                                                            x-on:click="
                                                                navigator.clipboard.writeText(@js($item->link));
                                                                copied = true;
                                                                setTimeout(() => copied = false, 1500);
                                                            "
                                                            class="flex-shrink-0 rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                                                        >
                                                            <span x-show="!copied">نسخ</span>
                                                            <span x-show="copied" x-cloak>تم النسخ ✓</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="flex items-center justify-between rounded-lg bg-surface p-2.5">
                                                <span class="text-xs font-medium text-ink-soft">الكمية</span>
                                                <span class="text-xs text-ink">{{ $item->quantity }}</span>
                                            </div>

                                            @if ($item->customer_phone)
                                                <div class="flex items-center justify-between rounded-lg bg-surface p-2.5">
                                                    <span class="text-xs font-medium text-ink-soft">رقم واتساب العميل</span>
                                                    <span class="text-xs text-ink" dir="ltr">{{ $item->customer_phone }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center justify-between rounded-lg bg-surface p-2.5">
                                                <span class="text-xs font-medium text-ink-soft">التاريخ والوقت</span>
                                                <span class="text-xs text-ink">{{ $item->item_date->format('Y-m-d H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @unless ($cart->is_locked)
                                    <button
                                        type="button"
                                        wire:click="startEditItem({{ $item->id }})"
                                        class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                                    >
                                        تعديل
                                    </button>

                                    <div x-data="{ confirming: false }" class="contents">
                                        <button
                                            type="button"
                                            x-on:click="confirming = true"
                                            class="rounded-lg border border-discount px-2.5 py-1 text-xs font-medium text-discount"
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
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-disabled">لا توجد عناصر بعد.</p>
            @endforelse
        </div>
    </div>

    @unless ($cart->is_locked || $editingItemId)
        <div x-data="{ open: false }" class="mt-6 border-t border-line-medium pt-4">
            <button
                type="button"
                x-on:click="open = true"
                class="w-full rounded-lg border border-line-medium py-2.5 text-sm font-semibold text-ink-soft transition hover:border-black hover:text-ink"
            >
                + إضافة عنصر
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
                        <h2 class="text-lg font-bold text-ink">إضافة عنصر</h2>
                        <button type="button" x-on:click="open = false" class="text-muted hover:text-ink" aria-label="إغلاق">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        @if ($itemJustAdded)
                            <div class="rounded-lg border border-green-300 bg-green-50 p-3" wire:poll.4s="$set('itemJustAdded', false)">
                                <p class="text-sm font-semibold text-green-700">تمت الإضافة بنجاح ✓</p>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="rounded-lg border border-discount bg-discount-light p-3">
                                <p class="text-sm font-semibold text-discount">تعذّرت الإضافة، يرجى تصحيح ما يلي:</p>
                                <ul class="mt-1 list-inside list-disc text-sm text-discount">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">الوصف (اختياري)</label>
                            <input type="text" wire:model="itemName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                            @error('itemName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">الرابط أو الكود</label>
                            <input type="text" wire:model="itemLink" dir="ltr" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                            @error('itemLink') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">الكمية</label>
                            <input type="number" min="1" wire:model="itemQuantity" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                            @error('itemQuantity') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-ink-soft">التاريخ والوقت</label>
                            <input type="datetime-local" wire:model="itemDate" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                            @error('itemDate') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>

                        <button
                            type="button"
                            wire:click="addItem"
                            wire:loading.attr="disabled"
                            wire:target="addItem"
                            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="addItem">+ إضافة العنصر</span>
                            <span wire:loading wire:target="addItem">جارٍ الإضافة...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endunless
</div>
