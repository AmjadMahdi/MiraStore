<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
