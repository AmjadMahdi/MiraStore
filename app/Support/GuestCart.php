<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GuestCart
{
    protected const SESSION_KEY = 'shein_cart_items';

    protected const CART_NAME_KEY = 'shein_cart_name';

    protected const CUSTOMER_PHONE_KEY = 'shein_cart_customer_phone';

    public static function items(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public static function add(string $code, int $quantity = 1, ?string $notes = null, ?string $date = null): void
    {
        $items = self::items();
        $items[] = [
            'id' => (string) Str::uuid(),
            'code' => $code,
            'quantity' => max(1, $quantity),
            'notes' => $notes,
            'date' => $date,
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

    public static function cartName(): string
    {
        return Session::get(self::CART_NAME_KEY, '');
    }

    public static function setCartName(string $name): void
    {
        Session::put(self::CART_NAME_KEY, $name);
    }

    public static function customerPhone(): string
    {
        return Session::get(self::CUSTOMER_PHONE_KEY, '');
    }

    public static function setCustomerPhone(string $phone): void
    {
        Session::put(self::CUSTOMER_PHONE_KEY, $phone);
    }

    public static function clear(): void
    {
        Session::forget([self::SESSION_KEY, self::CART_NAME_KEY, self::CUSTOMER_PHONE_KEY]);
    }
}
