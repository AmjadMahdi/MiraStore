<?php

use App\Models\SheinCart;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $statusFilter = 'open';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(SheinCart $cart, string $status): void
    {
        abort_unless(in_array($status, SheinCart::STATUSES, true), 422);

        $cart->update(['status' => $status]);
    }

    public function deleteCart(SheinCart $cart): void
    {
        $cart->delete();
    }

    public function with(): array
    {
        return [
            'carts' => SheinCart::query()
                ->where('status', $this->statusFilter)
                ->withCount('items')
                ->latest()
                ->paginate(10),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    @php
        $statusLabels = [
            'open' => 'مفتوحة',
            'ordered' => 'تم الطلب',
            'in_transit' => 'في الطريق',
            'arrived' => 'تم الوصول',
        ];
    @endphp

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">سلال شي إن</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.carts.create') }}" class="flex-shrink-0 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                + سلة جديدة
            </a>

            <a href="{{ route('admin.carts.main') }}" class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-1.5 text-sm font-medium text-ink-soft">
                السلة الرئيسية
            </a>

            <select wire:model.live="statusFilter" class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black">
                @foreach (\App\Models\SheinCart::STATUSES as $status)
                    <option value="{{ $status }}">{{ $statusLabels[$status] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($carts as $cart)
            <div class="rounded-lg border border-line-medium p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-ink">
                            <a href="{{ route('admin.carts.show', $cart) }}" class="underline-offset-2 hover:underline">{{ $cart->cart_name }}</a>
                            &middot; {{ $cart->cart_number }}
                            @if ($cart->is_locked)
                                <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">مقفلة</span>
                            @endif
                        </p>
                        <p class="text-sm text-muted">{{ $cart->customer_phone }} &middot; {{ $cart->items_count }} عنصر</p>
                        <p class="text-xs text-disabled">{{ $cart->created_at->format('Y-m-d') }}</p>
                    </div>

                    <select
                        wire:change="updateStatus({{ $cart->id }}, $event.target.value)"
                        class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black"
                    >
                        @foreach (\App\Models\SheinCart::STATUSES as $status)
                            <option value="{{ $status }}" @selected($cart->status === $status)>
                                {{ $statusLabels[$status] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <p class="mt-2 whitespace-pre-line text-xs text-muted">{{ $cart->cart_details }}</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a
                        href="{{ route('admin.carts.show', $cart) }}"
                        class="rounded-lg border border-line-medium px-3 py-1.5 text-xs font-medium text-ink-soft"
                    >
                        تعديل / التفاصيل
                    </a>

                    <div x-data="{ confirming: false }" class="contents">
                        <button
                            type="button"
                            x-on:click="confirming = true"
                            class="rounded-lg border border-discount px-3 py-1.5 text-xs font-medium text-discount"
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
                                <p class="text-base font-medium text-ink">حذف سلة "{{ $cart->cart_name }}" نهائياً؟</p>
                                <p class="mt-1 text-sm text-muted">سيتم حذف كل عناصرها ولا يمكن التراجع عن هذا.</p>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        type="button"
                                        x-on:click="confirming = false; $wire.deleteCart({{ $cart->id }})"
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
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد سلال {{ $statusLabels[$statusFilter] }}.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $carts->links() }}
    </div>
</div>
