<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreorderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['preorder_listing_id', 'price', 'fulfilled_watch_id'];

    public function listing()
    {
        return $this->belongsTo(PreorderListing::class, 'preorder_listing_id');
    }

    public function fulfilledWatch()
    {
        return $this->belongsTo(Watch::class, 'fulfilled_watch_id');
    }
}
