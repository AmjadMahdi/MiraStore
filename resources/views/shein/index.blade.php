<x-layouts.app title="SHEIN Order Mediation">
    <div class="mx-auto max-w-md p-6 pb-0">
        <h1 class="text-xl font-semibold text-gray-800">Order your SHEIN cart</h1>
        <p class="mt-2 text-sm text-gray-500">Zero commission. Submit your cart link and we'll quote you a price on WhatsApp.</p>
    </div>

    <livewire:shein.submit-cart />

    <div class="mx-auto max-w-md border-t border-gray-100 p-6">
        <livewire:shein.track-cart />
    </div>
</x-layouts.app>
