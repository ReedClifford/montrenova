<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorSetting extends Model
{
    protected $fillable = [
        'setting_key',

        /*
         * Legacy fields.
         * Retained to remain compatible with the existing table.
         * They are no longer used for investor allocation.
         */
        'capital_amount',
        'investor_profit_percentage',
        'management_fee_percentage',

        'brand_cut_percentage',
        'investment_start_date',
        'updated_by',
    ];

    protected $casts = [
        'capital_amount' => 'decimal:2',
        'investor_profit_percentage' => 'decimal:2',
        'management_fee_percentage' => 'decimal:2',
        'brand_cut_percentage' => 'decimal:2',
        'investment_start_date' => 'date',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}