<?php

use App\Support\GuestCart;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // no-op: with() re-reads the session count on every render
    }

    public function with(): array
    {
        return [
            'count' => GuestCart::count(),
        ];
    }
};
?>

<a
    href="{{ route('shein.cart') }}"
    id="nav-cart-icon"
    x-data="{ pulse: false }"
    x-on:cart-updated.window="pulse = false; $nextTick(() => pulse = true); setTimeout(() => pulse = false, 550)"
    x-bind:class="pulse ? 'cart-pulse' : ''"
    class="relative flex items-center text-ink hover:text-primary"
    aria-label="السلة"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path d="M2.25 2.75a.75.75 0 000 1.5h1.106c.07 0 .13.05.148.118l1.62 6.482a2.75 2.75 0 002.667 2.15h5.318a2.75 2.75 0 002.667-2.15l1.093-4.372a.75.75 0 00-.728-.928H5.51l-.28-1.122a1.75 1.75 0 00-1.698-1.35H2.25z" />
        <path d="M6.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM13.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
    </svg>

    @if ($count > 0)
        <span class="absolute -top-2 -end-2 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-white">
            {{ $count }}
        </span>
    @endif
</a>
