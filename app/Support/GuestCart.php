<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GuestCart
{
    protected const SESSION_KEY = 'shein_cart_items';

    public static function items(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public static function add(string $code, int $quantity = 1, ?string $notes = null): void
    {
        $items = self::items();
        $items[] = [
            'id' => (string) Str::uuid(),
            'code' => $code,
            'quantity' => max(1, $quantity),
            'notes' => $notes,
        ];
        Session::put(self::SESSION_KEY, $items);
    }

    public static function remove(string $id): void
    {
        $items = array_values(array_filter(
            self::items(),
            fn (array $item) => $item['id'] !== $id
        ));

        Session::put(self::SESSION_KEY, $items);
    }

    public static function count(): int
    {
        return count(self::items());
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
