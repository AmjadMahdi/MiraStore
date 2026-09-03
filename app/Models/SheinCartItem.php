<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SheinCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shein_cart_id',
        'name',
        'quantity',
        'link',
        'item_date',
    ];

    protected $attributes = [
        'quantity' => 1,
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'item_date' => 'date',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(SheinCart::class, 'shein_cart_id');
    }
}
