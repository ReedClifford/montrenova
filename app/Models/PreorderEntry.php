<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreorderEntry extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['proof_path'];

    protected $appends = ['has_proof'];

    public function getHasProofAttribute(): bool
    {
        return (bool) $this->proof_path;
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date:Y-m-d'];
    }

    public function preorder()
    {
        return $this->belongsTo(Preorder::class);
    }
}
