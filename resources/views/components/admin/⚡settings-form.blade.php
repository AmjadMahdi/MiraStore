<?php

use App\Models\Setting;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $defaultCartName = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->defaultCartName = Setting::get('default_cart_name', 'طلبي من Shein');
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('default_cart_name', $this->defaultCartName);

        $this->saved = true;
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">الإعدادات</h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">اسم السلة الافتراضي للعملاء</label>
            <p class="mt-1 text-xs text-muted">هذا الاسم يظهر تلقائياً عند إضافة العميل لرابط منتج جديد.</p>
            <input type="text" wire:model="defaultCartName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('defaultCartName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            حفظ
        </button>

        @if ($saved)
            <p class="text-center text-sm text-success" wire:poll.3s="$set('saved', false)">تم الحفظ ✓</p>
        @endif
    </form>
</div>
