<?php

namespace App\Http\Controllers;

use App\Models\CatalogWatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicCatalogController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategory = trim((string) $request->query('category', 'all'));

        $catalogSource = CatalogWatch::query()
            ->where('is_visible', true)
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->unique(fn ($watch) => $this->duplicateKey($watch))
            ->values();

        $filteredWatches = $selectedCategory !== '' && Str::lower($selectedCategory) !== 'all'
            ? $catalogSource
                ->filter(function ($watch) use ($selectedCategory) {
                    return Str::lower(trim((string) $watch->category)) === Str::lower($selectedCategory);
                })
                ->values()
            : $catalogSource;

        $categories = $catalogSource
            ->pluck('category')
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique(fn ($category) => Str::lower($category))
            ->sort()
            ->values();

        $categoryCounts = $catalogSource
            ->filter(fn ($watch) => trim((string) $watch->category) !== '')
            ->groupBy(fn ($watch) => trim((string) $watch->category))
            ->map(fn ($items) => $items->count())
            ->toArray();

        return Inertia::render('Public/Catalog', [
            'watches' => $filteredWatches
                ->map(fn ($watch) => $this->catalogWatchCard($watch))
                ->values()
                ->all(),

            'categories' => $categories
                ->values()
                ->all(),

            'activeCategory' => $selectedCategory ?: 'all',
            'categoryCounts' => $categoryCounts,
            'totalCatalogWatches' => $catalogSource->count(),
            'canLogin' => Route::has('login'),

            'catalogDebug' => [
                'database_catalog_watch_count' => CatalogWatch::query()->count(),
                'visible_catalog_watch_count' => CatalogWatch::query()
                    ->where('is_visible', true)
                    ->count(),
                'catalog_source_count' => $catalogSource->count(),
                'filtered_watch_count' => $filteredWatches->count(),
                'selected_category' => $selectedCategory ?: 'all',
            ],
        ]);
    }

    private function catalogWatchCard(CatalogWatch $watch): array
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
            'created_at' => optional($watch->created_at)->toISOString(),
            'updated_at' => optional($watch->updated_at)->toISOString(),
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

    private function duplicateKey(CatalogWatch $watch): string
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
}