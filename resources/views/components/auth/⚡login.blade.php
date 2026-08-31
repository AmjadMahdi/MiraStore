<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $this->addError('email', 'Your account has been suspended.');

            return;
        }

        session()->regenerate();

        $this->redirect($user->isSuperAdmin() ? route('admin.dashboard') : route('vendor.dashboard'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-sm p-6">
    <h1 class="text-xl font-semibold text-gray-800">Sign in</h1>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" wire:model="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="login"
            class="w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in...</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-500">
        Vendor? <a href="{{ route('register') }}" class="font-semibold text-rose-600">Create a store</a>
    </p>
</div>
