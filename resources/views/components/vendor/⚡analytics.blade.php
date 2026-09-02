<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $vendor = Auth::user();
        $isPremium = $vendor->max_products_limit === null;

        $products = collect();

        if ($isPremium) {
            $products = $vendor->products()
                ->withCount([
                    'interactionLogs as views_count' => fn ($query) => $query->where('action_type', 'view'),
                    'interactionLogs as whatsapp_clicks_count' => fn ($query) => $query->where('action_type', 'whatsapp_click'),
                ])
                ->latest()
                ->get();
        }

        return [
            'isPremium' => $isPremium,
            'products' => $products,
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">التحليلات</h1>

    @if (! $isPremium)
        <div class="mt-4 rounded-lg bg-amber-50 p-4 text-sm text-warning">
            التحليلات المتقدمة (المشاهدات ومعدل النقر على واتساب) ميزة حصرية لباقة بريميوم. قم بترقية اشتراكك لفتحها.
        </div>
    @else
        <div class="mt-6 space-y-3">
            @forelse ($products as $product)
                @php
                    $ctr = $product->views_count > 0
                        ? round($product->whatsapp_clicks_count / $product->views_count * 100, 1)
                        : 0;
                @endphp

                <div class="rounded-lg border border-line-medium p-3">
                    <p class="text-sm font-medium text-ink">{{ $product->name }}</p>

                    <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-lg font-semibold text-primary">{{ $product->views_count }}</p>
                            <p class="text-xs text-muted">مشاهدات</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-primary">{{ $product->whatsapp_clicks_count }}</p>
                            <p class="text-xs text-muted">نقرات واتساب</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-primary">{{ $ctr }}%</p>
                            <p class="text-xs text-muted">معدل النقر</p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-disabled">لا توجد منتجات بعد.</p>
            @endforelse
        </div>
    @endif
</div>
