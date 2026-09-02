<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['is_active' => ! $vendor->is_active]);
    }

    public function toggleVerified(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['is_verified' => ! $vendor->is_verified]);
    }

    public function togglePlatformStore(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['is_platform_store' => ! $vendor->is_platform_store]);
    }

    public function upgradeToPremium(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['max_products_limit' => null]);
    }

    public function downgradeToBasic(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['max_products_limit' => 5]);
    }

    public function deleteVendor(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->delete();
    }

    public function with(): array
    {
        return [
            'vendors' => User::query()
                ->where('role', 'vendor')
                ->when($this->search, fn ($query) => $query->where('store_name', 'like', "%{$this->search}%"))
                ->withCount('products')
                ->latest()
                ->paginate(10),
        ];
    }
};
?>

<div class="mx-auto max-w-3xl p-6 sm:p-8">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold tracking-tight text-ink">التجّار</h1>

        <a href="{{ route('admin.vendors.create') }}" class="flex-shrink-0 rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
            + إضافة تاجر
        </a>
    </div>

    <input
        type="search"
        wire:model.live.debounce.300ms="search"
        placeholder="ابحث باسم المتجر..."
        class="mt-3 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
    >

    <div class="mt-6 space-y-3">
        @forelse ($vendors as $vendor)
            <div class="rounded-lg border border-line-medium p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ $vendor->store_name }}
                            @if ($vendor->is_platform_store)
                                <span class="ms-1 rounded bg-success px-1.5 py-0.5 text-xs font-medium text-white">متجرنا</span>
                            @endif
                            @if ($vendor->is_verified)
                                <span class="ms-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-white">موثّق</span>
                            @endif
                            @unless ($vendor->is_active)
                                <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">موقوف</span>
                            @endunless
                        </p>
                        <p class="truncate text-xs text-muted">{{ $vendor->name }}</p>
                        <p class="text-xs text-muted">
                            {{ $vendor->email }} &middot; {{ $vendor->products_count }} منتج
                            &middot; الحد: {{ $vendor->max_products_limit ?? 'غير محدود' }}
                        </p>
                    </div>

                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="flex-shrink-0 text-sm text-primary underline">
                        تعديل
                    </a>
                </div>

                <div class="mt-2 flex flex-wrap gap-2">
                    <div x-data="{ confirming: false }" class="contents">
                        <button
                            type="button"
                            x-on:click="confirming = true"
                            class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                        >
                            {{ $vendor->is_active ? 'إيقاف' : 'تفعيل' }}
                        </button>

                        <div
                            x-show="confirming"
                            x-cloak
                            x-on:click.self="confirming = false"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                        >
                            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                <p class="text-base font-medium text-ink">
                                    {{ $vendor->is_active ? 'هل تريد إيقاف هذا التاجر؟' : 'هل تريد إعادة تفعيل هذا التاجر؟' }}
                                </p>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        type="button"
                                        x-on:click="confirming = false; $wire.toggleActive({{ $vendor->id }})"
                                        class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary-hover"
                                    >
                                        {{ $vendor->is_active ? 'إيقاف' : 'تفعيل' }}
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

                    <button
                        type="button"
                        wire:click="toggleVerified({{ $vendor->id }})"
                        class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                    >
                        {{ $vendor->is_verified ? 'إزالة شارة التوثيق' : 'وضع علامة موثّق' }}
                    </button>

                    <button
                        type="button"
                        wire:click="togglePlatformStore({{ $vendor->id }})"
                        class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                    >
                        {{ $vendor->is_platform_store ? 'إزالة صفة "متجرنا"' : 'وضع علامة "متجرنا"' }}
                    </button>

                    @if ($vendor->max_products_limit !== null)
                        <button
                            type="button"
                            wire:click="upgradeToPremium({{ $vendor->id }})"
                            class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                        >
                            الترقية إلى بريميوم (غير محدود)
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="downgradeToBasic({{ $vendor->id }})"
                            class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink-soft"
                        >
                            الرجوع إلى الباقة الأساسية (5)
                        </button>
                    @endif

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
                                <p class="text-base font-medium text-ink">حذف هذا التاجر؟</p>
                                <p class="mt-1 text-sm text-muted">سيتم إخفاء متجره ومنتجاته من الموقع. يمكن استرجاع البيانات لاحقاً.</p>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        type="button"
                                        x-on:click="confirming = false; $wire.deleteVendor({{ $vendor->id }})"
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
        @empty
            <p class="py-10 text-center text-sm text-disabled">لا يوجد تجّار.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $vendors->links() }}
    </div>
</div>
