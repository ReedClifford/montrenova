<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected static function booted(): void
    {
        $guard = function (Expense $expense) {
            if ($expense->preorder_id) throw \Illuminate\Validation\ValidationException::withMessages([
                'preorder' => 'This expense belongs to a cancelled preorder and cannot be edited or deleted.',
            ]);
        };
        static::updating($guard);
        static::deleting($guard);
    }

    protected $fillable = [
        'title',
        'category',
        'amount',
        'spent_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spent_at' => 'date',
    ];
}
