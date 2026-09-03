<x-layouts.app :title="$cart->cart_name">
    <div class="mx-auto max-w-md p-6 sm:p-8">
        @php
            $steps = \App\Models\SheinCart::STATUSES;
            $stepLabels = [
                'open' => 'مفتوحة',
                'ordered' => 'تم الطلب',
                'in_transit' => 'في الطريق',
                'arrived' => 'تم الوصول',
            ];
            $currentIndex = array_search($cart->status, $steps);
        @endphp

        <p class="text-sm text-muted">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>

        <div class="mt-4 flex items-center justify-between">
            @foreach ($steps as $i => $step)
                <div class="flex flex-1 flex-col items-center">
                    <div @class([
                        'flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-primary text-white' => $i <= $currentIndex,
                        'bg-line-medium text-disabled' => $i > $currentIndex,
                    ])>
                        {{ $i + 1 }}
                    </div>
                    <p class="mt-1 text-center text-xs text-muted">{{ $stepLabels[$step] }}</p>
                </div>

                @if (! $loop->last)
                    <div @class([
                        'h-0.5 flex-1',
                        'bg-primary' => $i < $currentIndex,
                        'bg-line-medium' => $i >= $currentIndex,
                    ])></div>
                @endif
            @endforeach
        </div>

        @if ($cart->items->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-sm font-semibold text-ink-soft">العناصر ({{ $cart->items->count() }})</h2>

                <div class="mt-2 space-y-2">
                    @foreach ($cart->items as $item)
                        <div class="rounded-lg border border-line-medium p-3">
                            <p class="text-sm font-medium text-ink">
                                {{ $item->name ?? 'بدون وصف' }}
                                <span class="ms-1 rounded bg-surface px-1.5 py-0.5 text-xs font-medium text-muted">الكمية: {{ $item->quantity }}</span>
                            </p>
                            @if ($item->link)
                                <p class="mt-0.5 truncate text-xs text-muted" dir="ltr">{{ $item->link }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-disabled">{{ $item->item_date->format('Y-m-d') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($cart->cart_details)
            <div class="mt-6 rounded-lg bg-surface p-3">
                <p class="text-xs font-semibold text-muted">تفاصيل الطلب</p>
                <p class="mt-1 whitespace-pre-line text-sm text-ink-soft" dir="ltr">{{ $cart->cart_details }}</p>
            </div>
        @endif
    </div>
</x-layouts.app>
