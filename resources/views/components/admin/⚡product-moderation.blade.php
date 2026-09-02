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

    public ?int $previewingProductId = null;

    /** @var array<int, int> */
    public array $selected = [];

    public bool $bulkRejecting = false;

    public string $bulkRejectionReason = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function openPreview(int $productId): void
    {
        $this->previewingProductId = $productId;
    }

    public function closePreview(): void
    {
        $this->previewingProductId = null;
        $this->rejectingProductId = null;
        $this->rejectionReason = '';
    }

    public function approve(Product $product): void
    {
        $product->update(['status' => 'approved', 'rejection_reason' => null]);

        if ($this->previewingProductId === $product->id) {
            $this->closePreview();
        }
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

        if ($this->previewingProductId === $this->rejectingProductId) {
            $this->closePreview();

            return;
        }

        $this->cancelReject();
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();

        if ($this->previewingProductId === $product->id) {
            $this->closePreview();
        }
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->currentPageProductIds();

        if (empty(array_diff($ids, $this->selected))) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
        }
    }

    public function bulkApprove(): void
    {
        Product::whereIn('id', $this->selected)->update(['status' => 'approved', 'rejection_reason' => null]);

        $this->selected = [];
    }

    public function startBulkReject(): void
    {
        $this->bulkRejecting = true;
        $this->bulkRejectionReason = '';
    }

    public function cancelBulkReject(): void
    {
        $this->bulkRejecting = false;
        $this->bulkRejectionReason = '';
    }

    public function confirmBulkReject(): void
    {
        $this->validate(['bulkRejectionReason' => 'required|string|max:500']);

        Product::whereIn('id', $this->selected)->update([
            'status' => 'rejected',
            'rejection_reason' => $this->bulkRejectionReason,
        ]);

        $this->selected = [];
        $this->bulkRejecting = false;
        $this->bulkRejectionReason = '';
    }

    protected function currentPageProductIds(): array
    {
        return Product::query()
            ->where('status', $this->statusFilter)
            ->latest()
            ->paginate(10)
            ->pluck('id')
            ->all();
    }

    public function with(): array
    {
        $products = Product::query()
            ->where('status', $this->statusFilter)
            ->with('vendor')
            ->latest()
            ->paginate(10);

        $pageIds = $products->pluck('id')->all();

        return [
            'products' => $products,
            'allOnPageSelected' => count($pageIds) > 0 && empty(array_diff($pageIds, $this->selected)),
            'previewingProduct' => $this->previewingProductId
                ? Product::with(['vendor', 'category'])->find($this->previewingProductId)
                : null,
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    @php
        $statusLabels = ['pending' => 'قيد المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض'];
        $stockLabels = ['in_stock' => 'متوفر', 'pre_order' => 'طلب مسبق', 'out_of_stock' => 'نفدت الكمية'];
    @endphp

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">مراجعة المنتجات</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.order') }}" class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-1.5 text-sm font-medium text-ink-soft">
                ترتيب العرض
            </a>

            <select wire:model.live="statusFilter" class="rounded-lg border border-line-medium text-sm">
                <option value="pending">قيد المراجعة</option>
                <option value="approved">معتمد</option>
                <option value="rejected">مرفوض</option>
            </select>
        </div>
    </div>

    @if ($products->isNotEmpty())
        <div class="mt-6 flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input
                    type="checkbox"
                    wire:click="toggleSelectAll"
                    @checked($allOnPageSelected)
                    class="h-4 w-4 rounded border border-line-medium"
                >
                تحديد الكل
            </label>

            @if (count($selected) > 0)
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted">{{ count($selected) }} محدد</span>

                    <button
                        type="button"
                        wire:click="bulkApprove"
                        class="rounded-lg bg-success px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        اعتماد المحدد
                    </button>

                    <button
                        type="button"
                        wire:click="startBulkReject"
                        class="rounded-lg bg-discount px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        رفض المحدد
                    </button>
                </div>
            @endif
        </div>

        @if ($bulkRejecting)
            <div class="mt-3 rounded-lg border border-line-medium p-3">
                <label class="block text-sm font-medium text-ink-soft">سبب رفض المنتجات المحددة</label>
                <input type="text" wire:model="bulkRejectionReason" placeholder="مثال: جودة الصورة منخفضة" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('bulkRejectionReason') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror

                <div class="mt-2 flex gap-2">
                    <button type="button" wire:click="confirmBulkReject" class="rounded-lg bg-discount px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90">
                        تأكيد الرفض
                    </button>
                    <button type="button" wire:click="cancelBulkReject" class="text-sm text-muted underline">
                        إلغاء
                    </button>
                </div>
            </div>
        @endif
    @endif

    <div class="mt-4 space-y-3">
        @forelse ($products as $product)
            <div class="rounded-lg border border-line-medium p-3">
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model.live="selected"
                        value="{{ $product->id }}"
                        class="h-4 w-4 flex-shrink-0 rounded border border-line-medium"
                    >

                    <button
                        type="button"
                        wire:click="openPreview({{ $product->id }})"
                        class="flex min-w-0 flex-1 items-center gap-3 text-start"
                    >
                        <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-surface">
                            <img src="{{ Storage::url($product->image_path) }}" class="h-full w-full object-cover">
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink underline-offset-2 hover:underline">{{ $product->name }}</p>
                            <p class="text-sm text-muted">{{ $product->vendor->store_name }} &middot; {{ number_format($product->price, 2) }}</p>
                        </div>
                    </button>
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                    <a
                        href="{{ route('admin.products.edit', $product) }}"
                        class="rounded-lg border border-line-medium px-3 py-1.5 text-sm font-medium text-ink-soft"
                    >
                        تعديل
                    </a>

                    @if ($product->status !== 'approved')
                        <button
                            type="button"
                            wire:click="approve({{ $product->id }})"
                            class="rounded-lg bg-success px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90"
                        >
                            اعتماد
                        </button>
                    @endif

                    @if ($product->status !== 'rejected')
                        <button
                            type="button"
                            wire:click="startReject({{ $product->id }})"
                            class="rounded-lg bg-discount px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90"
                        >
                            رفض
                        </button>
                    @endif

                    <div x-data="{ confirming: false }" class="contents">
                        <button
                            type="button"
                            x-on:click="confirming = true"
                            class="rounded-lg border border-discount px-3 py-1.5 text-sm font-medium text-discount"
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
                                <p class="text-base font-medium text-ink">حذف هذا المنتج؟</p>
                                <p class="mt-1 text-sm text-muted">لن يظهر المنتج بعد الآن للعملاء.</p>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        type="button"
                                        x-on:click="confirming = false; $wire.deleteProduct({{ $product->id }})"
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
                </div>

                @if ($rejectingProductId === $product->id && $previewingProductId !== $product->id)
                    <div class="mt-3 border-t border-line-medium pt-3">
                        <label class="block text-sm font-medium text-ink-soft">سبب الرفض</label>
                        <input type="text" wire:model="rejectionReason" placeholder="مثال: جودة الصورة منخفضة" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                        @error('rejectionReason') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror

                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="confirmReject" class="rounded-lg bg-discount px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90">
                                تأكيد الرفض
                            </button>
                            <button type="button" wire:click="cancelReject" class="text-sm text-muted underline">
                                إلغاء
                            </button>
                        </div>
                    </div>
                @endif

                @if ($product->status === 'rejected' && $product->rejection_reason)
                    <p class="mt-2 text-xs text-discount">تم الرفض: {{ $product->rejection_reason }}</p>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد منتجات {{ $statusLabels[$statusFilter] }}.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    {{-- Preview modal --}}
    @if ($previewingProduct)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="closePreview">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl">
                <div class="aspect-square w-full bg-surface">
                    <img src="{{ Storage::url($previewingProduct->image_path) }}" alt="{{ $previewingProduct->name }}" class="h-full w-full object-cover">
                </div>

                <div class="p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-muted">
                                {{ $previewingProduct->vendor->store_name }}
                                @if ($previewingProduct->vendor->is_verified)
                                    <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">موثّق</span>
                                @endif
                            </p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-ink">{{ $previewingProduct->name }}</h2>
                            @if ($previewingProduct->category)
                                <span class="mt-1 inline-block rounded bg-surface px-2 py-0.5 text-xs font-medium text-muted">{{ $previewingProduct->category->name }}</span>
                            @endif
                        </div>

                        <span @class([
                            'flex-shrink-0 rounded px-2 py-0.5 text-xs font-medium',
                            'bg-amber-50 text-warning' => $previewingProduct->status === 'pending',
                            'bg-green-50 text-success' => $previewingProduct->status === 'approved',
                            'bg-discount-light text-discount' => $previewingProduct->status === 'rejected',
                        ])>
                            {{ $statusLabels[$previewingProduct->status] }}
                        </span>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-lg font-semibold text-primary">{{ number_format($previewingProduct->price, 2) }}</span>
                        @if ($previewingProduct->compare_at_price)
                            <span class="text-sm text-disabled line-through">{{ number_format($previewingProduct->compare_at_price, 2) }}</span>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-muted">
                        حالة المخزون: <span class="font-medium text-ink-soft">{{ $stockLabels[$previewingProduct->stock_status] }}</span>
                    </p>

                    @if ($previewingProduct->options)
                        <p class="mt-2 text-sm text-ink-soft">{{ $previewingProduct->options }}</p>
                    @endif

                    <p class="mt-4 whitespace-pre-line text-sm text-ink-soft">{{ $previewingProduct->description }}</p>

                    <p class="mt-4 text-xs text-disabled">
                        تاريخ الإرسال: {{ $previewingProduct->created_at->format('Y-m-d H:i') }}
                    </p>

                    @if ($previewingProduct->status === 'rejected' && $previewingProduct->rejection_reason)
                        <p class="mt-3 rounded-lg bg-discount-light p-3 text-sm text-discount">
                            تم الرفض: {{ $previewingProduct->rejection_reason }}
                        </p>
                    @endif

                    @if ($rejectingProductId === $previewingProduct->id)
                        <div class="mt-4 border-t border-line-medium pt-4">
                            <label class="block text-sm font-medium text-ink-soft">سبب الرفض</label>
                            <input type="text" wire:model="rejectionReason" placeholder="مثال: جودة الصورة منخفضة" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                            @error('rejectionReason') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror

                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="confirmReject" class="rounded-lg bg-discount px-3 py-1.5 text-sm font-semibold text-white transition hover:opacity-90">
                                    تأكيد الرفض
                                </button>
                                <button type="button" wire:click="cancelReject" class="text-sm text-muted underline">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-line-medium pt-4">
                            <a
                                href="{{ route('admin.products.edit', $previewingProduct) }}"
                                class="flex-1 rounded-lg border border-line-medium py-2.5 text-center text-sm font-medium text-ink-soft"
                            >
                                تعديل
                            </a>

                            @if ($previewingProduct->status !== 'approved')
                                <button
                                    type="button"
                                    wire:click="approve({{ $previewingProduct->id }})"
                                    class="flex-1 rounded-lg bg-success py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                                >
                                    اعتماد
                                </button>
                            @endif

                            @if ($previewingProduct->status !== 'rejected')
                                <button
                                    type="button"
                                    wire:click="startReject({{ $previewingProduct->id }})"
                                    class="flex-1 rounded-lg bg-discount py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                                >
                                    رفض
                                </button>
                            @endif

                            <div x-data="{ confirming: false }" class="contents">
                                <button
                                    type="button"
                                    x-on:click="confirming = true"
                                    class="flex-1 rounded-lg border border-discount py-2.5 text-sm font-medium text-discount"
                                >
                                    حذف
                                </button>

                                <div
                                    x-show="confirming"
                                    x-cloak
                                    x-on:click.self="confirming = false"
                                    class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
                                >
                                    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                        <p class="text-base font-medium text-ink">حذف هذا المنتج؟</p>
                                        <p class="mt-1 text-sm text-muted">لن يظهر المنتج بعد الآن للعملاء.</p>
                                        <div class="mt-4 flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="confirming = false; $wire.deleteProduct({{ $previewingProduct->id }})"
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
                        </div>
                    @endif

                    <button type="button" wire:click="closePreview" class="mt-4 w-full text-center text-sm text-muted underline">
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
