<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public ?int $rejectingProductId = null;

    public string $rejectionReason = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(Product $product): void
    {
        $product->update(['status' => 'approved', 'rejection_reason' => null]);
    }

    public function startReject(int $productId): void
    {
        $this->rejectingProductId = $productId;
        $this->rejectionReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingProductId = null;
        $this->rejectionReason = '';
    }

    public function confirmReject(): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:500']);

        Product::findOrFail($this->rejectingProductId)->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason,
        ]);

        $this->cancelReject();
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->where('status', $this->statusFilter)
                ->with('vendor')
                ->latest()
                ->paginate(10),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Product moderation</h1>

        <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($products as $product)
            <div class="rounded-lg border border-gray-100 p-3">
                <div class="flex items-center gap-3">
                    <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                        <img src="{{ Storage::url($product->image_path) }}" class="h-full w-full object-cover">
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500">{{ $product->vendor->store_name }} &middot; {{ number_format($product->price, 2) }}</p>
                    </div>

                    @if ($product->status === 'pending')
                        <button
                            type="button"
                            wire:click="approve({{ $product->id }})"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white"
                        >
                            Approve
                        </button>

                        <button
                            type="button"
                            wire:click="startReject({{ $product->id }})"
                            class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white"
                        >
                            Reject
                        </button>
                    @endif
                </div>

                @if ($rejectingProductId === $product->id)
                    <div class="mt-3 border-t border-gray-100 pt-3">
                        <label class="block text-sm font-medium text-gray-700">Rejection reason</label>
                        <input type="text" wire:model="rejectionReason" placeholder="e.g. Image quality too low" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        @error('rejectionReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="confirmReject" class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white">
                                Confirm rejection
                            </button>
                            <button type="button" wire:click="cancelReject" class="text-sm text-gray-500 underline">
                                Cancel
                            </button>
                        </div>
                    </div>
                @endif

                @if ($product->status === 'rejected' && $product->rejection_reason)
                    <p class="mt-2 text-xs text-red-600">Rejected: {{ $product->rejection_reason }}</p>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">No {{ $statusFilter }} products.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
