<?php

use App\Models\Setting;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('nullable|url|max:255')]
    public string $support_whatsapp_link = '';

    public bool $justSaved = false;

    public function mount(): void
    {
        $this->support_whatsapp_link = Setting::get('support_whatsapp_link', '') ?? '';
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('support_whatsapp_link', $this->support_whatsapp_link !== '' ? $this->support_whatsapp_link : null);

        $this->justSaved = true;
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">الإعدادات</h1>

    @if ($justSaved)
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 p-3" wire:poll.4s="$set('justSaved', false)">
            <p class="text-sm font-semibold text-green-700">تم حفظ الإعدادات بنجاح ✓</p>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">رابط واتساب الإدارة</label>
            <p class="mt-0.5 text-xs text-muted">يظهر هذا الرابط للتجّار الجدد ليتواصلوا معكم في حال وجود استفسار.</p>
            <input
                type="text"
                wire:model="support_whatsapp_link"
                dir="ltr"
                placeholder="https://wa.me/967xxxxxxxxx"
                class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
            >
            @error('support_whatsapp_link') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">حفظ</span>
            <span wire:loading wire:target="save">جارٍ الحفظ...</span>
        </button>
    </form>
</div>
