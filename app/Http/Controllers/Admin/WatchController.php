<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Watch;
use App\Models\WatchImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WatchController extends Controller
{
    // public function index(Request $request)
    // {
    //     $search = trim((string) $request->input('search', ''));
    //     $status = $request->input('status', '');

    //     $watches = Watch::query()
    //         ->with(['primaryImage', 'images', 'sections'])
    //         ->withCount('images')
    //         ->when($search, function ($query) use ($search) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->where('brand', 'like', "%{$search}%")
    //                     ->orWhere('model_name', 'like', "%{$search}%")
    //                     ->orWhere('reference_number', 'like', "%{$search}%")
    //                     ->orWhere('condition', 'like', "%{$search}%")
    //                     ->orWhere('category', 'like', "%{$search}%");
    //             });
    //         })
    //         ->when($status, fn ($query) => $query->where('status', $status))
    //         ->latest()
    //         ->paginate(10)
    //         ->withQueryString();

    //     $activeInventoryQuery = Watch::query()
    //         ->where('status', '!=', 'sold');

    //     $inventoryCapital = (float) (clone $activeInventoryQuery)
    //         ->sum('capital_price');

    //     $expectedSalesValue = (float) (clone $activeInventoryQuery)
    //         ->selectRaw('
    //             COALESCE(
    //                 SUM(
    //                     CASE
    //                         WHEN discounted_price IS NOT NULL AND discounted_price > 0
    //                         THEN discounted_price
    //                         ELSE selling_price
    //                     END
    //                 ),
    //             0) as total
    //         ')
    //         ->value('total');

    //     $expectedProfit = $expectedSalesValue - $inventoryCapital;

    //     return Inertia::render('Admin/Watches/Index', [
    //         'watches' => $watches,
    //         'filters' => [
    //             'search' => $search,
    //             'status' => $status,
    //         ],
    //         'summary' => [
    //             'total_watches' => Watch::count(),
    //             'available_watches' => Watch::where('status', 'available')->count(),
    //             'reserved_watches' => Watch::where('status', 'reserved')->count(),
    //             'sold_watches' => Watch::where('status', 'sold')->count(),
    //             'draft_hidden_watches' => Watch::whereIn('status', ['draft', 'hidden'])->count(),
    //             'inventory_capital' => $inventoryCapital,
    //             'expected_sales_value' => $expectedSalesValue,
    //             'expected_profit' => $expectedProfit,
    //         ],
    //     ]);
    // }


    public function index(Request $request)
{
    $search = trim((string) $request->input('search', ''));
    $status = $request->input('status', '');

    $watches = Watch::query()
        ->with(['primaryImage', 'images', 'sections'])
        ->withCount('images')
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('condition', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        })
        ->when($status, fn ($query) => $query->where('status', $status))
        ->latest()
        ->paginate(10)
        ->withQueryString();

    $warrantyWatches = Watch::query()
        ->select([
            'id',
            'brand',
            'model_name',
            'reference_number',
            'serial_number',
            'buyer_name',
            'sold_price',
            'date_sold',
            'status',
        ])
        ->where('status', 'sold')
        ->whereNotNull('date_sold')
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        })
        ->latest('date_sold')
        ->paginate(10, ['*'], 'warranty_page')
        ->withQueryString()
        ->through(function ($watch) {
            $dateSold = Carbon::parse($watch->date_sold);
            $warrantyEndDate = $dateSold->copy()->addYear();
            $daysLeft = now()->startOfDay()->diffInDays($warrantyEndDate->copy()->startOfDay(), false);

            if ($daysLeft < 0) {
                $warrantyStatus = 'expired';
            } elseif ($daysLeft <= 30) {
                $warrantyStatus = 'expiring_soon';
            } else {
                $warrantyStatus = 'active';
            }

            return [
                'id' => $watch->id,
                'brand' => $watch->brand,
                'model_name' => $watch->model_name,
                'reference_number' => $watch->reference_number,
                'serial_number' => $watch->serial_number,
                'buyer_name' => $watch->buyer_name,
                'sold_price' => $watch->sold_price,
                'date_sold' => $dateSold->format('Y-m-d'),
                'warranty_start_date' => $dateSold->format('Y-m-d'),
                'warranty_end_date' => $warrantyEndDate->format('Y-m-d'),
                'warranty_days_left' => $daysLeft,
                'warranty_status' => $warrantyStatus,
            ];
        });

    $activeInventoryQuery = Watch::query()
        ->where('status', '!=', 'sold');

    $inventoryCapital = (float) (clone $activeInventoryQuery)
        ->sum('capital_price');

    $expectedSalesValue = (float) (clone $activeInventoryQuery)
        ->selectRaw('
            COALESCE(
                SUM(
                    CASE
                        WHEN discounted_price IS NOT NULL AND discounted_price > 0
                        THEN discounted_price
                        ELSE selling_price
                    END
                ),
            0) as total
        ')
        ->value('total');

    $expectedProfit = $expectedSalesValue - $inventoryCapital;

    return Inertia::render('Admin/Watches/Index', [
        'watches' => $watches,
        'warrantyWatches' => $warrantyWatches,
        'filters' => [
            'search' => $search,
            'status' => $status,
        ],
        'summary' => [
            'total_watches' => Watch::count(),
            'available_watches' => Watch::where('status', 'available')->count(),
            'reserved_watches' => Watch::where('status', 'reserved')->count(),
            'sold_watches' => Watch::where('status', 'sold')->count(),
            'draft_hidden_watches' => Watch::whereIn('status', ['draft', 'hidden'])->count(),
            'inventory_capital' => $inventoryCapital,
            'expected_sales_value' => $expectedSalesValue,
            'expected_profit' => $expectedProfit,
        ],
    ]);
}

    public function create()
    {
        return redirect()->route('admin.watches.index', [
            'create' => 1,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateWatch($request);

        if (($validated['status'] ?? null) === 'sold' && empty($validated['date_sold'])) {
            $validated['date_sold'] = now()->toDateString();
        }

        if (($validated['status'] ?? null) === 'sold' && empty($validated['sold_price'])) {
            $validated['sold_price'] = $validated['discounted_price']
                ?: $validated['selling_price'];
        }

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['brand'] ?? '',
            $validated['model_name'],
            $validated['reference_number'] ?? ''
        );

        $watch = Watch::create($validated);

        $this->syncSections($watch, $request->input('sections', []));
        $this->uploadImages($watch, $request);
        $this->normalizeImageOrder($watch);

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch stock created successfully.');
    }

    public function edit(Watch $watch)
    {
        $watch->load(['images', 'sections']);

        return Inertia::render('Admin/Watches/Edit', [
            'watch' => $watch,
        ]);
    }

public function update(Request $request, Watch $watch)
{
    $validated = $this->validateWatch($request, $watch->id);

    if (($validated['status'] ?? null) === 'sold') {
        if (empty($validated['date_sold']) && ! $watch->date_sold) {
            $validated['date_sold'] = now()->toDateString();
        }

        if (empty($validated['sold_price'])) {
            $validated['sold_price'] = $validated['discounted_price']
                ?: $validated['selling_price'];
        }
    }

    if (($validated['status'] ?? null) !== 'sold') {
        $validated['date_sold'] = null;
        $validated['sold_price'] = null;
        $validated['buyer_name'] = null;
    }

    $watch->update($validated);

    $this->syncSections($watch, $request->input('sections', []));
    $this->uploadImages($watch, $request);
    $this->normalizeImageOrder($watch);

    return redirect()
        ->route('admin.watches.index')
        ->with('success', 'Watch stock updated successfully.');
}

    public function destroy(Watch $watch)
    {
        foreach ($watch->images as $image) {
            Storage::disk('public')->delete([
                $image->image_path,
                $image->hd_path,
                $image->thumbnail_path,
            ]);
        }

        $watch->delete();

        return redirect()
            ->route('admin.watches.index')
            ->with('success', 'Watch stock deleted successfully.');
    }

    public function markSold(Request $request, Watch $watch)
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'sold_price' => ['required', 'numeric', 'min:0'],
            'date_sold' => ['required', 'date'],
        ]);

        $watch->update([
            'status' => 'sold',
            'buyer_name' => $validated['buyer_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            'sold_price' => $validated['sold_price'],
            'date_sold' => $validated['date_sold'],
            'is_visible' => false,
        ]);

        return back()->with('success', 'Watch marked as sold successfully.');
    }

    public function reserve(Request $request, Watch $watch)
    {
        $validated = $request->validate([
            'reserved_customer_name' => ['required', 'string', 'max:255'],
            'reserved_contact_number' => ['nullable', 'string', 'max:255'],
            'reservation_date' => ['required', 'date'],
            'reservation_deadline' => ['nullable', 'date', 'after_or_equal:reservation_date'],
            'reservation_notes' => ['nullable', 'string'],
        ]);

        $watch->update([
            'status' => 'reserved',
            'is_visible' => false,
            'reserved_customer_name' => $validated['reserved_customer_name'],
            'reserved_contact_number' => $validated['reserved_contact_number'] ?? null,
            'reservation_date' => $validated['reservation_date'],
            'reservation_deadline' => $validated['reservation_deadline'] ?? null,
            'reservation_notes' => $validated['reservation_notes'] ?? null,
        ]);

        return back()->with('success', 'Watch reserved successfully.');
    }

    public function clearReservation(Watch $watch)
    {
        $watch->update([
            'status' => 'available',
            'is_visible' => true,
            'reserved_customer_name' => null,
            'reserved_contact_number' => null,
            'reservation_date' => null,
            'reservation_deadline' => null,
            'reservation_notes' => null,
        ]);

        return back()->with('success', 'Reservation cleared successfully.');
    }

    public function deleteImage(WatchImage $image)
    {
        $watch = $image->watch;
        $wasPrimary = $image->is_primary;

        Storage::disk('public')->delete([
            $image->image_path,
            $image->hd_path,
            $image->thumbnail_path,
        ]);

        $image->delete();

        $this->normalizeImageOrder($watch);

        if ($wasPrimary && $watch->images()->exists()) {
            $firstImage = $watch->images()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            WatchImage::where('watch_id', $watch->id)
                ->update(['is_primary' => false]);

            $firstImage?->update([
                'is_primary' => true,
                'sort_order' => 1,
            ]);

            $this->normalizeImageOrder($watch);
        }

        return back()->with('success', 'Photo deleted successfully.');
    }

    public function setPrimaryImage(WatchImage $image)
    {
        $watch = $image->watch;

        $this->normalizeImageOrder($watch);

        WatchImage::where('watch_id', $image->watch_id)
            ->update(['is_primary' => false]);

        $image->update([
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $otherImages = $watch->images()
            ->where('id', '!=', $image->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($otherImages as $index => $otherImage) {
            $otherImage->update([
                'sort_order' => $index + 2,
            ]);
        }

        return back()->with('success', 'Primary photo updated successfully.');
    }

    public function moveImage(Request $request, WatchImage $image)
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:up,down,left,right'],
        ]);

        $direction = $validated['direction'];

        if ($direction === 'left') {
            $direction = 'up';
        }

        if ($direction === 'right') {
            $direction = 'down';
        }

        $watch = $image->watch;

        $this->normalizeImageOrder($watch);

        $image->refresh();

        $swapImage = $watch->images()
            ->when($direction === 'up', function ($query) use ($image) {
                $query->where('sort_order', '<', $image->sort_order)
                    ->orderByDesc('sort_order');
            })
            ->when($direction === 'down', function ($query) use ($image) {
                $query->where('sort_order', '>', $image->sort_order)
                    ->orderBy('sort_order');
            })
            ->first();

        if (! $swapImage) {
            return back()->with('success', 'Photo order unchanged.');
        }

        $currentOrder = $image->sort_order;
        $swapOrder = $swapImage->sort_order;

        $image->update([
            'sort_order' => $swapOrder,
        ]);

        $swapImage->update([
            'sort_order' => $currentOrder,
        ]);

        $this->normalizeImageOrder($watch);

        return back()->with('success', 'Photo order updated successfully.');
    }

    private function validateWatch(Request $request, ?int $watchId = null): array
    {
        return $request->validate([
            'brand' => ['nullable', 'string', 'max:255'],
            'model_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],

            'condition' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'movement' => ['nullable', 'string', 'max:255'],
            'case_size' => ['nullable', 'string', 'max:255'],
            'case_material' => ['nullable', 'string', 'max:255'],
            'dial_color' => ['nullable', 'string', 'max:255'],
            'crystal' => ['nullable', 'string', 'max:255'],
            'bracelet_or_strap' => ['nullable', 'string', 'max:255'],
            'water_resistance' => ['nullable', 'string', 'max:255'],
            'box_papers' => ['nullable', 'string', 'max:255'],
            'warranty_type' => ['nullable', 'string', 'max:255'],
            'buyer_name' => ['nullable', 'required_if:status,sold', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'capital_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:1'],
            'discounted_price' => ['nullable', 'numeric', 'min:0'],
            'sold_price' => ['nullable', 'numeric', 'min:0'],

            'status' => ['required', 'in:draft,available,reserved,sold,hidden'],

            'is_featured' => ['boolean'],
            'is_visible' => ['boolean'],
            'display_price' => ['boolean'],
            'allow_inquiry' => ['boolean'],

            'date_acquired' => ['nullable', 'date'],
            'date_sold' => ['nullable', 'date'],

            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
    }

    private function uploadImages(Watch $watch, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $currentCount = $watch->images()->count();

        foreach ($request->file('images') as $index => $file) {
            if ($currentCount + $index >= 5) {
                break;
            }

            $folder = 'watches/' . $watch->id;
            $path = $file->store($folder, 'public');

            $watch->images()->create([
                'image_path' => $path,
                'hd_path' => $path,
                'thumbnail_path' => $path,
                'is_primary' => $currentCount === 0 && $index === 0,
                'sort_order' => $currentCount + $index + 1,
            ]);
        }
    }

    private function syncSections(Watch $watch, array $sections): void
    {
        $watch->sections()->delete();

        foreach ($sections as $index => $section) {
            if (empty($section['title']) && empty($section['content'])) {
                continue;
            }

            $watch->sections()->create([
                'title' => $section['title'] ?? '',
                'content' => $section['content'] ?? '',
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function normalizeImageOrder(Watch $watch): void
    {
        $images = $watch->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($images as $index => $image) {
            $image->update([
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function generateUniqueSlug(string $brand, string $model, string $reference): string
    {
        $base = Str::slug(trim($brand . ' ' . $model . ' ' . $reference));
        $slug = $base ?: Str::random(10);
        $counter = 1;

        while (Watch::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}