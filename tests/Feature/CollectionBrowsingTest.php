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

    private function stock(string $name, string $status = 'available'): Watch
    {
        return Watch::create(['brand' => 'Seiko', 'model_name' => $name, 'slug' => (string) Str::uuid(),
            'status' => $status, 'is_visible' => true, 'selling_price' => 20000]);
    }

    private function listing(string $name, bool $visible = true): PreorderListing
    {
        return PreorderListing::create(['source_watch_id' => $this->stock($name, 'sold')->id,
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
        $this->get('/?search=Presage')->assertOk()->assertInertia(fn (Assert $page) => $page
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

    public function test_reference_search_and_no_results_return_clear_filter_state(): void
    {
        $this->listing('Cocktail Time');
        $this->get('/?search=SRPB43')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('watches.total', 1));
        $this->get('/?search=unknown-model')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('collectionSearch', 'unknown-model')->where('watches.total', 0)->has('watches.data', 0));
    }
}
