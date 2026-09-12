<?php

use App\Models\SheinCart;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public const ALL_STATUSES_FILTER = 'all';

    public string $statusFilter = self::ALL_STATUSES_FILTER;

    public ?int $confirmingActivationForCartId = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(SheinCart $cart, string $status): void
    {
        abort_unless(in_array($status, SheinCart::statuses(), true), 422);

        $cart->update(['status' => $status]);
    }

    public function deleteCart(SheinCart $cart): void
    {
        $cart->delete();
    }

    public function toggleAcceptsSubmissions(SheinCart $cart): void
    {
        if ($cart->accepts_submissions) {
            $cart->disableSubmissions();

            return;
        }

        $otherActiveCart = $this->otherActiveCart($cart);

        if (! $otherActiveCart) {
            $cart->enableSubmissions();

            return;
        }

        // Switching the active cart while another one is already live is
        // consequential enough (it silently stops link submissions on the
        // old cart) that it needs an explicit confirmation rather than
        // happening automatically on a single click.
        $this->confirmingActivationForCartId = $cart->id;
    }

    public function cancelActivationConfirm(): void
    {
        $this->confirmingActivationForCartId = null;
    }

    public function confirmActivation(): void
    {
        $cart = SheinCart::findOrFail($this->confirmingActivationForCartId);

        $cart->enableSubmissions();
        $this->cancelActivationConfirm();
    }

    protected function otherActiveCart(SheinCart $cart): ?SheinCart
    {
        return SheinCart::where('accepts_submissions', true)
            ->where('id', '!=', $cart->id)
            ->first();
    }

    public function with(): array
    {
        return [
            'carts' => SheinCart::query()
                ->when($this->statusFilter !== self::ALL_STATUSES_FILTER, fn ($query) => $query->where('status', $this->statusFilter))
                ->withCount('items')
                ->latest()
                ->paginate(10),
            'confirmingCart' => $this->confirmingActivationForCartId
                ? SheinCart::find($this->confirmingActivationForCartId)
                : null,
            'confirmingOtherActiveCart' => $this->confirmingActivationForCartId
                ? $this->otherActiveCart(SheinCart::findOrFail($this->confirmingActivationForCartId))
                : null,
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    @php
        $statusLabels = \App\Models\SheinCart::statusLabels();
    @endphp

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">سلال Shein</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.carts.create') }}" class="flex-shrink-0 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                + سلة جديدة
            </a>

            <select wire:model.live="statusFilter" class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black">
                <option value="{{ static::ALL_STATUSES_FILTER }}">الكل</option>
                @foreach (\App\Models\SheinCart::statuses() as $status)
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
                            @if ($cart->accepts_submissions)
                                <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">تستقبل روابط الزوار</span>
                            @endif
                        </p>
                        <p class="text-sm text-muted">{{ $cart->customer_phone }} &middot; {{ $cart->items_count }} عنصر</p>
                        <p class="text-xs text-disabled">{{ $cart->created_at->format('Y-m-d') }}</p>
                    </div>

                    <select
                        wire:change="updateStatus({{ $cart->id }}, $event.target.value)"
                        class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black"
                    >
                        @foreach (\App\Models\SheinCart::statuses() as $status)
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

                    <button
                        type="button"
                        wire:click="toggleAcceptsSubmissions({{ $cart->id }})"
                        @class([
                            'rounded-lg border px-3 py-1.5 text-xs font-medium',
                            'border-primary bg-primary text-white' => $cart->accepts_submissions,
                            'border-line-medium text-ink-soft' => ! $cart->accepts_submissions,
                        ])
                    >
                        {{ $cart->accepts_submissions ? 'إيقاف الاستقبال' : 'تفعيل' }}
                    </button>

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
            <p class="py-10 text-center text-sm text-disabled">لا توجد سلال {{ $statusFilter === static::ALL_STATUSES_FILTER ? '' : $statusLabels[$statusFilter] }}.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $carts->links() }}
    </div>

    @if ($confirmingCart)
        <div
            x-data
            x-on:keydown.escape.window="$wire.cancelActivationConfirm()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                <p class="text-base font-medium text-ink">هل أنت متأكد من إيقاف استقبال الروابط في سلة "{{ $confirmingOtherActiveCart->cart_name }}"؟</p>
                <p class="mt-1 text-sm text-muted">
                    سيتم إيقاف الاستقبال في سلة "{{ $confirmingOtherActiveCart->cart_name }}" وتفعيله في سلة "{{ $confirmingCart->cart_name }}".
                </p>

                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        wire:click="confirmActivation"
                        class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary-hover"
                    >
                        تأكيد التفعيل
                    </button>
                    <button
                        type="button"
                        wire:click="cancelActivationConfirm"
                        class="flex-1 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink"
                    >
                        إلغاء
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
