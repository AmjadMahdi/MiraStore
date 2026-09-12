<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    public static function getArray(string $key, array $default = []): array
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * @param  array<int, string>  $value
     */
    public static function setArray(string $key, array $value): void
    {
        static::set($key, json_encode(array_values($value), JSON_UNESCAPED_UNICODE));
    }
}
