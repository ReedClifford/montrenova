<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WatchInventoryActionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_counts_and_filtered_pages_cover_all_matching_inventory(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->travelTo(now('Asia/Manila')->startOfDay()->addHours(10));

        for ($i = 0; $i < 11; $i++) {
            $watch = $this->stock(['created_at' => now()->subDays(31)]);
            $watch->images()->create(['image_path' => 'watch.jpg', 'is_primary' => true]);
        }
        $olderCasio = $this->stock([
            'brand' => 'Casio',
            'created_at' => now()->subDays(31),
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->stock();
        }

        $this->get(route('admin.watches.index', ['brand' => 'Tissot']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('watches.data', 10)
                ->where('watches.total', 21)
                ->where('actionCounts.needs_push', 11)
                ->where('actionCounts.no_photo', 10)
                ->where('actionCounts.ready_to_post', 11));

        $this->get(route('admin.watches.index', ['brand' => 'Tissot', 'action' => 'needs_push', 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.action', 'needs_push')
                ->where('watches.total', 11)
                ->where('watches.current_page', 2)
                ->has('watches.data', 1)
                ->where('watches.data.0.brand', 'Tissot')
                ->where('watches.prev_page_url', fn ($url) => str_contains($url, 'action=needs_push'))
                ->where('actionCounts.no_photo', 10));

        $this->get(route('admin.watches.index', ['brand' => 'Casio', 'action' => 'no_photo']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('watches.total', 1)
                ->where('watches.data.0.id', $olderCasio->id));
    }

    public function test_overdue_and_low_margin_match_their_action_thresholds(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $today = now('Asia/Manila');
        $overdue = $this->stock([
            'status' => 'reserved',
            'reservation_deadline' => $today->copy()->subDay()->toDateString(),
            'discounted_price' => 95,
        ]);
        $this->stock(['status' => 'reserved', 'reservation_deadline' => $today->toDateString()]);
        $this->stock(['status' => 'sold', 'discounted_price' => 95]);

        foreach (['reservation_overdue', 'low_margin'] as $action) {
            $this->get(route('admin.watches.index', ['action' => $action]))
                ->assertInertia(fn (Assert $page) => $page
                    ->where('watches.total', 1)
                    ->where('watches.data.0.id', $overdue->id));
        }
    }

    private function stock(array $attributes = []): Watch
    {
        $watch = new Watch([
            'brand' => 'Tissot',
            'model_name' => 'PRX',
            'slug' => (string) Str::uuid(),
            'status' => 'available',
            'is_visible' => true,
            'selling_price' => 100,
            'capital_price' => 90,
        ]);
        $watch->forceFill($attributes)->save();

        return $watch;
    }
}
