<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $store_name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $whatsapp_number = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')],
        ];
    }

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'store_name' => $this->store_name,
            'email' => $this->email,
            'whatsapp_number' => $this->whatsapp_number,
            'password' => Hash::make($this->password),
            'role' => 'vendor',
        ]);

        Auth::login($user);

        $this->redirect(route('vendor.dashboard'), navigate: true);
    }
};
?>

<div class="mx-auto mt-8 max-w-md rounded-xl border border-line-medium p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">أنشئ متجرك</h1>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">اسمك</label>
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
            <label class="block text-sm font-medium text-ink-soft">كلمة المرور</label>
            <div class="relative mt-1.5" x-data="{ show: false }">
                <input
                    :type="show ? 'text' : 'password'"
                    wire:model="password"
                    class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 pe-11 text-base focus:border-black focus:ring-1 focus:ring-black"
                >
                <button
                    type="button"
                    x-on:click="show = !show"
                    class="absolute inset-y-0 end-0 flex items-center px-3 text-muted"
                    :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                >
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                </button>
            </div>
            @error('password') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">تأكيد كلمة المرور</label>
            <div class="relative mt-1.5" x-data="{ show: false }">
                <input
                    :type="show ? 'text' : 'password'"
                    wire:model="password_confirmation"
                    class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 pe-11 text-base focus:border-black focus:ring-1 focus:ring-black"
                >
                <button
                    type="button"
                    x-on:click="show = !show"
                    class="absolute inset-y-0 end-0 flex items-center px-3 text-muted"
                    :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                >
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                </button>
            </div>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="register"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="register">إنشاء المتجر</span>
            <span wire:loading wire:target="register">جارٍ إنشاء المتجر...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-muted">
        لديك حساب بالفعل؟ <a href="{{ route('login') }}" class="font-semibold text-primary">تسجيل الدخول</a>
    </p>
</div>
