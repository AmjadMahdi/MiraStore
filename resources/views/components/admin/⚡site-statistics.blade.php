<?php

use App\Models\SiteVisit;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateTo = now()->toDateString();
        $this->dateFrom = now()->subDays(6)->toDateString();
    }

    public function applyPreset(string $preset): void
    {
        $this->dateTo = now()->toDateString();

        $this->dateFrom = match ($preset) {
            'today' => now()->toDateString(),
            '7days' => now()->subDays(6)->toDateString(),
            '30days' => now()->subDays(29)->toDateString(),
            default => $this->dateFrom,
        };
    }

    protected function range(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();

        // Guard against a from-date typed after the to-date rather than
        // erroring — just treat the range as swapped.
        return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    public function with(): array
    {
        [$from, $to] = $this->range();

        $dailyBreakdown = SiteVisit::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as visits')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get();

        return [
            'totalVisits' => $dailyBreakdown->sum('visits'),
            'dailyBreakdown' => $dailyBreakdown,
        ];
    }
};
?>

<div class="mx-auto max-w-2xl p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">الإحصائيات</h1>
    <p class="mt-1 text-sm text-muted">عدد زيارات الموقع خلال الفترة المحددة.</p>

    <div class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm font-medium text-ink-soft">من</label>
            <input
                type="date"
                wire:model.live="dateFrom"
                class="mt-1.5 rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-ink-soft">إلى</label>
            <input
                type="date"
                wire:model.live="dateTo"
                class="mt-1.5 rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
            >
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="applyPreset('today')" class="rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink-soft">
                اليوم
            </button>
            <button type="button" wire:click="applyPreset('7days')" class="rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink-soft">
                آخر 7 أيام
            </button>
            <button type="button" wire:click="applyPreset('30days')" class="rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink-soft">
                آخر 30 يوم
            </button>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-line-medium bg-surface p-5">
        <p class="text-sm text-muted">إجمالي الزيارات</p>
        <p class="mt-1 text-3xl font-bold text-ink" style="font-variant-numeric: tabular-nums">{{ $totalVisits }}</p>
    </div>

    <div class="mt-6">
        <h2 class="text-sm font-semibold text-ink-soft">التفصيل اليومي</h2>

        <div class="mt-2 space-y-1.5">
            @forelse ($dailyBreakdown as $day)
                <div class="flex items-center justify-between rounded-lg border border-line-medium px-3 py-2">
                    <span class="text-sm text-ink" dir="ltr">{{ $day->date }}</span>
                    <span class="text-sm font-semibold text-ink" style="font-variant-numeric: tabular-nums">{{ $day->visits }}</span>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-disabled">لا توجد زيارات مسجّلة في هذه الفترة.</p>
            @endforelse
        </div>
    </div>
</div>
