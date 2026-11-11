<?php

namespace Tests\Feature;

use App\Models\PreorderListing;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollectionBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_defaults_to_all_and_keeps_available_first(): void
    {
        $stock = $this->stock('On hand');
        $listing = $this->listing('New preorder');
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionType', 'all')->where('watches.total', 2)
            ->where('watches.data.0.id', $stock->id)->where('watches.data.1.id', 'preorder-'.$listing->id));
        $this->get('/?type=all')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionType', 'all')->where('watches.total', 2)
            ->where('watches.data.0.id', $stock->id)->where('watches.data.1.id', 'preorder-'.$listing->id));
    }

    public function test_default_collection_keeps_available_stock_ahead_of_preorders_across_pages(): void
    {
        $listing = $this->listing('Preorder');
        for ($i = 0; $i < 21; $i++) {
            $stock = $this->stock('Available '.$i);
            $stock->update(['created_at' => now()->subMonth()]);
        }
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('collectionType', 'all')->where('watches.total', 22)
            ->has('watches.data', 20)->where('watches.data', fn ($items) => collect($items)->every(fn ($item) => $item['status'] === 'available')));
        $this->get('/?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('watches.data', 2)->where('watches.data.0.status', 'available')
            ->where('watches.data.1.id', 'preorder-'.$listing->id));
    }

    private function stock(string $name, string $status = 'available'): Watch
    {
        return Watch::create(['brand' => 'Seiko', 'model_name' => $name, 'slug' => (string) Str::uuid(),
            'status' => $status, 'is_visible' => true, 'selling_price' => 20000]);
    }

    private function listing(string $name, bool $visible = true): PreorderListing
    {
        return PreorderListing::create(['source_watch_id' => $this->stock('Source for listing', 'sold')->id,
            'price' => 25000, 'is_visible' => $visible,
            'watch_snapshot' => ['brand' => 'Seiko', 'model_name' => $name, 'reference_number' => 'SRPB43', 'images' => []]]);
    }

    public function test_search_finds_stock_and_preorders_across_pages_without_exposing_hidden_records(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->stock('Other watch '.$i);
        }
        $this->stock('Presage available');
        $this->listing('Presage preorder');
        $this->listing('Presage hidden', false);
        $this->stock('Presage reserved', 'reserved');
        $this->get('/?type=all&search=Presage')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')->where('collectionSearch', 'Presage')
            ->where('watches.total', 2)->has('watches.data', 2));
    }

    public function test_availability_filters_apply_before_pagination(): void
    {
        $this->stock('Available');
        $listing = $this->listing('Preorder');
        $this->get('/?type=preorder')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionType', 'preorder')->where('watches.total', 1)
            ->where('watches.data.0.id', 'preorder-'.$listing->id));
        $this->get('/?type=available')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionType', 'available')->where('watches.total', 1)
            ->where('watches.data.0.status', 'available'));
    }

    public function test_reference_search_matches_normalized_references_across_public_statuses(): void
    {
        $this->stock('Available Tissot')->update(['reference_number' => 'T137.010.11.056.00']);
        $this->stock('Sold Tissot', 'sold')->update(['reference_number' => 't137-010-11-056-00']);
        $this->stock('Private sale', 'sold')->update(['is_visible' => false, 'reference_number' => 'T1370101105600']);
        $this->listing('Preorder Tissot')->update([
            'watch_snapshot' => ['brand' => 'Tissot', 'model_name' => 'PRX', 'reference_number' => 'T1370101105600'],
        ]);

        foreach (['T137.010.11.056.00', 't1370101105600'] as $search) {
            $this->get('/?search='.$search)->assertInertia(fn (Assert $page) => $page
                ->where('watches.total', 3)
                ->where('watches.data.0.status', 'available')
                ->where('watches.data.1.status', 'preorder')
                ->where('watches.data.2.status', 'sold'));
        }
        $this->get('/?type=sold&search=t1370101105600')->assertInertia(fn (Assert $page) => $page
            ->where('watches.total', 1)->where('watches.data.0.status', 'sold'));
    }

    public function test_sold_pages_and_comparison_only_show_public_watch_details(): void
    {
        $sold = $this->stock('Sold unit', 'sold');
        $sold->update(['sold_price' => 18000, 'capital_price' => 12000, 'buyer_name' => 'Private Buyer']);
        $available = $this->stock('Available unit');
        $hidden = $this->stock('Private sold unit', 'sold');
        $hidden->update(['is_visible' => false]);
        $listing = $this->listing('Public preorder');

        $this->get(route('public.watches.show', $sold))->assertInertia(fn (Assert $page) => $page
            ->component('Public/WatchShow')->where('watch.status', 'sold')->where('watch.price', 18000)
            ->where('relatedWatches.0.id', $available->id)->missing('watch.buyer_name')->missing('watch.capital_price'));
        $this->get(route('public.watches.show', $hidden))->assertNotFound();
        $this->get(route('public.watches.compare', ['items' => [(string) $sold->id, 'preorder-'.$listing->id, (string) $hidden->id]]))
            ->assertInertia(fn (Assert $page) => $page->component('Public/WatchCompare')
                ->has('watches', 2)->where('watches.0.status', 'sold')->where('watches.1.status', 'preorder')
                ->missing('watches.0.buyer_name')->missing('watches.0.capital_price'));
    }

    public function test_reference_search_and_no_results_return_clear_filter_state(): void
    {
        $this->listing('Cocktail Time');
        $this->get('/?type=all&search=SRPB43')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('watches.total', 1));
        $this->get('/?search=unknown-model')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionSearch', 'unknown-model')->where('watches.total', 0)->has('watches.data', 0));
    }
}
