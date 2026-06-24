<?php

namespace App\Http\Controllers;

use App\Models\CatalogWatch;
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
        $featuredWatches = Watch::query()
            ->with(['primaryImage', 'images'])
            ->withCount('images')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['available'])
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->orderByRaw('CASE WHEN display_order IS NULL OR display_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->latest('id')
            ->limit(6)
            ->get();

        $featuredWatch = $featuredWatches->first();

        if (! $featuredWatch) {
            $featuredWatch = Watch::query()
                ->with(['primaryImage', 'images'])
                ->withCount('images')
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

        $soldWatchPool = Watch::query()
            ->with(['primaryImage'])
            ->withCount('images')
            ->whereRaw('LOWER(TRIM(status)) = ?', ['sold'])
            ->orderByRaw('COALESCE(date_sold, updated_at, created_at) DESC')
            ->limit(300)
            ->get();

        $soldWatches = $soldWatchPool
            ->take(8)
            ->map(fn ($watch) => $this->publicWatchCard($watch))
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Hero Best Seller Carousel
        |--------------------------------------------------------------------------
        | Top 5 best-selling models by number of sold records.
        |
        | Grouping priority:
        | 1. reference_number when available
        | 2. brand + model_name if reference_number is blank
        |
        | Hero images are manually assigned by ranking:
        | #1 => public/images/rank1.jpg
        | #2 => public/images/rank2.jpg
        | #3 => public/images/rank3.jpg
        | #4 => public/images/rank4.jpg
        | #5 => public/images/rank5.jpg
        */
        $bestSellerWatches = $soldWatchPool
            ->groupBy(fn ($watch) => $this->bestSellerModelKey($watch))
            ->map(function ($items) {
                $latestSoldWatch = $items
                    ->sortByDesc(fn ($watch) => $this->soldSortTimestamp($watch))
                    ->first();

                return [
                    'watch' => $latestSoldWatch,
                    'sold_count' => $items->count(),
                    'latest_sold_timestamp' => $this->soldSortTimestamp($latestSoldWatch),
                ];
            })
            ->sort(function ($a, $b) {
                return ($b['sold_count'] <=> $a['sold_count'])
                    ?: ($b['latest_sold_timestamp'] <=> $a['latest_sold_timestamp']);
            })
            ->take(5)
            ->values()
            ->map(function ($item, $index) {
                $rank = $index + 1;
                $card = $this->publicWatchCard($item['watch']);
                $rankWristShotUrl = $this->bestSellerRankWristShotUrl($rank);

                $card['sold_count'] = $item['sold_count'];
                $card['best_seller_rank'] = $rank;
                $card['wristshot_image_url'] = $rankWristShotUrl;
                $card['hero_image_url'] = $rankWristShotUrl
                    ?: ($card['primary_hd_url'] ?? $card['primary_image_url'] ?? $card['image_url'] ?? null);

                return $card;
            })
            ->values();

        if ($bestSellerWatches->isEmpty()) {
            $bestSellerWatches = $featuredWatches
                ->take(5)
                ->map(function ($watch, $index) {
                    $rank = $index + 1;
                    $card = $this->publicWatchCard($watch);
                    $rankWristShotUrl = $this->bestSellerRankWristShotUrl($rank);

                    $card['sold_count'] = 0;
                    $card['best_seller_rank'] = $rank;
                    $card['wristshot_image_url'] = $rankWristShotUrl;
                    $card['hero_image_url'] = $rankWristShotUrl
                        ?: ($card['primary_hd_url'] ?? $card['primary_image_url'] ?? $card['image_url'] ?? null);

                    return $card;
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Catalog Preview
        |--------------------------------------------------------------------------
        | This is now from catalog_watches table, not watches table.
        */
        $catalogSource = $this->uniquePublicCatalogWatches(
            $this->catalogBaseQuery()->get()
        );

        $catalogPreviewWatches = $this->catalogPreviewWatches($catalogSource)
            ->map(fn ($watch) => $this->publicCatalogWatchCard($watch))
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
            'featuredWatches' => $featuredWatches
                ->map(fn ($watch) => $this->publicWatchCard($watch))
                ->values()
                ->all(),
            'bestSellerWatches' => $bestSellerWatches->all(),
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
                ->map(fn ($watch) => $this->publicCatalogWatchCard($watch))
                ->values()
                ->all(),
            'categories' => $categories
                ->values()
                ->all(),
            'activeCategory' => $selectedCategory ?: 'all',
            'categoryCounts' => $this->catalogCategoryCounts($catalogSource),
            'totalCatalogWatches' => $catalogSource->count(),
            'canLogin' => Route::has('login'),
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
        return CatalogWatch::query()
            ->where('is_visible', true)
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
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
            return 'catalog:' . Str::lower($fallbackKey);
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

    private function bestSellerRankWristShotUrl(int $rank): ?string
    {
        $rankWristShots = [
            1 => 'images/rank1.jpg',
            2 => 'images/rank2.jpg',
            3 => 'images/rank3.jpg',
            4 => 'images/rank4.jpg',
            5 => 'images/rank5.jpg',
        ];

        return $this->publicAssetUrlIfExists($rankWristShots[$rank] ?? null);
    }

    private function publicAssetUrlIfExists(?string $path): ?string
    {
        $cleanPath = ltrim(trim((string) $path), '/');

        if ($cleanPath === '') {
            return null;
        }

        return file_exists(public_path($cleanPath))
            ? asset($cleanPath)
            : null;
    }

    private function bestSellerModelKey(Watch $watch): string
    {
        $reference = Str::lower(trim((string) ($watch->reference_number ?? '')));

        if ($reference !== '') {
            return 'ref:' . $reference;
        }

        $brand = Str::lower(trim((string) ($watch->brand ?? '')));
        $model = Str::lower(trim((string) ($watch->model_name ?? '')));
        $fallbackKey = trim($brand . '|' . $model, '| ');

        if ($fallbackKey !== '') {
            return 'model:' . $fallbackKey;
        }

        return 'id:' . $watch->id;
    }

    private function soldSortTimestamp(?Watch $watch): int
    {
        if (! $watch) {
            return 0;
        }

        $value = $watch->date_sold ?: $watch->updated_at ?: $watch->created_at;

        if (! $value) {
            return 0;
        }

        return $value instanceof Carbon
            ? $value->getTimestamp()
            : Carbon::parse($value)->getTimestamp();
    }

    private function publicCatalogWatchCard(CatalogWatch $watch): array
    {
        return [
            'id' => $watch->id,
            'brand' => $watch->brand ?: 'Seiko',
            'model_name' => $watch->model_name,
            'reference_number' => $watch->reference_number,
            'condition' => null,
            'category' => $watch->category,
            'description' => null,

            'selling_price' => 0,
            'discounted_price' => null,
            'sold_price' => 0,
            'price' => 0,

            'status' => 'available',
            'is_featured' => false,
            'display_order' => (int) ($watch->sort_order ?? 0),
            'created_at' => $this->formatDateTime($watch->created_at),
            'updated_at' => $this->formatDateTime($watch->updated_at),
            'date_sold' => null,
            'images_count' => $watch->image_url ? 1 : 0,

            'primary_image_url' => $watch->image_url,
            'primary_hd_url' => $watch->image_url,
            'image_url' => $watch->image_url,
            'thumbnail_url' => $watch->image_url,

            'primary_image' => $watch->image_url ? [
                'id' => null,
                'image_url' => $watch->image_url,
                'hd_url' => $watch->image_url,
                'thumbnail_url' => $watch->image_url,
                'is_primary' => true,
            ] : null,

            'is_catalog_item' => true,
        ];
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