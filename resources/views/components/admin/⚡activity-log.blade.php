<?php

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new class extends Component
{
    use WithPagination;

    public string $eventFilter = '';

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function deleteSelected(array $ids): void
    {
        Activity::whereIn('id', array_map('intval', $ids))->delete();
    }

    public function with(): array
    {
        return [
            'activities' => Activity::query()
                ->with('causer', 'subject')
                ->when($this->eventFilter, fn ($query) => $query->where('event', $this->eventFilter))
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-ink">سجل النشاط</h1>

        <select wire:model.live="eventFilter" class="rounded-lg border border-line-medium px-3 py-1.5 text-sm focus:border-black focus:ring-1 focus:ring-black">
            <option value="">كل الأحداث</option>
            <option value="created">إنشاء</option>
            <option value="updated">تعديل</option>
            <option value="deleted">حذف</option>
        </select>
    </div>

    <div class="mt-4" x-data="{ selected: [], all: @js($activities->pluck('id')) }">
        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-1.5 text-xs font-medium text-ink-soft">
                <input
                    type="checkbox"
                    x-on:change="selected = $event.target.checked ? [...all] : []"
                    :checked="all.length > 0 && selected.length === all.length"
                    class="h-4 w-4 rounded border-line-medium"
                >
                تحديد الكل
            </label>

            <div x-data="{ confirming: false }" class="contents">
                <button
                    type="button"
                    x-on:click="confirming = true"
                    :class="selected.length === 0 ? 'pointer-events-none opacity-40' : ''"
                    class="rounded-lg border border-discount px-3 py-1.5 text-xs font-medium text-discount"
                >
                    حذف المحدد (<span x-text="selected.length"></span>)
                </button>

                <div
                    x-show="confirming"
                    x-cloak
                    x-on:click.self="confirming = false"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                >
                    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                        <p class="text-base font-medium text-ink">حذف <span x-text="selected.length"></span> سجل من سجل النشاط نهائياً؟</p>
                        <p class="mt-1 text-sm text-muted">لا يمكن التراجع عن هذا.</p>
                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                x-on:click="confirming = false; $wire.deleteSelected(selected); selected = []"
                                class="flex-1 rounded-lg bg-discount py-2 text-sm font-semibold text-white transition hover:opacity-90"
                            >
                                حذف نهائياً
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

        <div class="mt-3 space-y-3">
            @php
                $eventLabels = ['created' => 'أنشأ', 'updated' => 'عدّل', 'deleted' => 'حذف'];
            @endphp

            @forelse ($activities as $activity)
                <div class="flex items-start gap-2 rounded-lg border border-line-medium p-3">
                    <input
                        type="checkbox"
                        value="{{ $activity->id }}"
                        x-model="selected"
                        class="mt-1 h-4 w-4 flex-shrink-0 rounded border-line-medium"
                    >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-ink">
                                <span class="font-medium">{{ $activity->causer?->name ?? 'النظام' }}</span>
                                {{ $eventLabels[$activity->event] ?? $activity->event }}
                                <span class="font-medium">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                                @if ($activity->subject && method_exists($activity->subject, 'getAttribute') && $activity->subject_type === \App\Models\Product::class)
                                    ({{ $activity->subject->name }})
                                @endif
                            </p>
                            <p class="text-xs text-disabled">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>

                        @if ($activity->event === 'updated' && $attrs = $activity->properties->get('attributes'))
                            @php $old = $activity->properties->get('old', []); @endphp
                            <ul class="mt-2 space-y-0.5 text-xs text-muted">
                                @foreach ($attrs as $field => $value)
                                    <li>
                                        <span class="font-medium">{{ $field }}</span>:
                                        {{ $old[$field] ?? '—' }} &larr; {{ $value }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-disabled">لا يوجد نشاط مسجّل.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
</div>
