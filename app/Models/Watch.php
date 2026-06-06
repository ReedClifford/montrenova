<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Watch extends Model
{
    protected $fillable = [
        'display_order',
        'stock_code',
        'brand',
        'model_name',
        'reference_number',
        'slug',
        'condition',
        'category',
        'description',
        'movement',
        'case_size',
        'case_material',
        'dial_color',
        'crystal',
        'bracelet_or_strap',
        'water_resistance',
        'box_papers',
        'warranty_type',
        'capital_price',
        'selling_price',
        'discounted_price',
        'sold_price',
        'status',
        'is_featured',
        'is_visible',
        'display_price',
        'allow_inquiry',
        'date_acquired',
        'date_sold',
        'reserved_customer_name',
        'reserved_contact_number',
        'reservation_date',
        'reservation_deadline',
        'reservation_notes',
        'buyer_name',
        'serial_number',
    ];

    protected $casts = [
        'capital_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'display_price' => 'boolean',
        'allow_inquiry' => 'boolean',
        'date_acquired' => 'date',
        'date_sold' => 'date',
        'reservation_date' => 'date',
        'reservation_deadline' => 'date',
    ];

    protected $appends = [
        'formatted_price',
        'formatted_encoded_date',
        'formatted_sold_date',
        'final_public_price',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(WatchImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(WatchSection::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(WatchImage::class)
            ->where('is_primary', true)
            ->orderBy('sort_order');
    }

    public function getFinalPublicPriceAttribute(): float
    {
        if ($this->discounted_price && (float) $this->discounted_price > 0) {
            return (float) $this->discounted_price;
        }

        return (float) $this->selling_price;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₱' . number_format((float) $this->final_public_price, 2);
    }

    public function getFormattedEncodedDateAttribute(): string
    {
        return $this->created_at
            ? $this->created_at->format('M d, Y')
            : '';
    }

    public function getFormattedSoldDateAttribute(): string
    {
        return $this->date_sold
            ? $this->date_sold->format('M d, Y')
            : '';
    }
}