<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Investor;
use App\Models\Investor2;
use App\Models\InvestorSetting;
use App\Models\InvestorSetting2;
use App\Models\Preorder;
use App\Models\PreorderListing;
use App\Models\User;
use App\Models\Watch;
use App\Services\PreorderAccounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PreorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_dashboards_keep_separate_settings_participants_and_preorder_cutoffs(): void
    {
        $this->admin();
        $order = $this->order();
        $this->entry($order, 'payment', 4000);
        $firstOwner = User::factory()->create(['role' => 'owner']);
        $secondOwner = User::factory()->create(['role' => 'owner2']);
        $this->actingAs($firstOwner)->get(route('investor.dashboard'))->assertOk();
        $this->actingAs($secondOwner)->get(route('investor2.dashboard'))->assertOk();
        InvestorSetting::first()->update(['investment_start_date' => today()->addDay(), 'brand_cut_percentage' => 40]);
        InvestorSetting2::first()->update(['investment_start_date' => today()->subDay(), 'brand_cut_percentage' => 60]);
        Investor::create(['name' => 'First group only', 'capital_amount' => 10000, 'is_active' => true]);
        Investor2::create(['name' => 'Second group only', 'capital_amount' => 20000, 'is_active' => true]);
        $first = $this->actingAs($firstOwner)->get(route('investor.dashboard'))->assertOk();
        $second = $this->actingAs($secondOwner)->get(route('investor2.dashboard'))->assertOk();
        $this->assertEquals(0, $first->viewData('page')['props']['preorderMoney']['customer_deposits']);
        $this->assertEquals(4000, $second->viewData('page')['props']['preorderMoney']['customer_deposits']);
        $this->assertEquals(10000, $first->viewData('page')['props']['summary']['total_capital']);
        $this->assertEquals(20000, $second->viewData('page')['props']['summary']['total_capital']);
        $this->assertEquals(40, $first->viewData('page')['props']['settings']['brand_cut_percentage']);
        $this->assertEquals(60, $second->viewData('page')['props']['settings']['brand_cut_percentage']);
        $this->actingAs($firstOwner)->get(route('investor2.dashboard'))->assertRedirect(route('investor.dashboard'));
        $this->actingAs($secondOwner)->get(route('investor.dashboard'))->assertRedirect(route('investor2.dashboard'));
    }

    public function test_payment_proofs_are_private_and_admin_only(): void
    {
        Storage::fake('local');
        $this->admin();
        $order = $this->order();
        $this->post(route('admin.preorders.entries.store', $order), [
            'request_key' => (string) Str::uuid(), 'type' => 'payment', 'amount' => 4000,
            'paid_at' => today()->toDateString(), 'method' => 'GCash',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $entry = $order->entries()->first();
        Storage::disk('local')->assertExists($entry->proof_path);
        $this->assertArrayNotHasKey('proof_path', $entry->toArray());
        $this->assertTrue($entry->has_proof);
        $this->get(route('admin.preorders.proof', $entry))->assertDownload();
        $this->actingAs(User::factory()->create(['role' => 'investor']));
        $this->get(route('admin.preorders.proof', $entry))->assertRedirect(route('investor.dashboard'));
    }

    public function test_both_investor_dashboards_exclude_deposits_and_pending_costs_from_profit(): void
    {
        $this->admin();
        $order = $this->order();
        foreach (['investor', 'investor2'] as $group) {
            $this->actingAs(User::factory()->create(['role' => $group === 'investor' ? 'owner' : 'owner2']));
            $response = $this->get(route($group.'.dashboard'))->assertOk();
            $baseline[$group] = $response->viewData('page')['props']['summary'];
        }
        $this->admin();
        $this->entry($order, 'payment', 4000);
        $this->entry($order, 'cost', 13000);
        foreach (['investor', 'investor2'] as $group) {
            $this->actingAs(User::factory()->create(['role' => $group === 'investor' ? 'owner' : 'owner2']));
            $this->get(route($group.'.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('summary.net_profit', fn ($value) => (float) $value === (float) $baseline[$group]['net_profit'])
                ->where('summary.current_fund_value', fn ($value) => (float) $value === (float) $baseline[$group]['current_fund_value'])
                ->where('summary.current_on_hand_money', fn ($value) => (float) $value === (float) $baseline[$group]['current_on_hand_money'] - 9000)
                ->where('preorderMoney.customer_deposits', 4000)
                ->where('preorderMoney.supplier_spend', 13000));
        }
    }

    public function test_collection_paginates_preorders_with_available_stock_without_duplicates(): void
    {
        $this->listing();
        for ($i = 0; $i < 21; $i++) {
            Watch::create(['brand' => 'Seiko', 'model_name' => 'Available '.$i, 'slug' => 'stock-'.$i,
                'status' => 'available', 'is_visible' => true, 'selling_price' => 15000]);
        }
        $first = $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')->where('watches.total', 22)->has('watches.data', 20));
        $second = $this->get('/?page=2')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('watches.total', 22)->has('watches.data', 2));
        $ids = array_merge(array_column($first->viewData('page')['props']['watches']['data'], 'id'),
            array_column($second->viewData('page')['props']['watches']['data'], 'id'));
        $this->assertCount(22, array_unique($ids));
        $this->assertContains('preorder-1', $ids);
    }

    private function listing(): PreorderListing
    {
        $source = Watch::create(['model_name' => 'Presage', 'brand' => 'Seiko', 'slug' => (string) Str::uuid(),
            'status' => 'sold', 'selling_price' => 19000, 'sold_price' => 18000, 'capital_price' => 12000,
            'buyer_name' => 'Original Buyer', 'serial_number' => 'ORIGINAL', 'date_sold' => '2026-01-01']);

        return PreorderListing::create(['source_watch_id' => $source->id, 'price' => 20000,
            'watch_snapshot' => ['brand' => 'Seiko', 'model_name' => 'Presage', 'images' => [], 'sections' => []]]);
    }

    private function order(): Preorder
    {
        return Preorder::create(['preorder_listing_id' => $this->listing()->id, 'customer_name' => 'New Buyer',
            'contact' => 'Messenger', 'total_price' => 20000, 'deposit_percentage' => 20]);
    }

    private function entry(Preorder $order, string $type, float $amount, ?string $key = null)
    {
        return $this->post(route('admin.preorders.entries.store', $order), [
            'request_key' => $key ?? (string) Str::uuid(), 'type' => $type, 'amount' => $amount,
            'paid_at' => today()->toDateString(), 'method' => 'Bank transfer',
        ]);
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_partial_payments_confirm_at_twenty_percent_and_cannot_overpay(): void
    {
        $this->admin();
        $order = $this->order();
        $this->entry($order, 'payment', 1000)->assertSessionHasNoErrors();
        $this->assertSame('awaiting_deposit', $order->fresh()->status);
        $this->entry($order, 'payment', 3000)->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertEquals(16000, $order->fresh()->balance);
        $this->entry($order, 'payment', 16000.01)->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('preorder_entries', 2);
    }

    public function test_duplicate_payment_request_is_idempotent(): void
    {
        $this->admin();
        $order = $this->order();
        $key = (string) Str::uuid();
        $this->entry($order, 'payment', 4000, $key)->assertSessionHasNoErrors();
        $this->entry($order, 'payment', 4000, $key)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('preorder_entries', 1);
    }

    public function test_fulfillment_records_one_sale_with_all_costs_and_preserves_cash(): void
    {
        $this->admin();
        $order = $this->order();
        $this->entry($order, 'payment', 4000);
        $this->patch(route('admin.preorders.update', $order), ['status' => 'ordered'])->assertSessionHasNoErrors();
        $this->entry($order, 'cost', 13000);
        $this->entry($order, 'cost', 500);
        $this->assertEquals(-9500, PreorderAccounting::summary()['cash_adjustment']);
        $this->patch(route('admin.preorders.update', $order), ['status' => 'arrived']);
        $this->post(route('admin.preorders.fulfill', $order), ['serial_number' => 'NEW'])->assertSessionHasErrors('serial_number');
        $this->entry($order, 'payment', 16000);
        $before = PreorderAccounting::summary()['cash_adjustment'];
        $this->post(route('admin.preorders.fulfill', $order), ['serial_number' => 'NEW'])->assertSessionHasNoErrors();
        $sale = $order->fresh()->fulfilledWatch;
        $this->assertEquals(20000, $sale->sold_price);
        $this->assertEquals(13500, $sale->capital_price);
        $this->assertEquals($before, (float) $sale->sold_price - (float) $sale->capital_price);
        $this->assertEquals(0, PreorderAccounting::summary()['cash_adjustment']);
        $this->assertSame('New Buyer', $sale->buyer_name);
        $this->assertSame('Original Buyer', $order->listing->sourceWatch->buyer_name);
        $this->assertSame(today()->toDateString(), $sale->date_sold->toDateString());
        $this->post(route('admin.preorders.fulfill', $order), ['serial_number' => 'NEW'])->assertSessionHasErrors();
        $this->assertDatabaseCount('watches', 2);
        $this->entry($order, 'payment', 1)->assertSessionHasErrors('amount');
        $this->delete(route('admin.watches.destroy', $sale))->assertSessionHasErrors('preorder');
    }

    public function test_cancellation_keeps_deposit_liability_and_records_cost_only_once(): void
    {
        $this->admin();
        $order = $this->order();
        $this->entry($order, 'payment', 4000);
        $this->entry($order, 'cost', 1000);
        $this->entry($order, 'refund', 100)->assertSessionHasErrors('amount');
        $this->patch(route('admin.preorders.update', $order), ['status' => 'cancelled', 'cancellation_reason' => 'Customer cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(4000, PreorderAccounting::summary()['customer_deposits']);
        $this->assertEquals(1000, Expense::sum('amount'));
        $this->entry($order, 'refund', 4000.01)->assertSessionHasErrors('amount');
        $this->entry($order, 'refund', 1500)->assertSessionHasNoErrors();
        $this->assertEquals(2500, $order->fresh()->refundable);
        $this->entry($order, 'refund', 2500)->assertSessionHasNoErrors();
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertEquals(0, PreorderAccounting::summary()['cash_adjustment']);
        $this->delete(route('admin.expenses.destroy', Expense::first()))->assertSessionHasErrors('preorder');
        $this->patch(route('admin.preorders.update', $order), ['status' => 'cancelled', 'cancellation_reason' => 'Again'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('expenses', 1);
        $this->entry($order, 'cost', 500)->assertSessionHasErrors('amount');
    }

    public function test_order_cannot_skip_confirmation_or_arrival(): void
    {
        $this->admin();
        $order = $this->order();
        $this->patch(route('admin.preorders.update', $order), ['status' => 'ordered'])->assertSessionHasErrors('status');
        $this->post(route('admin.preorders.fulfill', $order), ['serial_number' => 'NEW'])->assertSessionHasErrors();
    }

    public function test_public_listing_hides_private_customer_data_and_hidden_listings(): void
    {
        $listing = $this->listing();
        $this->get(route('public.preorders.show', $listing))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/PreorderShow')->where('watch.status', 'preorder')->missing('watch.buyer_name')->missing('watch.capital_price'));
        $listing->update(['is_visible' => false]);
        $this->get(route('public.preorders.show', $listing))->assertNotFound();
    }

    public function test_admin_pages_render_and_non_admin_cannot_manage_orders(): void
    {
        $order = $this->order();
        $this->get(route('admin.preorders.index'))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'investor']));
        $this->post(route('admin.preorders.entries.store', $order), [])->assertRedirect(route('investor.dashboard'));
        $this->admin();
        $this->get(route('admin.preorders.index'))->assertOk();
        $this->get(route('admin.preorders.show', $order))->assertOk();
    }

    public function test_listing_copies_sold_watch_and_locks_order_price(): void
    {
        $this->admin();
        Storage::fake('public');
        $source = $this->listing()->sourceWatch;
        $newSource = $source->replicate(['slug']);
        $newSource->slug = (string) Str::uuid();
        $newSource->save();
        Storage::disk('public')->put('original.jpg', 'photo');
        $newSource->images()->create(['image_path' => 'original.jpg', 'is_primary' => true]);
        $this->post(route('admin.preorder-listings.store'), ['source_watch_id' => $newSource->id, 'price' => 20000])->assertSessionHasNoErrors();
        $listing = PreorderListing::where('source_watch_id', $newSource->id)->first();
        $this->assertNotEquals('original.jpg', $listing->watch_snapshot['images'][0]['path']);
        $this->assertArrayNotHasKey('buyer_name', $listing->watch_snapshot);
        $this->post(route('admin.preorders.store'), ['preorder_listing_id' => $listing->id, 'customer_name' => 'Customer',
            'contact' => 'Messenger', 'total_price' => 20000, 'deposit_percentage' => 20])->assertSessionHasNoErrors();
        $this->patch(route('admin.preorder-listings.update', $listing), ['price' => 25000, 'is_visible' => false])->assertSessionHasNoErrors();
        $this->assertEquals(20000, Preorder::first()->total_price);
        $this->delete(route('admin.watches.destroy', $newSource))->assertSessionHasErrors('preorder');
        Storage::disk('public')->assertExists('original.jpg');
    }
}
