<?php

use App\Models\SheinCart;
use Livewire\Component;

new class extends Component
{
    public function markAllOrdered(): void
    {
        SheinCart::where('status', 'open')->update(['status' => 'ordered']);
    }

    public function with(): array
    {
        $carts = SheinCart::query()
            ->where('status', 'open')
            ->oldest()
            ->get();

        $allCodes = $carts
            ->flatMap(fn (SheinCart $cart) => collect(explode("\n", $cart->cart_details))
                ->map(fn ($line) => trim($line))
                ->filter())
            ->values();

        return [
            'carts' => $carts,
            'allCodes' => $allCodes,
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">السلة الرئيسية</h1>
            <p class="mt-1 text-sm text-muted">كل الأكواد المفتوحة من جميع العملاء في قائمة واحدة، جاهزة للشراء من Shein.</p>
        </div>
        <a href="{{ route('admin.carts.index') }}" class="flex-shrink-0 text-sm text-primary underline">سلال العملاء</a>
    </div>

    @if ($carts->isEmpty())
        <p class="mt-10 py-10 text-center text-sm text-disabled">لا توجد سلال مفتوحة حالياً.</p>
    @else
        <div class="mt-6 rounded-lg border border-line-medium p-4">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-ink">
                    {{ $allCodes->count() }} كود من {{ $carts->count() }} {{ $carts->count() === 1 ? 'سلة' : 'سلال' }}
                </p>

                <button
                    type="button"
                    x-data
                    x-on:click="
                        navigator.clipboard.writeText(@js($allCodes->implode(chr(10))));
                        $el.textContent = 'تم النسخ ✓';
                        setTimeout(() => $el.textContent = 'نسخ كل الأكواد', 1500);
                    "
                    class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-1.5 text-xs font-medium text-ink-soft"
                >
                    نسخ كل الأكواد
                </button>
            </div>

            <div class="mt-3 max-h-64 overflow-y-auto rounded-lg bg-surface p-3">
                <p class="whitespace-pre-line text-xs text-ink-soft" dir="ltr">{{ $allCodes->implode("\n") }}</p>
            </div>
        </div>

        <div x-data="{ confirming: false }" class="mt-3">
            <button
                type="button"
                x-on:click="confirming = true"
                class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover"
            >
                تأكيد الشراء وتحويل الكل إلى "تم الطلب"
            </button>

            <div
                x-show="confirming"
                x-cloak
                x-on:click.self="confirming = false"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                    <p class="text-base font-medium text-ink">تأكيد شراء كل الطلبات المفتوحة؟</p>
                    <p class="mt-1 text-sm text-muted">سيتم تحويل كل السلال المفتوحة ({{ $carts->count() }}) إلى حالة "تم الطلب".</p>
                    <div class="mt-4 flex gap-2">
                        <button
                            type="button"
                            x-on:click="confirming = false; $wire.markAllOrdered()"
                            class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary-hover"
                        >
                            تأكيد
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

        <div class="mt-8 space-y-3">
            <h2 class="text-sm font-semibold text-ink-soft">تفاصيل حسب العميل</h2>

            @foreach ($carts as $cart)
                <div class="rounded-lg border border-line-medium p-3">
                    <p class="text-sm font-medium text-ink">{{ $cart->cart_name }} &middot; {{ $cart->cart_number }}</p>
                    <p class="text-sm text-muted">{{ $cart->customer_phone }}</p>
                    <p class="mt-2 whitespace-pre-line text-xs text-ink-soft" dir="ltr">{{ $cart->cart_details }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
