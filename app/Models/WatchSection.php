<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchSection extends Model
{
    protected $fillable = [
        'watch_id',
        'title',
        'content',
        'sort_order',
    ];

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }
}