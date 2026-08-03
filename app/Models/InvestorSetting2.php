<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorSetting2 extends Model
{
    use HasFactory;

    protected $table = 'investor_settings2';

    protected $fillable = [
        'setting_key',
        'capital_amount',
        'investor_profit_percentage',
        'management_fee_percentage',
        'brand_cut_percentage',
        'investment_start_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'capital_amount' => 'decimal:2',
            'investor_profit_percentage' => 'decimal:4',
            'management_fee_percentage' => 'decimal:4',
            'brand_cut_percentage' => 'decimal:4',
            'investment_start_date' => 'date',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
