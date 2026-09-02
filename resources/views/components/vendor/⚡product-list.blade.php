<?php

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public function delete(Product $product): void
    {
        abort_unless($product->vendor_id === Auth::id(), 403);

        $product->delete();
    }

    public function with(): array
    {
        $vendor = Auth::user();

        return [
            'vendor' => $vendor,
            'products' => $vendor->products()->latest()->get(),
            'atLimit' => $vendor->max_products_limit !== null
                && $vendor->products()->count() >= $vendor->max_products_limit,
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-ink">منتجاتي</h1>

        @if (! $atLimit)
            <a href="{{ route('vendor.products.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover">
                إضافة منتج
            </a>
        @endif
    </div>

    @if ($atLimit)
        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-warning">
            لقد وصلت إلى الحد الأقصى المسموح به وهو {{ $vendor->max_products_limit }} منتجات. قم بالترقية إلى بريميوم لرفع عدد غير محدود.
        </p>
    @endif

    <div class="mt-6 space-y-3">
        @php
            $statusLabels = ['approved' => 'معتمد', 'pending' => 'قيد المراجعة', 'rejected' => 'مرفوض'];
        @endphp

        @forelse ($products as $product)
            <div class="flex items-center gap-3 rounded-lg border border-line-medium p-3">
                <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-surface">
                    <img src="{{ Storage::url($product->image_path) }}" class="h-full w-full object-cover">
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink">{{ $product->name }}</p>
                    <p class="text-sm text-muted">{{ number_format($product->price, 2) }}</p>
                </div>

                <span @class([
                    'rounded px-2 py-0.5 text-xs font-medium',
                    'bg-green-50 text-success' => $product->status === 'approved',
                    'bg-amber-50 text-warning' => $product->status === 'pending',
                    'bg-discount-light text-discount' => $product->status === 'rejected',
                ])>
                    {{ $statusLabels[$product->status] }}
                </span>

                <a href="{{ route('vendor.products.edit', $product) }}" class="text-sm text-muted underline">تعديل</a>

                <div x-data="{ confirming: false }" class="contents">
                    <button
                        type="button"
                        x-on:click="confirming = true"
                        class="text-sm text-discount underline"
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
                            <p class="text-base font-medium text-ink">هل تريد حذف هذا المنتج؟</p>
                            <p class="mt-1 text-sm text-muted">لا يمكن التراجع عن هذا الإجراء.</p>
                            <div class="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    x-on:click="confirming = false; $wire.delete({{ $product->id }})"
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

            @if ($product->status === 'rejected' && $product->rejection_reason)
                <p class="-mt-2 ps-3 text-xs text-discount">تم الرفض: {{ $product->rejection_reason }}</p>
            @endif
        @empty
            <div class="flex flex-col items-center py-16 text-center">
                <svg class="h-10 w-10 text-disabled" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <p class="mt-3 text-sm text-disabled">لم تقم بإضافة أي منتجات بعد.</p>
                <a href="{{ route('vendor.products.create') }}" class="mt-3 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover">
                    أضف منتجك الأول
                </a>
            </div>
        @endforelse
    </div>
</div>
