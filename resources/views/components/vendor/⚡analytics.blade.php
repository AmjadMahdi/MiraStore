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

<div class="mx-auto max-w-2xl p-6">
    <h1 class="text-xl font-semibold text-gray-800">Analytics</h1>

    @if (! $isPremium)
        <div class="mt-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-700">
            Advanced analytics (views and WhatsApp click-through rates) are a Premium feature. Upgrade your subscription to unlock them.
        </div>
    @else
        <div class="mt-6 space-y-3">
            @forelse ($products as $product)
                @php
                    $ctr = $product->views_count > 0
                        ? round($product->whatsapp_clicks_count / $product->views_count * 100, 1)
                        : 0;
                @endphp

                <div class="rounded-lg border border-gray-100 p-3">
                    <p class="text-sm font-medium text-gray-800">{{ $product->name }}</p>

                    <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-lg font-semibold text-rose-600">{{ $product->views_count }}</p>
                            <p class="text-xs text-gray-500">Views</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-rose-600">{{ $product->whatsapp_clicks_count }}</p>
                            <p class="text-xs text-gray-500">WhatsApp clicks</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-rose-600">{{ $ctr }}%</p>
                            <p class="text-xs text-gray-500">Click-through</p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-gray-400">No products yet.</p>
            @endforelse
        </div>
    @endif
</div>
