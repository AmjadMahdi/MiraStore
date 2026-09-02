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

    <div class="mt-6 space-y-3">
        @php
            $eventLabels = ['created' => 'أنشأ', 'updated' => 'عدّل', 'deleted' => 'حذف'];
        @endphp

        @forelse ($activities as $activity)
            <div class="rounded-lg border border-line-medium p-3">
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
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا يوجد نشاط مسجّل.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
</div>
