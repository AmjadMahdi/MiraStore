<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['is_active' => ! $vendor->is_active]);
    }

    public function toggleVerified(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['is_verified' => ! $vendor->is_verified]);
    }

    public function upgradeToPremium(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['max_products_limit' => null]);
    }

    public function downgradeToBasic(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['max_products_limit' => 5]);
    }

    public function with(): array
    {
        return [
            'vendors' => User::query()
                ->where('role', 'vendor')
                ->when($this->search, fn ($query) => $query->where('store_name', 'like', "%{$this->search}%"))
                ->withCount('products')
                ->latest()
                ->paginate(10),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Vendors</h1>

        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by store name..."
            class="rounded-lg border-gray-300 text-sm"
        >
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($vendors as $vendor)
            <div class="rounded-lg border border-gray-100 p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800">
                            {{ $vendor->store_name }}
                            @if ($vendor->is_verified)
                                <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-700">Verified</span>
                            @endif
                            @unless ($vendor->is_active)
                                <span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">Suspended</span>
                            @endunless
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $vendor->email }} &middot; {{ $vendor->products_count }} products
                            &middot; limit: {{ $vendor->max_products_limit ?? 'unlimited' }}
                        </p>
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="toggleActive({{ $vendor->id }})"
                        wire:confirm="{{ $vendor->is_active ? 'Suspend this vendor?' : 'Reactivate this vendor?' }}"
                        class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700"
                    >
                        {{ $vendor->is_active ? 'Suspend' : 'Activate' }}
                    </button>

                    <button
                        type="button"
                        wire:click="toggleVerified({{ $vendor->id }})"
                        class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700"
                    >
                        {{ $vendor->is_verified ? 'Remove verified badge' : 'Mark verified' }}
                    </button>

                    @if ($vendor->max_products_limit !== null)
                        <button
                            type="button"
                            wire:click="upgradeToPremium({{ $vendor->id }})"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700"
                        >
                            Upgrade to Premium (unlimited)
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="downgradeToBasic({{ $vendor->id }})"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700"
                        >
                            Downgrade to Basic (5)
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">No vendors found.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $vendors->links() }}
    </div>
</div>
