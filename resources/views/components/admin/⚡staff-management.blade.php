<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public function toggleActive(User $staff): void
    {
        abort_unless($staff->isStaff(), 404);
        abort_if($staff->id === Auth::id(), 403, 'لا يمكنك إيقاف حسابك الخاص.');

        $staff->update(['is_active' => ! $staff->is_active]);
    }

    public function deleteStaff(User $staff): void
    {
        abort_unless($staff->isStaff(), 404);
        abort_if($staff->id === Auth::id(), 403, 'لا يمكنك حذف حسابك الخاص.');

        $staff->delete();
    }

    public function with(): array
    {
        return [
            'staffMembers' => User::query()
                ->whereIn('role', ['super_admin', 'supervisor'])
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->get(),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">حسابات الموظفين</h1>

        <a href="{{ route('admin.staff.create') }}" class="flex-shrink-0 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
            + إضافة حساب
        </a>
    </div>

    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="ابحث بالاسم..."
        class="mt-3 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
    >

    <div class="mt-6 space-y-3">
        @forelse ($staffMembers as $staff)
            <div class="rounded-lg border border-line-medium p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ $staff->name }}
                            @if ($staff->role === 'super_admin')
                                <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">مدير النظام</span>
                            @else
                                <span class="ms-1 rounded bg-surface px-1.5 py-0.5 text-xs font-medium text-ink-soft">مشرف النظام</span>
                            @endif
                            @unless ($staff->is_active)
                                <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">موقوف</span>
                            @endunless
                            @if ($staff->id === auth()->id())
                                <span class="ms-1 text-xs text-muted">(أنت)</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-muted">{{ $staff->email }}</p>
                    </div>

                    <a href="{{ route('admin.staff.edit', $staff) }}" class="flex-shrink-0 text-sm text-primary underline">
                        تعديل
                    </a>
                </div>

                @if ($staff->id !== auth()->id())
                    <div class="mt-2 flex flex-wrap gap-2">
                        <div x-data="{ confirming: false }" class="contents">
                            <button
                                type="button"
                                x-on:click="confirming = true"
                                class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                            >
                                {{ $staff->is_active ? 'إيقاف' : 'تفعيل' }}
                            </button>

                            <div
                                x-show="confirming"
                                x-cloak
                                x-on:click.self="confirming = false"
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                            >
                                <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                    <p class="text-base font-medium text-ink">
                                        {{ $staff->is_active ? 'هل تريد إيقاف هذا الحساب؟' : 'هل تريد إعادة تفعيل هذا الحساب؟' }}
                                    </p>
                                    <div class="mt-4 flex gap-2">
                                        <button
                                            type="button"
                                            x-on:click="confirming = false; $wire.toggleActive({{ $staff->id }})"
                                            class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                        >
                                            {{ $staff->is_active ? 'إيقاف' : 'تفعيل' }}
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
                                    <p class="text-base font-medium text-ink">حذف حساب "{{ $staff->name }}"؟</p>
                                    <div class="mt-4 flex gap-2">
                                        <button
                                            type="button"
                                            x-on:click="confirming = false; $wire.deleteStaff({{ $staff->id }})"
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
                @endif
            </div>
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا توجد حسابات.</p>
        @endforelse
    </div>
</div>
