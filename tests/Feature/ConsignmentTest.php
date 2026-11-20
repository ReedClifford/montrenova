<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_consignment_publishes_normally_and_recognizes_only_the_margin_when_sold(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owners = [
            'investor.dashboard' => User::factory()->create(['role' => 'owner']),
            'investor2.dashboard' => User::factory()->create(['role' => 'owner2']),
        ];
        $baseline = [];
        foreach ($owners as $dashboard => $owner) {
            $baseline[$dashboard] = $this->actingAs($owner)->get(route($dashboard))
                ->assertOk()->viewData('page')['props']['summary'];
        }

        $photo = UploadedFile::fake()->createWithContent('watch.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA5sAAAAASUVORK5CYII='
        ));
        $this->actingAs($admin)->post(route('admin.watches.store'), [
            'brand' => 'Seiko', 'model_name' => 'Consigned Presage', 'condition' => 'Pre-owned',
            'warranty_type' => 'Montre Card', 'save_mode' => 'publish',
            'is_consigned' => true, 'capital_price' => 15000, 'selling_price' => 20000,
            'sections' => [['title' => 'Details', 'content' => 'Watch details']],
            'images' => [$photo],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $watch = Watch::sole();
        $this->assertTrue($watch->is_consigned);
        $this->assertSame(0.0, $watch->committedCapital());
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('watches.data.0.id', $watch->id)->where('watches.data.0.price', 20000)
            ->where('watches.data.0.status', 'available')->missing('watches.data.0.capital_price'));
        $this->get(route('public.watches.show', $watch))->assertOk();
        $this->get(route('admin.watches.index'))->assertInertia(fn (Assert $page) => $page
            ->where('summary.inventory_capital', 0)->where('summary.expected_profit', 5000));

        foreach ($owners as $dashboard => $owner) {
            $this->actingAs($owner)->get(route($dashboard))->assertInertia(fn (Assert $page) => $page
                ->where('summary.inventory_capital', 0)
                ->where('summary.current_on_hand_money', fn ($value) => (float) $value === (float) $baseline[$dashboard]['current_on_hand_money'])
                ->where('summary.current_fund_value', fn ($value) => (float) $value === (float) $baseline[$dashboard]['current_fund_value']));
        }

        $this->actingAs($admin)->patch(route('admin.watches.mark-sold', $watch), [
            'buyer_name' => 'Customer', 'serial_number' => 'CONSIGN-001',
            'sold_price' => 19000, 'date_sold' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertEquals(15000, $watch->fresh()->committedCapital());
        $this->get(route('admin.sales.index'))->assertInertia(fn (Assert $page) => $page
            ->where('recentSales.0.profit', 4000));
        $this->get(route('public.sold-watches.index'))->assertInertia(fn (Assert $page) => $page
            ->where('soldWatches.data.0.id', $watch->id));
        $this->get(route('admin.sales.receipt', $watch))->assertOk()->assertSee('₱19,000.00')
            ->assertDontSee('₱15,000.00');

        foreach ($owners as $dashboard => $owner) {
            $this->actingAs($owner)->get(route($dashboard))->assertInertia(fn (Assert $page) => $page
                ->where('summary.gross_profit', 4000)->where('summary.net_profit', 4000)
                ->where('summary.current_fund_value', fn ($value) => (float) $value === (float) $baseline[$dashboard]['current_fund_value'] + 4000));
        }
    }
}
