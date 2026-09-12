<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class SheinCart extends Model
{
    use HasFactory;

    /**
     * The cart pipeline is admin-managed (see SheinCartStatus) rather than a
     * fixed list — these read the current, ordered set of statuses.
     */
    public static function statuses(): array
    {
        return SheinCartStatus::orderBy('display_order')->pluck('key')->all();
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return SheinCartStatus::orderBy('display_order')->pluck('label', 'key')->all();
    }

    protected $fillable = [
        'cart_number',
        'cart_name',
        'description',
        'customer_phone',
        'cart_details',
        'status',
        'is_locked',
        'public_token',
        'accepts_submissions',
    ];

    protected $attributes = [
        'is_locked' => false,
        'accepts_submissions' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'accepts_submissions' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SheinCartItem::class);
    }

    /**
     * Mark this cart as the one open cart that public homepage submissions
     * (via the Hero "Add Link" flow) go into, unmarking any other cart that
     * previously held that role — only one cart can accept submissions at a time.
     */
    public function enableSubmissions(): void
    {
        static::where('id', '!=', $this->id)->update(['accepts_submissions' => false]);
        $this->update(['accepts_submissions' => true]);
    }

    public function disableSubmissions(): void
    {
        $this->update(['accepts_submissions' => false]);
    }

    /**
     * Enable a public, unauthenticated read-only link for this cart,
     * generating a fresh unguessable token (retrying past a collision).
     */
    public function enablePublicLink(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $token = Str::random(40);

            if (! static::where('public_token', $token)->exists()) {
                $this->update(['public_token' => $token]);

                return;
            }
        }
    }

    public function disablePublicLink(): void
    {
        $this->update(['public_token' => null]);
    }

    protected static function booted(): void
    {
        static::creating(function (SheinCart $cart) {
            if (! $cart->cart_number) {
                $cart->cart_number = static::generateCartNumber();
            }

            // The DB column still carries a plain 'open' default as a
            // last-resort safety net, but the real source of truth for
            // which statuses exist is the admin-managed SheinCartStatus
            // table — so a fresh cart should get whichever status is
            // currently first in that list, not a value that could have
            // since been renamed or removed.
            if (! $cart->status) {
                $cart->status = static::statuses()[0] ?? 'open';
            }
        });
    }

    public const CART_NUMBER_START = 10;

    /**
     * Sequential, e.g. mira-10, mira-11, mira-12... — picks up from the
     * highest existing "mira-N" number rather than a counter column, so it
     * stays correct even if old MIRA-XXXXX carts are mixed in.
     */
    public static function generateCartNumber(): string
    {
        $maxNumber = static::query()
            ->where('cart_number', 'like', 'mira-%')
            ->pluck('cart_number')
            ->map(fn (string $number) => (int) Str::after($number, 'mira-'))
            ->max();

        $next = $maxNumber !== null ? $maxNumber + 1 : static::CART_NUMBER_START;

        return 'mira-'.max($next, static::CART_NUMBER_START);
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
