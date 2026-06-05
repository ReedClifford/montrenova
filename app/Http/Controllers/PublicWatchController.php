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
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($watch) => $this->publicWatchCard($watch));

        /*
        |--------------------------------------------------------------------------
        | Actual Sold Count
        |--------------------------------------------------------------------------
        |
        | This is the real total number of sold watches from the database.
        | Do not use soldWatches.length in Vue for the total sold count because
        | soldWatches below is only limited to the latest 12 display items.
        |
        */

        $soldCount = Watch::query()
            ->where('status', 'sold')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recently Sold Watches
        |--------------------------------------------------------------------------
        |
        | This is only for display on the public homepage.
        | It is intentionally limited so the page stays clean and fast.
        |
        */

        $soldWatches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->where('status', 'sold')
            ->where('is_visible', true)
            ->orderByRaw('COALESCE(date_sold, updated_at) DESC')
            ->limit(12)
            ->get()
            ->map(fn ($watch) => $this->publicWatchCard($watch));

            $soldCount = Watch::query()
    ->where('status', 'sold')
    ->count();

$soldThisMonthCount = Watch::query()
    ->where('status', 'sold')
    ->whereNotNull('date_sold')
    ->whereYear('date_sold', now()->year)
    ->whereMonth('date_sold', now()->month)
    ->count();

$soldWatches = Watch::query()
    ->with(['primaryImage'])
    ->where('status', 'sold')
    ->whereNotNull('date_sold')
    ->latest('date_sold')
    ->limit(8)
    ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => false,
            'featuredWatch' => $featuredWatch ? $this->publicWatchCard($featuredWatch) : null,
            'watches' => $watches,
            'soldWatches' => $soldWatches,
            'soldCount' => $soldCount,
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
            'date_sold' => $watch->date_sold?->toISOString(),
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



    public function soldGallery(\Illuminate\Http\Request $request)
{
    $search = trim((string) $request->query('search', ''));

    $soldWatches = \App\Models\Watch::query()
        ->with(['primaryImage', 'images'])
        ->where('status', 'sold')
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('condition', 'like', "%{$search}%");
            });
        })
        ->latest('date_sold')
        ->latest()
        ->paginate(12)
        ->withQueryString();

    return \Inertia\Inertia::render('Public/SoldGallery', [
        'soldWatches' => $soldWatches,
        'filters' => [
            'search' => $search,
        ],
        'soldCount' => \App\Models\Watch::query()
            ->where('status', 'sold')
            ->count(),
    ]);
}
}