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

    public function with(): array
    {
        return [
            'carts' => SheinCart::query()
                ->where('status', $this->statusFilter)
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
                        <p class="text-sm font-medium text-ink">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>
                        <p class="text-sm text-muted">{{ $cart->customer_phone }}</p>
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
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد سلال {{ $statusLabels[$statusFilter] }}.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $carts->links() }}
    </div>
</div>
