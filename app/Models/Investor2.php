<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor2 extends Model
{
    use HasFactory;

    protected $table = 'investors2';

    protected $fillable = [
        'name',
        'capital_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capital_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
