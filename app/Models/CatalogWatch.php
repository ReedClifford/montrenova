<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogWatch extends Model
{
    protected $fillable = [
        'brand',
        'model_name',
        'reference_number',
        'category',
        'image_path',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $imagePath = trim($this->image_path);

        if (
            str_starts_with($imagePath, 'http://') ||
            str_starts_with($imagePath, 'https://') ||
            str_starts_with($imagePath, 'data:') ||
            str_starts_with($imagePath, 'blob:')
        ) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, '/')) {
            return $imagePath;
        }

        if (str_starts_with($imagePath, 'storage/')) {
            return asset($imagePath);
        }

        if (str_starts_with($imagePath, 'public/')) {
            $imagePath = preg_replace('/^public\//', '', $imagePath);
        }

        return asset('storage/' . ltrim($imagePath, '/'));
    }
}