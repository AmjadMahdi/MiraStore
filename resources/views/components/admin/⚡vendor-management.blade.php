<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $rejectingVendorId = null;

    public string $rejectionReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveApplication(User $vendor): void
    {
        abort_unless($vendor->isVendor(), 403);

        $vendor->update(['application_status' => 'approved', 'application_rejection_reason' => null]);
    }

    public function startRejectApplication(int $vendorId): void
    {
        $this->rejectingVendorId = $vendorId;
        $this->rejectionReason = '';
    }

    public function cancelRejectApplication(): void
    {
        $this->rejectingVendorId = null;
        $this->rejectionReason = '';
    }

    public function confirmRejectApplication(): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:500']);

        $vendor = User::findOrFail($this->rejectingVendorId);
        abort_unless($vendor->isVendor(), 403);

        $vendor->update([
            'application_status' => 'rejected',
            'application_rejection_reason' => $this->rejectionReason,
        ]);

        $this->cancelRejectApplication();
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
            'pendingCount' => User::where('role', 'vendor')->where('application_status', 'pending')->count(),
            'vendors' => User::query()
                ->where('role', 'vendor')
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('application_status', $this->statusFilter))
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

    <div class="mt-3 flex gap-2">
        @foreach (['pending' => 'قيد المراجعة', 'approved' => 'مقبولون', 'rejected' => 'مرفوضون', 'all' => 'الكل'] as $value => $label)
            <button
                type="button"
                wire:click="$set('statusFilter', '{{ $value }}')"
                @class([
                    'rounded-full border px-4 py-1.5 text-sm font-medium transition',
                    'border-primary bg-primary text-white' => $statusFilter === $value,
                    'border-line-medium text-ink-soft hover:bg-surface' => $statusFilter !== $value,
                ])
            >
                {{ $label }}
                @if ($value === 'pending' && $pendingCount > 0)
                    <span class="ms-1 rounded-full bg-discount px-1.5 text-xs text-white">{{ $pendingCount }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($vendors as $vendor)
            <div class="rounded-lg border border-line-medium p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">
                            {{ $vendor->store_name }}
                            @if ($vendor->application_status === 'pending')
                                <span class="ms-1 rounded bg-warning px-1.5 py-0.5 text-xs font-medium text-white">طلب قيد المراجعة</span>
                            @elseif ($vendor->application_status === 'rejected')
                                <span class="ms-1 rounded bg-discount-light px-1.5 py-0.5 text-xs font-medium text-discount">طلب مرفوض</span>
                            @endif
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
                        @if ($vendor->application_status === 'rejected' && $vendor->application_rejection_reason)
                            <p class="mt-1 text-xs text-discount">سبب الرفض: {{ $vendor->application_rejection_reason }}</p>
                        @endif
                    </div>

                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="flex-shrink-0 text-sm text-primary underline">
                        تعديل
                    </a>
                </div>

                @if (in_array($vendor->application_status, ['pending', 'rejected'], true))
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="approveApplication({{ $vendor->id }})"
                            class="rounded-lg bg-primary px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-primary-hover"
                        >
                            قبول الطلب
                        </button>
                        @if ($vendor->application_status === 'pending')
                            <button
                                type="button"
                                wire:click="startRejectApplication({{ $vendor->id }})"
                                class="rounded-lg border border-discount px-2.5 py-1 text-xs font-medium text-discount"
                            >
                                رفض الطلب
                            </button>
                        @endif
                    </div>

                    @if ($rejectingVendorId === $vendor->id)
                        <div class="mt-2 space-y-2">
                            <input
                                type="text"
                                wire:model="rejectionReason"
                                placeholder="سبب الرفض (سيظهر للتاجر)"
                                class="w-full rounded-lg border border-line-medium px-3.5 py-2 text-sm focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @error('rejectionReason') <p class="text-xs text-discount">{{ $message }}</p> @enderror
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    wire:click="confirmRejectApplication"
                                    class="rounded-lg bg-discount px-2.5 py-1 text-xs font-semibold text-white transition hover:opacity-90"
                                >
                                    تأكيد الرفض
                                </button>
                                <button
                                    type="button"
                                    wire:click="cancelRejectApplication"
                                    class="rounded-lg border border-line-medium px-2.5 py-1 text-xs font-medium text-ink"
                                >
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    @endif
                @endif

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
