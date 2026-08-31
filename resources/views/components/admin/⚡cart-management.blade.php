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

<div class="mx-auto max-w-3xl p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">SHEIN carts</h1>

        <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 text-sm">
            @foreach (\App\Models\SheinCart::STATUSES as $status)
                <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($carts as $cart)
            <div class="rounded-lg border border-gray-100 p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>
                        <p class="text-sm text-gray-500">{{ $cart->customer_phone }}</p>
                    </div>

                    <select
                        wire:change="updateStatus({{ $cart->id }}, $event.target.value)"
                        class="rounded-lg border-gray-300 text-sm"
                    >
                        @foreach (\App\Models\SheinCart::STATUSES as $status)
                            <option value="{{ $status }}" @selected($cart->status === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <p class="mt-2 whitespace-pre-line text-xs text-gray-500">{{ $cart->cart_details }}</p>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">No {{ str_replace('_', ' ', $statusFilter) }} carts.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $carts->links() }}
    </div>
</div>
