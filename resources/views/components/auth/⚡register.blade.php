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

<div class="mx-auto max-w-sm p-6">
    <h1 class="text-xl font-semibold text-gray-800">Create your store</h1>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Your name</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Store name</label>
            <input type="text" wire:model="store_name" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('store_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">WhatsApp number</label>
            <input type="text" wire:model="whatsapp_number" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('whatsapp_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" wire:model="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Confirm password</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="register"
            class="w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="register">Create store</span>
            <span wire:loading wire:target="register">Creating store...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-500">
        Already have an account? <a href="{{ route('login') }}" class="font-semibold text-rose-600">Sign in</a>
    </p>
</div>
