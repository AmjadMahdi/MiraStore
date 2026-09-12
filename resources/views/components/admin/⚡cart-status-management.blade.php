<?php

use App\Models\SheinCart;
use App\Models\SheinCartStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public string $label = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editingLabel = '';

    public string $deleteBlockedMessage = '';

    /** @var array<int, int> */
    public array $orderedIds = [];

    public function mount(): void
    {
        $this->refreshOrderedIds();
    }

    protected function refreshOrderedIds(): void
    {
        $this->orderedIds = SheinCartStatus::orderBy('display_order')->pluck('id')->all();
    }

    public function addStatus(): void
    {
        $this->validate(['label' => ['required', 'string', 'max:255', Rule::unique('shein_cart_statuses', 'label')]]);

        $nextOrder = ((int) SheinCartStatus::max('display_order')) + 1;

        SheinCartStatus::create([
            'key' => SheinCartStatus::generateKey(),
            'label' => $this->label,
            'display_order' => $nextOrder,
        ]);

        $this->label = '';
        $this->refreshOrderedIds();
    }

    public function startEdit(SheinCartStatus $status): void
    {
        $this->editingId = $status->id;
        $this->editingLabel = $status->label;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingLabel = '';
    }

    public function updateStatus(): void
    {
        $status = SheinCartStatus::findOrFail($this->editingId);

        $this->validate([
            'editingLabel' => ['required', 'string', 'max:255', Rule::unique('shein_cart_statuses', 'label')->ignore($status->id)],
        ]);

        $status->update(['label' => $this->editingLabel]);

        $this->cancelEdit();
    }

    public function deleteStatus(SheinCartStatus $status): void
    {
        $this->deleteBlockedMessage = '';

        if (SheinCartStatus::count() <= 1) {
            $this->deleteBlockedMessage = 'يجب أن تبقى حالة واحدة على الأقل.';

            return;
        }

        $cartsUsingStatus = SheinCart::where('status', $status->key)->count();

        if ($cartsUsingStatus > 0) {
            $this->deleteBlockedMessage = "لا يمكن حذف \"{$status->label}\" لأنها مستخدمة في {$cartsUsingStatus} سلة حالياً — غيّر حالة تلك السلال أولاً.";

            return;
        }

        $status->delete();
        $this->refreshOrderedIds();
    }

    public function moveStatus(int $from, int $to): void
    {
        if ($from === $to || ! array_key_exists($from, $this->orderedIds) || ! array_key_exists($to, $this->orderedIds)) {
            return;
        }

        $ids = $this->orderedIds;
        $moved = array_splice($ids, $from, 1);
        array_splice($ids, $to, 0, $moved);

        $this->orderedIds = $ids;

        foreach ($this->orderedIds as $index => $id) {
            SheinCartStatus::whereKey($id)->update(['display_order' => $index]);
        }
    }

    public function with(): array
    {
        $cartCounts = SheinCart::selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $statuses = SheinCartStatus::whereIn('id', $this->orderedIds)->get()->keyBy('id');

        return [
            'statuses' => collect($this->orderedIds)->map(fn ($id) => $statuses->get($id))->filter()->values(),
            'cartCounts' => $cartCounts,
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">حالات السلة</h1>
    <p class="mt-1 text-sm text-muted">اسحب الحالات لتغيير ترتيب مراحل تتبع الطلب التي يراها العميل.</p>

    @if ($deleteBlockedMessage)
        <div class="mt-4 rounded-lg border border-discount bg-discount-light p-3">
            <p class="text-sm font-semibold text-discount">{{ $deleteBlockedMessage }}</p>
        </div>
    @endif

    <form wire:submit="addStatus" class="mt-6 flex items-start gap-2">
        <div class="flex-1">
            <input
                type="text"
                wire:model="label"
                placeholder="اسم الحالة الجديدة"
                class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
            >
            @error('label') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="addStatus"
            class="flex-shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            + إضافة
        </button>
    </form>

    <div class="mt-6 space-y-2" x-data="{ dragIndex: null }">
        @forelse ($statuses as $index => $status)
            <div
                draggable="true"
                x-on:dragstart="dragIndex = {{ $index }}"
                x-on:dragover.prevent
                x-on:drop="if (dragIndex !== null) { $wire.moveStatus(dragIndex, {{ $index }}); dragIndex = null }"
                class="cursor-move rounded-lg border border-line-medium bg-white p-3 transition hover:bg-surface"
            >
                @if ($editingId === $status->id)
                    <form wire:submit="updateStatus" class="flex items-start gap-2" x-on:dragstart.stop x-on:mousedown.stop>
                        <div class="flex-1">
                            <input
                                type="text"
                                wire:model="editingLabel"
                                class="w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @error('editingLabel') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="flex-shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white transition hover:bg-primary-hover">
                            حفظ
                        </button>
                        <button type="button" wire:click="cancelEdit" class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink">
                            إلغاء
                        </button>
                    </form>
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 text-disabled" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $status->label }}</p>
                                <p class="text-xs text-muted">{{ $cartCounts[$status->key] ?? 0 }} سلة</p>
                            </div>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-2">
                            <button
                                type="button"
                                wire:click="startEdit({{ $status->id }})"
                                x-on:mousedown.stop
                                class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                            >
                                تعديل
                            </button>

                            <div x-data="{ confirming: false }" class="contents">
                                <button
                                    type="button"
                                    x-on:click="confirming = true"
                                    x-on:mousedown.stop
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
                                        <p class="text-base font-medium text-ink">حذف حالة "{{ $status->label }}"؟</p>
                                        @if (($cartCounts[$status->key] ?? 0) > 0)
                                            <p class="mt-1 text-sm text-muted">
                                                {{ $cartCounts[$status->key] }} سلة تستخدم هذه الحالة حالياً — لا يمكن الحذف قبل تغيير حالتها.
                                            </p>
                                        @endif
                                        <div class="mt-4 flex gap-2">
                                            <button
                                                type="button"
                                                x-on:click="confirming = false; $wire.deleteStatus({{ $status->id }})"
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

                            <span class="flex-shrink-0 rounded bg-surface px-2 py-0.5 text-xs font-medium text-muted">{{ $index + 1 }}</span>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد حالات بعد.</p>
        @endforelse
    </div>
</div>
