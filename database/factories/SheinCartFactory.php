<?php

namespace Database\Factories;

use App\Models\SheinCart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SheinCart>
 */
class SheinCartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_name' => $this->faker->randomElement([
                'Summer Outfits', 'Winter Essentials', 'My SHEIN Order',
                'Back to School', 'Eid Shopping', 'Birthday Gifts',
            ]),
            'customer_phone' => '+9677'.$this->faker->numerify('#######'),
            'cart_details' => 'https://shein.com/cart/'.$this->faker->uuid(),
            'status' => $this->faker->randomElement(SheinCart::STATUSES),
        ];
    }
}
