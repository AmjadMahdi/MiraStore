<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SheinCartStatus extends Model
{
    protected $fillable = ['key', 'label', 'color', 'display_order'];

    /**
     * Fixed set of tones the customer-facing pill/dot can render as —
     * admins pick a status's meaning (open/info/etc.), not raw CSS, so the
     * actual Tailwind classes stay literal strings Tailwind's scanner can see.
     */
    public const PALETTE = [
        'muted' => ['pill' => 'bg-surface text-muted', 'dot' => 'bg-disabled'],
        'info' => ['pill' => 'bg-blue-50 text-info', 'dot' => 'bg-info'],
        'warning' => ['pill' => 'bg-amber-50 text-warning', 'dot' => 'bg-warning'],
        'success' => ['pill' => 'bg-green-50 text-success', 'dot' => 'bg-success'],
        'dark' => ['pill' => 'bg-primary text-white', 'dot' => 'bg-white'],
    ];

    public function pillClasses(): string
    {
        return static::PALETTE[$this->color]['pill'] ?? static::PALETTE['muted']['pill'];
    }

    public function dotClasses(): string
    {
        return static::PALETTE[$this->color]['dot'] ?? static::PALETTE['muted']['dot'];
    }

    /**
     * Generates a stable internal key, decoupled from the (Arabic) label
     * text — Str::slug() would just strip it down to nothing.
     */
    public static function generateKey(): string
    {
        do {
            $key = 'status_'.Str::random(8);
        } while (static::where('key', $key)->exists());

        return $key;
    }
}
