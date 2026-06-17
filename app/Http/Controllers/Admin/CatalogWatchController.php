<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogWatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CatalogWatchController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $catalogWatches = CatalogWatch::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('brand', 'like', "%{$search}%")
                        ->orWhere('model_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Catalog/Index', [
            'catalogWatches' => $catalogWatches,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCatalogWatch($request);

        $validated['brand'] = $validated['brand'] ?: 'Seiko';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('photo')) {
            $validated['image_path'] = $request
                ->file('photo')
                ->store('catalog-watches', 'public');
        }

        CatalogWatch::create($validated);

        return back()->with('success', 'Catalog watch added successfully.');
    }

    public function update(Request $request, CatalogWatch $catalog)
    {
        $validated = $this->validateCatalogWatch($request, $catalog);

        $validated['brand'] = $validated['brand'] ?: 'Seiko';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('photo')) {
            if ($catalog->image_path) {
                Storage::disk('public')->delete($catalog->image_path);
            }

            $validated['image_path'] = $request
                ->file('photo')
                ->store('catalog-watches', 'public');
        }

        $catalog->update($validated);

        return back()->with('success', 'Catalog watch updated successfully.');
    }

    public function destroy(CatalogWatch $catalog)
    {
        if ($catalog->image_path) {
            Storage::disk('public')->delete($catalog->image_path);
        }

        $catalog->delete();

        return back()->with('success', 'Catalog watch deleted successfully.');
    }

    private function validateCatalogWatch(Request $request, ?CatalogWatch $catalogWatch = null): array
    {
        return $request->validate([
            'brand' => ['nullable', 'string', 'max:255'],
            'model_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'photo' => [
                $catalogWatch ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}