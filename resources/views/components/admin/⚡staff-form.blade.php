<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?User $staff = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'supervisor';

    public bool $is_active = true;

    public function mount(?User $staff = null): void
    {
        if ($staff?->exists) {
            $this->staff = $staff;
            $this->name = $staff->name;
            $this->email = $staff->email;
            $this->role = $staff->role;
            $this->is_active = $staff->is_active;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->staff?->id),
            ],
            'password' => $this->staff
                ? 'nullable|string|min:8'
                : 'required|string|min:8',
            'role' => ['required', 'in:super_admin,supervisor'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $isEditingSelf = $this->staff && $this->staff->id === Auth::id();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            // A staff member can't demote themselves or lock themselves out —
            // only another System Administrator can change those two fields
            // on this account.
            'role' => $isEditingSelf ? $this->staff->role : $this->role,
            'is_active' => $isEditingSelf ? true : $this->is_active,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->staff) {
            $this->staff->update($data);
        } else {
            $data['password'] = Hash::make($this->password);
            User::create($data);
        }

        $this->redirect(route('admin.staff.index'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">
        {{ $staff ? 'تعديل الحساب' : 'إضافة حساب' }}
    </h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">الاسم</label>
            <input type="text" wire:model="name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">البريد الإلكتروني</label>
            <input type="email" wire:model="email" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('email') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">
                {{ $staff ? 'كلمة مرور جديدة (اتركها فارغة لعدم التغيير)' : 'كلمة المرور' }}
            </label>
            <input type="password" wire:model="password" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('password') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        @if ($staff && $staff->id === auth()->id())
            <p class="rounded-lg bg-surface p-3 text-sm text-ink-soft">
                هذا حسابك الخاص — لا يمكنك تغيير مستواك الوظيفي أو إيقاف حسابك من هنا.
            </p>
        @else
            <div>
                <label class="block text-sm font-medium text-ink-soft">المستوى الوظيفي</label>
                <select wire:model="role" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                    <option value="super_admin">مدير النظام (صلاحيات كاملة)</option>
                    <option value="supervisor">مشرف النظام (بدون إدارة الحسابات)</option>
                </select>
                @error('role') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-1.5 text-sm text-ink-soft">
                <input type="checkbox" wire:model="is_active" class="rounded border-line-medium">
                الحساب مُفعّل
            </label>
        @endif

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">{{ $staff ? 'حفظ التغييرات' : 'إضافة الحساب' }}</span>
            <span wire:loading wire:target="save">جارٍ الحفظ...</span>
        </button>
    </form>
</div>
