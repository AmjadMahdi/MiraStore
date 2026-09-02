<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected static array $colors = [
        '#f43f5e', '#3b82f6', '#10b981', '#f59e0b',
        '#8b5cf6', '#ec4899', '#14b8a6', '#fb7185',
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->images()->create(['path' => $product->image_path, 'sort_order' => 0]);

            $extra = random_int(1, 3);

            for ($i = 1; $i <= $extra; $i++) {
                $product->images()->create(['path' => static::placeholderImage(), 'sort_order' => $i]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 3, 60);
        $hasDiscount = $this->faker->boolean(30);

        return [
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->paragraph(),
            'price' => $price,
            'compare_at_price' => $hasDiscount ? round($price * 1.25, 2) : null,
            'image_path' => static::placeholderImage(),
            'options' => $this->faker->boolean(50) ? 'Sizes: S, M, L' : null,
            'status' => 'approved',
            'stock_status' => $this->faker->randomElement(['in_stock', 'in_stock', 'in_stock', 'pre_order', 'out_of_stock']),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'rejection_reason' => $this->faker->randomElement([
                'Image quality too low',
                'Price does not match description',
                'Duplicate listing',
            ]),
        ]);
    }

    public static function placeholderImage(): string
    {
        $color = static::$colors[array_rand(static::$colors)];

        $image = ImageManager::gd()->create(800, 800)->fill($color);

        $path = 'products/demo-'.uniqid().'.jpg';

        Storage::disk('public')->put($path, (string) $image->toJpeg(80));

        return $path;
    }
}
