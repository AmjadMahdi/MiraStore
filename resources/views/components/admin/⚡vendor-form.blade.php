<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?User $vendor = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $store_name = '';

    public string $email = '';

    #[Validate('required|string|max:50')]
    public string $whatsapp_number = '';

    public string $password = '';

    #[Validate('nullable|integer|min:1')]
    public string $max_products_limit = '';

    public bool $unlimited_products = false;

    public bool $is_verified = false;

    public bool $is_active = true;

    public function mount(?User $vendor = null): void
    {
        if ($vendor?->exists) {
            $this->vendor = $vendor;
            $this->name = $vendor->name;
            $this->store_name = $vendor->store_name ?? '';
            $this->email = $vendor->email;
            $this->whatsapp_number = $vendor->whatsapp_number ?? '';
            $this->max_products_limit = $vendor->max_products_limit !== null ? (string) $vendor->max_products_limit : '';
            $this->unlimited_products = $vendor->max_products_limit === null;
            $this->is_verified = $vendor->is_verified;
            $this->is_active = $vendor->is_active;
        } else {
            $this->max_products_limit = '5';
        }
    }

    protected function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->vendor?->id),
            ],
            'password' => $this->vendor
                ? 'nullable|string|min:8'
                : 'required|string|min:8',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'store_name' => $this->store_name,
            'email' => $this->email,
            'whatsapp_number' => $this->whatsapp_number,
            'max_products_limit' => $this->unlimited_products ? null : ($this->max_products_limit !== '' ? (int) $this->max_products_limit : 5),
            'is_verified' => $this->is_verified,
            'is_active' => $this->is_active,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->vendor) {
            $this->vendor->update($data);
        } else {
            $data['role'] = 'vendor';
            $data['password'] = Hash::make($this->password);
            User::create($data);
        }

        $this->redirect(route('admin.vendors.index'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">
        {{ $vendor ? 'تعديل بيانات التاجر' : 'إضافة تاجر' }}
    </h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">الاسم</label>
            <input type="text" wire:model="name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">اسم المتجر</label>
            <input type="text" wire:model="store_name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('store_name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">البريد الإلكتروني</label>
            <input type="email" wire:model="email" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('email') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">رقم واتساب</label>
            <input type="text" wire:model="whatsapp_number" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('whatsapp_number') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">
                {{ $vendor ? 'كلمة مرور جديدة (اتركها فارغة لعدم التغيير)' : 'كلمة المرور' }}
            </label>
            <input type="password" wire:model="password" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('password') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">الحد الأقصى للمنتجات</label>
            <div class="mt-1.5 flex items-center gap-2">
                <input
                    type="number"
                    wire:model="max_products_limit"
                    min="1"
                    x-bind:disabled="$wire.unlimited_products"
                    class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black disabled:bg-surface disabled:text-disabled"
                >
                <label class="flex flex-shrink-0 items-center gap-1.5 text-sm text-ink-soft">
                    <input type="checkbox" wire:model.live="unlimited_products" class="rounded border-line-medium">
                    غير محدود
                </label>
            </div>
            @error('max_products_limit') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-4">
            <label class="flex items-center gap-1.5 text-sm text-ink-soft">
                <input type="checkbox" wire:model="is_verified" class="rounded border-line-medium">
                بائع موثّق
            </label>
            <label class="flex items-center gap-1.5 text-sm text-ink-soft">
                <input type="checkbox" wire:model="is_active" class="rounded border-line-medium">
                الحساب مُفعّل
            </label>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">{{ $vendor ? 'حفظ التغييرات' : 'إضافة التاجر' }}</span>
            <span wire:loading wire:target="save">جارٍ الحفظ...</span>
        </button>
    </form>
</div>
