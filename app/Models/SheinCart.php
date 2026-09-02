<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class SheinCart extends Model
{
    use HasFactory;

    public const STATUSES = ['open', 'ordered', 'in_transit', 'arrived'];

    protected $fillable = [
        'cart_number',
        'cart_name',
        'customer_phone',
        'cart_details',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (SheinCart $cart) {
            if (! $cart->cart_number) {
                $cart->cart_number = static::generateCartNumber();
            }
        });
    }

    public static function generateCartNumber(): string
    {
        do {
            $number = 'MIRA-'.random_int(10000, 99999);
        } while (static::where('cart_number', $number)->exists());

        return $number;
    }

    /**
     * Create a cart, retrying with a fresh cart_number if a concurrent
     * request wins the race on the same randomly-generated number.
     */
    public static function createWithUniqueNumber(array $attributes, int $attempts = 3): self
    {
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return static::create($attributes);
            } catch (UniqueConstraintViolationException $e) {
                if ($i === $attempts) {
                    throw $e;
                }
            }
        }
    }
}
