<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class PublicWatchController extends Controller
{
    public function welcome()
    {
        $featuredWatch = Watch::query()
            ->with(['primaryImage', 'images'])
            ->where('status', 'available')
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->latest()
            ->first();

        if (! $featuredWatch) {
            $featuredWatch = Watch::query()
                ->with(['primaryImage', 'images'])
                ->where('status', 'available')
                ->where('is_visible', true)
                ->latest()
                ->first();
        }

        $watches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->where('status', 'available')
            ->where('is_visible', true)
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->through(fn ($watch) => $this->publicWatchCard($watch));

        $soldWatches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->where('status', 'sold')
            ->where('is_visible', true)
            ->latest('updated_at')
            ->limit(12)
            ->get()
            ->map(fn ($watch) => $this->publicWatchCard($watch));

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => false,
            'featuredWatch' => $featuredWatch ? $this->publicWatchCard($featuredWatch) : null,
            'watches' => $watches,
            'soldWatches' => $soldWatches,
        ]);
    }

    public function show(Watch $watch)
    {
        abort_unless($watch->status === 'available' && (bool) $watch->is_visible, 404);

        $watch->load([
            'primaryImage',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'sections',
        ]);

        return Inertia::render('Public/WatchShow', [
            'watch' => $this->publicWatchDetails($watch),
            'canLogin' => Route::has('login'),
        ]);
    }

    private function publicWatchCard(Watch $watch): array
    {
        $price = $this->listedPrice($watch);

        return [
            'id' => $watch->id,
            'brand' => $watch->brand,
            'model_name' => $watch->model_name,
            'reference_number' => $watch->reference_number,
            'condition' => $watch->condition,
            'category' => $watch->category,
            'description' => $watch->description,
            'selling_price' => (float) ($watch->selling_price ?? 0),
            'discounted_price' => $watch->discounted_price ? (float) $watch->discounted_price : null,
            'price' => $price,
            'status' => $watch->status,
            'is_featured' => (bool) $watch->is_featured,
            'created_at' => $watch->created_at?->toISOString(),
            'images_count' => $watch->images_count ?? $watch->images?->count() ?? 0,
            'primary_image_url' => $watch->primaryImage?->image_url,
            'primary_hd_url' => $watch->primaryImage?->hd_url,
        ];
    }

    private function publicWatchDetails(Watch $watch): array
    {
        return [
            'id' => $watch->id,
            'brand' => $watch->brand,
            'model_name' => $watch->model_name,
            'reference_number' => $watch->reference_number,
            'condition' => $watch->condition,
            'category' => $watch->category,
            'description' => $watch->description,
            'movement' => $watch->movement,
            'case_size' => $watch->case_size,
            'case_material' => $watch->case_material,
            'dial_color' => $watch->dial_color,
            'crystal' => $watch->crystal,
            'bracelet_or_strap' => $watch->bracelet_or_strap,
            'water_resistance' => $watch->water_resistance,
            'box_papers' => $watch->box_papers,
            'warranty_type' => $watch->warranty_type,
            'selling_price' => (float) ($watch->selling_price ?? 0),
            'discounted_price' => $watch->discounted_price ? (float) $watch->discounted_price : null,
            'price' => $this->listedPrice($watch),
            'status' => $watch->status,
            'is_featured' => (bool) $watch->is_featured,
            'images' => $watch->images->map(fn ($image) => [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'hd_url' => $image->hd_url,
                'thumbnail_url' => $image->thumbnail_url,
                'is_primary' => (bool) $image->is_primary,
            ])->values(),
            'sections' => $watch->sections->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'body' => $section->body,
            ])->values(),
        ];
    }

    private function listedPrice(Watch $watch): float
    {
        if ($watch->discounted_price && (float) $watch->discounted_price > 0) {
            return (float) $watch->discounted_price;
        }

        return (float) ($watch->selling_price ?? 0);
    }
}