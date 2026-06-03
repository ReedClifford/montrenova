<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public static function getDecimal(string $key, float $default = 0): float
    {
        return (float) optional(static::where('key', $key)->first())->value ?: $default;
    }

    public static function setDecimal(string $key, float $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}