<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'vendor',
            'store_name' => ucfirst(fake()->unique()->words(2, true)).' Store',
            'whatsapp_number' => '+9677'.fake()->numerify('#######'),
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_products_limit' => null,
            'is_verified' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
