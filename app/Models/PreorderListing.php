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
            'created_at' => $this->created_at?->toISOString(),
        ]);
    }
}
