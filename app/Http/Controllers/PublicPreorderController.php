<?php

namespace App\Http\Controllers;

use App\Models\PreorderListing;
use Inertia\Inertia;

class PublicPreorderController extends Controller
{
    public function show(PreorderListing $listing)
    {
        abort_unless($listing->is_visible, 404);

        return Inertia::render('Public/PreorderShow', [
            'watch' => $listing->publicCard(),
            'relatedPreorders' => PreorderListing::where('is_visible', true)
                ->whereKeyNot($listing->id)->inRandomOrder()->limit(8)->get()->map->publicCard()->values(),
        ]);
    }
}
