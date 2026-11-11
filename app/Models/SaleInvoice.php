<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInvoice extends Model
{
    protected $fillable = ['number', 'watch_id', 'sale_cycle', 'preorder_id', 'customer_token', 'snapshot', 'fingerprint'];

    protected $hidden = ['customer_token'];

    protected $casts = ['snapshot' => 'array', 'sale_cycle' => 'integer'];

    public const PRIVATE_HEADERS = [
        'Cache-Control' => 'private, no-store, max-age=0',
        'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        'Referrer-Policy' => 'no-referrer',
    ];

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }
}
