<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicWatchController extends Controller
{
    public function welcome()
    {
        $featuredWatch = Watch::query()
            ->with(['primaryImage', 'images'])
            ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->latest('id')
            ->first();

        if (! $featuredWatch) {
            $featuredWatch = Watch::query()
                ->with(['primaryImage', 'images'])
                ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
                ->where('is_visible', true)
                ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('display_order')
                ->latest('id')
                ->first();
        }

        $watches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
            ->where('is_visible', true)
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($watch) => $this->publicWatchCard($watch));

        $soldCount = Watch::query()
            ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
            ->count();

        $soldThisMonthCount = Watch::query()
            ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
            ->whereNotNull('date_sold')
            ->whereBetween('date_sold', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->count();

        $soldWatches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
            ->orderByRaw('COALESCE(date_sold, updated_at, created_at) DESC')
            ->limit(8)
            ->get()
            ->map(fn ($watch) => $this->publicWatchCard($watch))
            ->values();

        $catalogSource = $this->uniquePublicCatalogWatches(
            $this->catalogBaseQuery()->get()
        );

        $catalogPreviewWatches = $this->catalogPreviewWatches($catalogSource)
            ->map(fn ($watch) => $this->publicWatchCard($watch))
            ->values()
            ->all();

        $catalogCategories = $this->catalogCategories($catalogSource)
            ->values()
            ->all();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => false,
            'featuredWatch' => $featuredWatch
                ? $this->publicWatchCard($featuredWatch)
                : null,
            'watches' => $watches,
            'soldWatches' => $soldWatches,
            'soldCount' => $soldCount,
            'soldThisMonthCount' => $soldThisMonthCount,
            'catalogPreviewWatches' => $catalogPreviewWatches,
            'catalogCategories' => $catalogCategories,
        ]);
    }

    public function show(Watch $watch)
    {
        abort_unless(
            strtolower(trim((string) $watch->status)) === 'available' && (bool) $watch->is_visible,
            404
        );

        $watch->load([
            'primaryImage',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'sections',
        ]);

        $availableWatches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->where('id', '!=', $watch->id)
            ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
            ->where('is_visible', true)
            ->orderByRaw('
                CASE
                    WHEN category = ? THEN 0
                    ELSE 1
                END
            ', [$watch->category])
            ->orderByDesc('is_featured')
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn ($item) => $this->publicWatchCard($item))
            ->values();

        return Inertia::render('Public/WatchShow', [
            'watch' => $this->publicWatchDetails($watch),
            'availableWatches' => $availableWatches,
            'relatedWatches' => $availableWatches,
            'canLogin' => Route::has('login'),
        ]);
    }

    public function catalog(Request $request)
    {
        $selectedCategory = trim((string) $request->query('category', 'all'));

        $catalogSource = $this->uniquePublicCatalogWatches(
            $this->catalogBaseQuery()->get()
        );

        $categories = $this->catalogCategories($catalogSource);

        $filteredWatches = $selectedCategory !== '' && Str::lower($selectedCategory) !== 'all'
            ? $catalogSource
                ->filter(function ($watch) use ($selectedCategory) {
                    return Str::lower(trim((string) $watch->category)) === Str::lower($selectedCategory);
                })
                ->values()
            : $catalogSource;

        return Inertia::render('Public/Catalog', [
            'watches' => $filteredWatches
                ->map(fn ($watch) => $this->publicWatchCard($watch))
                ->values()
                ->all(),
            'categories' => $categories
                ->values()
                ->all(),
            'activeCategory' => $selectedCategory ?: 'all',
            'categoryCounts' => $this->catalogCategoryCounts($catalogSource),
            'totalCatalogWatches' => $catalogSource->count(),
            'canLogin' => Route::has('login'),

            // temporary helpful debug values
            'catalogDebug' => [
                'database_watch_count' => Watch::query()->count(),
                'catalog_source_count' => $catalogSource->count(),
                'filtered_watch_count' => $filteredWatches->count(),
                'selected_category' => $selectedCategory ?: 'all',
            ],
        ]);
    }

    public function soldGallery(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $soldWatches = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('brand', 'like', "%{$search}%")
                        ->orWhere('model_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('condition', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('COALESCE(date_sold, updated_at, created_at) DESC')
            ->paginate(12)
            ->withQueryString()
            ->through(fn ($watch) => $this->publicWatchCard($watch));

        return Inertia::render('Public/SoldGallery', [
            'soldWatches' => $soldWatches,
            'filters' => [
                'search' => $search,
            ],
            'soldCount' => Watch::query()
                ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
                ->count(),
        ]);
    }

    private function catalogBaseQuery()
    {
        return Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->orderByRaw("
                CASE
                    WHEN LOWER(TRIM(COALESCE(status, 'available'))) = 'available' THEN 0
                    WHEN LOWER(TRIM(COALESCE(status, 'available'))) = 'reserved' THEN 1
                    WHEN LOWER(TRIM(COALESCE(status, 'available'))) = 'sold' THEN 2
                    ELSE 3
                END
            ")
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->latest('id');
    }

    private function uniquePublicCatalogWatches($watches)
    {
        return collect($watches)
            ->unique(fn ($watch) => $this->catalogDuplicateKey($watch))
            ->values();
    }

    private function catalogDuplicateKey($watch): string
    {
        $reference = trim((string) ($watch->reference_number ?? ''));

        if ($reference !== '') {
            return 'ref:' . Str::lower($reference);
        }

        $brand = trim((string) ($watch->brand ?? ''));
        $model = trim((string) ($watch->model_name ?? ''));
        $category = trim((string) ($watch->category ?? ''));

        $fallbackKey = trim($brand . '|' . $model . '|' . $category, '| ');

        if ($fallbackKey !== '') {
            return 'watch:' . Str::lower($fallbackKey);
        }

        return 'id:' . $watch->id;
    }

    private function catalogCategories($watches)
    {
        return collect($watches)
            ->pluck('category')
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique(fn ($category) => Str::lower($category))
            ->sort()
            ->values();
    }

    private function catalogCategoryCounts($watches)
    {
        return collect($watches)
            ->filter(fn ($watch) => trim((string) $watch->category) !== '')
            ->groupBy(fn ($watch) => trim((string) $watch->category))
            ->map(fn ($items) => $items->count())
            ->toArray();
    }

    private function catalogPreviewWatches($watches)
    {
        $withCategory = collect($watches)
            ->filter(fn ($watch) => trim((string) $watch->category) !== '')
            ->groupBy(fn ($watch) => Str::lower(trim((string) $watch->category)))
            ->map(fn ($items) => $items->first())
            ->values();

        if ($withCategory->isNotEmpty()) {
            return $withCategory->take(8)->values();
        }

        return collect($watches)->take(8)->values();
    }

    private function publicWatchCard(Watch $watch): array
    {
        $price = $this->listedPrice($watch);
        $primaryImage = $watch->primaryImage;

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
            'sold_price' => (float) ($watch->sold_price ?? 0),
            'price' => $price,
            'status' => $watch->status ?: 'available',
            'is_featured' => (bool) $watch->is_featured,
            'display_order' => (int) ($watch->display_order ?? 0),
            'created_at' => $this->formatDateTime($watch->created_at),
            'updated_at' => $this->formatDateTime($watch->updated_at),
            'date_sold' => $this->formatDateTime($watch->date_sold),
            'images_count' => $watch->images_count ?? $watch->images?->count() ?? 0,

            'primary_image_url' => $primaryImage?->image_url,
            'primary_hd_url' => $primaryImage?->hd_url,
            'image_url' => $primaryImage?->image_url,
            'thumbnail_url' => $primaryImage?->thumbnail_url,

            'primary_image' => $primaryImage ? [
                'id' => $primaryImage->id,
                'image_url' => $primaryImage->image_url,
                'hd_url' => $primaryImage->hd_url,
                'thumbnail_url' => $primaryImage->thumbnail_url,
                'is_primary' => (bool) $primaryImage->is_primary,
            ] : null,
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
            'status' => $watch->status ?: 'available',
            'is_featured' => (bool) $watch->is_featured,
            'display_order' => (int) ($watch->display_order ?? 0),
            'created_at' => $this->formatDateTime($watch->created_at),
            'updated_at' => $this->formatDateTime($watch->updated_at),
            'date_sold' => $this->formatDateTime($watch->date_sold),
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

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        return $value instanceof Carbon
            ? $value->toISOString()
            : Carbon::parse($value)->toISOString();
    }
}