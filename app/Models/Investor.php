<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    protected $fillable = [
        'name',
        'capital_amount',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'capital_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}