<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WatchImage extends Model
{
    protected $fillable = [
        'watch_id',
        'image_path',
        'hd_path',
        'thumbnail_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'hd_url',
        'thumbnail_url',
    ];

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image_path
            ? Storage::url($this->image_path)
            : '';
    }

    public function getHdUrlAttribute(): string
    {
        return Storage::url($this->hd_path ?: $this->image_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::url($this->thumbnail_path ?: $this->image_path);
    }
}