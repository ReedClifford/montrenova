<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreorderListing extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['watch_snapshot' => 'array', 'price' => 'decimal:2', 'is_visible' => 'boolean'];
    }

    public function getWatchSnapshotAttribute($value): array
    {
        $snapshot = is_array($value) ? $value : (json_decode($value ?? '{}', true) ?: []);
        $snapshot['images'] = collect($snapshot['images'] ?? [])->filter(fn ($image) => is_array($image))
            ->map(function (array $image): array {
                if (! empty($image['path'])) {
                    // Resolve against the current request rather than a host saved at publication.
                    $image['image_url'] = asset('storage/'.ltrim($image['path'], '/'));
                    $image['hd_url'] = $image['image_url'];
                }

                return $image;
            })->values()->all();
        $snapshot['primary_image_url'] = $snapshot['images'][0]['image_url'] ?? ($snapshot['primary_image_url'] ?? null);

        return $snapshot;
    }

    public function sourceWatch()
    {
        return $this->belongsTo(Watch::class, 'source_watch_id');
    }

    public function orders()
    {
        return $this->hasMany(Preorder::class);
    }

    public function publicCard(): array
    {
        return array_merge($this->watch_snapshot, [
            'id' => 'preorder-'.$this->id,
            'status' => 'preorder',
            'selling_price' => (float) $this->price,
            'discounted_price' => null,
            'price' => (float) $this->price,
            'url' => route('public.preorders.show', $this),
            'estimated_arrival' => $this->estimated_arrival,
            'warranty_years' => Watch::DEFAULT_WARRANTY_YEARS,
            'created_at' => $this->created_at?->toISOString(),
        ]);
    }
}
