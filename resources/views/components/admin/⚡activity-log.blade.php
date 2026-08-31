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

<div class="mx-auto max-w-3xl p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Activity log</h1>

        <select wire:model.live="eventFilter" class="rounded-lg border-gray-300 text-sm">
            <option value="">All events</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="deleted">Deleted</option>
        </select>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($activities as $activity)
            <div class="rounded-lg border border-gray-100 p-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-800">
                        <span class="font-medium">{{ $activity->causer?->name ?? 'System' }}</span>
                        {{ $activity->event }}
                        <span class="font-medium">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                        @if ($activity->subject && method_exists($activity->subject, 'getAttribute') && $activity->subject_type === \App\Models\Product::class)
                            ({{ $activity->subject->name }})
                        @endif
                    </p>
                    <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                </div>

                @if ($activity->event === 'updated' && $attrs = $activity->properties->get('attributes'))
                    @php $old = $activity->properties->get('old', []); @endphp
                    <ul class="mt-2 space-y-0.5 text-xs text-gray-500">
                        @foreach ($attrs as $field => $value)
                            <li>
                                <span class="font-medium">{{ $field }}</span>:
                                {{ $old[$field] ?? '—' }} &rarr; {{ $value }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-400">No activity recorded.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
</div>
