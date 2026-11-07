<?php

namespace App\Http\Controllers;

use App\Models\PreorderListing;
use Inertia\Inertia;

class PublicPreorderController extends Controller
{
    public function show(PreorderListing $listing)
    {
        abort_unless($listing->is_visible, 404);

        return Inertia::render('Public/PreorderShow', ['watch' => $listing->publicCard()]);
    }
}
