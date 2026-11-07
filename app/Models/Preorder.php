<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preorder extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['paid', 'balance', 'required_deposit', 'cost', 'payment_status', 'refundable'];

    protected function casts(): array
    {
        return ['total_price' => 'decimal:2', 'deposit_percentage' => 'decimal:2',
            'estimated_arrival' => 'date:Y-m-d', 'fulfilled_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function listing()
    {
        return $this->belongsTo(PreorderListing::class, 'preorder_listing_id');
    }

    public function entries()
    {
        return $this->hasMany(PreorderEntry::class)->orderByDesc('id');
    }

    public function fulfilledWatch()
    {
        return $this->belongsTo(Watch::class, 'fulfilled_watch_id');
    }

    public function cents(string $type): int
    {
        return (int) $this->entries->where('type', $type)->sum(fn ($entry) => (int) round((float) $entry->amount * 100));
    }

    public function getPaidAttribute(): float
    {
        return ($this->cents('payment') - $this->cents('refund')) / 100;
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (int) round((float) $this->total_price * 100) - (int) round($this->paid * 100)) / 100;
    }

    public function getRequiredDepositAttribute(): float
    {
        return max(0.01, round((float) $this->total_price * (float) $this->deposit_percentage / 100, 2));
    }

    public function getCostAttribute(): float
    {
        return $this->cents('cost') / 100;
    }

    public function getRefundableAttribute(): float
    {
        return max(0, $this->paid);
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return $this->paid > 0 ? 'refund_pending' : 'refunded';
        }

        return $this->balance <= 0 ? 'fully_paid' : ($this->paid > 0 ? 'partially_paid' : 'unpaid');
    }
}
