<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Watch extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'capital_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
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
    ];

    public function images(): HasMany
    {
        return $this->hasMany(WatchImage::class)->orderBy('sort_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(WatchSection::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(WatchImage::class)->where('is_primary', true);
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = $this->discounted_price ?: $this->selling_price;

        return '₱' . number_format((float) $price, 2);
    }
}