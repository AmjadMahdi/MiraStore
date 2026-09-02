<x-layouts.app title="وساطة طلبات شي إن">
    <div class="mx-auto max-w-md p-6 pb-0">
        <h1 class="text-2xl font-bold tracking-tight text-ink">اطلب سلة شي إن الخاصة بك</h1>
        <p class="mt-2 text-sm text-muted">بدون عمولة. أرسل رابط سلتك وسنرسل لك السعر عبر واتساب.</p>
    </div>

    <livewire:shein.submit-cart />

    <div class="mx-auto max-w-md border-t border-line-medium p-6">
        <livewire:shein.track-cart />
    </div>
</x-layouts.app>
