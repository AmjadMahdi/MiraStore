<?php

namespace Database\Factories;

use App\Models\InteractionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InteractionLog>
 */
class InteractionLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action_type' => $this->faker->randomElement(['view', 'whatsapp_click', 'phone_call']),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
