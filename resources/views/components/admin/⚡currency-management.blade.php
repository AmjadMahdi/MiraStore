<?php

use App\Models\Currency;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $editingId = null;

    public string $editingName = '';

    public string $editingCode = '';

    public string $editingSymbol = '';

    public string $deleteBlockedMessage = '';

    public function toggleEnabled(Currency $currency): void
    {
        $currency->update(['is_enabled' => ! $currency->is_enabled]);
    }

    public function startEdit(Currency $currency): void
    {
        $this->editingId = $currency->id;
        $this->editingName = $currency->name;
        $this->editingCode = $currency->code;
        $this->editingSymbol = $currency->symbol;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingName = '';
        $this->editingCode = '';
        $this->editingSymbol = '';
    }

    public function updateCurrency(): void
    {
        $currency = Currency::findOrFail($this->editingId);

        $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingCode' => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($currency->id)],
            'editingSymbol' => ['required', 'string', 'max:10'],
        ]);

        $currency->update([
            'name' => $this->editingName,
            'code' => $this->editingCode,
            'symbol' => $this->editingSymbol,
        ]);

        $this->cancelEdit();
    }

    public function deleteCurrency(Currency $currency): void
    {
        $this->deleteBlockedMessage = '';

        if ($currency->products()->exists()) {
            $this->deleteBlockedMessage = "لا يمكن حذف \"{$currency->name}\" لأنها مستخدمة في منتجات حالية.";

            return;
        }

        $currency->delete();
    }

    public function with(): array
    {
        return [
            'currencies' => Currency::withCount('products')->orderBy('name')->get(),
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">العملات</h1>
    <p class="mt-1 text-sm text-muted">تحكم في العملات المتاحة للتجّار عند تسعير منتجاتهم.</p>

    @if ($deleteBlockedMessage)
        <div class="mt-4 rounded-lg border border-discount bg-discount-light p-3">
            <p class="text-sm font-semibold text-discount">{{ $deleteBlockedMessage }}</p>
        </div>
    @endif

    <div class="mt-6 space-y-2">
        @foreach ($currencies as $currency)
            <div class="rounded-lg border border-line-medium p-3 transition hover:bg-surface">
                @if ($editingId === $currency->id)
                    <form wire:submit="updateCurrency" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-ink-soft">الاسم</label>
                            <input type="text" wire:model="editingName" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                            @error('editingName') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-ink-soft">الرمز (كود)</label>
                                <input type="text" wire:model="editingCode" dir="ltr" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('editingCode') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-ink-soft">علامة العملة</label>
                                <input type="text" wire:model="editingSymbol" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black">
                                @error('editingSymbol') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-hover">
                                حفظ
                            </button>
                            <button type="button" wire:click="cancelEdit" class="rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink">
                                إلغاء
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink">
                                {{ $currency->name }}
                                <span class="ms-1 text-xs text-muted" dir="ltr">{{ $currency->code }} &middot; {{ $currency->symbol }}</span>
                                @unless ($currency->is_enabled)
                                    <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">معطّلة</span>
                                @endunless
                            </p>
                            <p class="text-xs text-muted">{{ $currency->products_count }} منتج</p>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-2">
                            <button
                                type="button"
                                wire:click="toggleEnabled({{ $currency->id }})"
                                class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                            >
                                {{ $currency->is_enabled ? 'تعطيل' : 'تفعيل' }}
                            </button>

                            <button
                                type="button"
                                wire:click="startEdit({{ $currency->id }})"
                                class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                            >
                                تعديل
                            </button>

                            <div x-data="{ confirming: false }" class="contents">
                                <button
                                    type="button"
                                    x-on:click="confirming = true"
                                    class="rounded-lg border border-discount px-2.5 py-1 text-xs font-medium text-discount"
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
                                        <p class="text-base font-medium text-ink">حذف عملة "{{ $currency->name }}"؟</p>
                                        @if ($currency->products_count > 0)
                                            <p class="mt-1 text-sm text-muted">
                                                لا يمكن الحذف حالياً — {{ $currency->products_count }} منتج يستخدم هذه العملة.
                                            </p>
                                        @endif
                                        <div class="mt-4 flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="confirming = false; $wire.deleteCurrency({{ $currency->id }})"
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
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
