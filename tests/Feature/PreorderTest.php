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

    public function test_follow_up_queue_counts_all_orders_and_filters_before_pagination(): void
    {
        $this->admin();
        for ($i = 0; $i < 16; $i++) {
            $this->order();
        }
        $supplier = $this->order();
        $this->entry($supplier, 'payment', 4000);
        $old = $this->order();
        $old->update(['status' => 'ordered', 'created_at' => now()->subDays(15)]);
        $overdue = $this->order();
        $overdue->update(['status' => 'ordered', 'estimated_arrival' => today()->subDay()]);
        $recent = $this->order();
        $recent->update(['status' => 'ordered', 'estimated_arrival' => today()->addDay()]);
        $unpaid = $this->order();
        $this->entry($unpaid, 'payment', 19000);
        $this->entry($unpaid, 'cost', 2000);
        $unpaid->update(['status' => 'arrived']);
        $paid = $this->order();
        $this->entry($paid, 'payment', 20000);
        $paid->update(['status' => 'arrived']);
        $this->order()->update(['status' => 'cancelled']);
        $this->order()->update(['status' => 'fulfilled']);

        $response = $this->get(route('admin.preorders.index'))->assertOk();
        $counts = collect($response->viewData('page')['props']['followUps'])->pluck('count', 'key')->all();
        $this->assertSame(['deposit' => 16, 'supplier' => 1, 'sourcing' => 2, 'balance' => 1, 'handover' => 1], $counts);
        foreach (['supplier' => [$supplier->id], 'sourcing' => [$overdue->id, $old->id], 'balance' => [$unpaid->id], 'handover' => [$paid->id]] as $queue => $ids) {
            $filtered = $this->get(route('admin.preorders.index', ['queue' => $queue]))->assertOk();
            $orders = $filtered->viewData('page')['props']['orders'];
            $this->assertSame(count($ids), $orders['total']);
            $this->assertEqualsCanonicalizing($ids, array_column($orders['data'], 'id'));
        }
        $this->get(route('admin.preorders.index', ['queue' => 'deposit']))->assertInertia(fn (Assert $page) => $page
            ->where('orders.total', 16)->has('orders.data', 15)->where('filters.queue', 'deposit'));
        $this->get(route('admin.preorders.index', ['queue' => 'balance', 'search' => 'no matching customer']))
            ->assertInertia(fn (Assert $page) => $page->where('orders.total', 0)->where('followUps.3.count', 1));
        $this->get(route('admin.preorders.index', ['queue' => 'unknown']))->assertSessionHasErrors('queue');
    }

    public function test_customer_tracking_is_private_and_only_exposes_customer_facing_fields(): void
    {
        $this->admin();
        $order = $this->order();
        $this->entry($order, 'payment', 4000);
        $this->entry($order, 'cost', 3000);
        $order->update(['notes' => 'Private supplier negotiation', 'tracking_token' => Str::random(64)]);
        $this->app['auth']->forgetGuards();
        $response = $this->get(route('public.preorders.track', $order->tracking_token))->assertOk();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn (Assert $page) => $page->component('Public/PreorderTracking')
                ->where('order.status', 'confirmed')->where('order.paid', 4000)
                ->where('order.balance', 16000)->has('order.transactions', 1)
                ->where('order.transactions.0.type', 'payment')
                ->missing('order.cost')->missing('order.notes')->missing('order.contact')
                ->where('order.customer_name', $order->customer_name)->missing('order.tracking_token')
                ->missing('order.transactions.0.reference')->missing('order.transactions.0.proof_path'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('public.preorders.track', Str::random(64)))->assertNotFound();
        $this->get('/track/preorders/'.$order->id)->assertNotFound();
    }

    public function test_admin_can_update_and_clear_eta_on_the_existing_tracking_link(): void
    {
        $order = $this->order();
        $order->update(['tracking_token' => Str::random(64)]);
        $trackingUrl = route('public.preorders.track', $order->tracking_token);
        $editUrl = route('admin.preorders.eta.update', $order);

        $this->patch($editUrl, ['estimated_arrival' => '2027-01-15'])->assertRedirect('/login');
        $this->admin();
        $this->patch($editUrl, ['estimated_arrival' => '2027-02-30'])->assertSessionHasErrors('estimated_arrival');
        $this->patch($editUrl, ['estimated_arrival' => '2027-01-15', 'status' => 'fulfilled'])->assertSessionHasNoErrors();
        $this->assertSame('awaiting_deposit', $order->fresh()->status);
        $this->get($trackingUrl)->assertInertia(fn (Assert $page) => $page
            ->where('order.estimated_arrival', '2027-01-15'));

        $this->patch($editUrl, ['estimated_arrival' => null])->assertSessionHasNoErrors();
        $this->get($trackingUrl)->assertInertia(fn (Assert $page) => $page
            ->where('order.estimated_arrival', null));

        $order->update(['status' => 'cancelled']);
        $this->patch($editUrl, ['estimated_arrival' => '2027-01-16'])->assertSessionHasErrors('estimated_arrival');
    }

    public function test_only_admin_can_create_and_reset_tracking_links_and_old_links_are_revoked(): void
    {
        $order = $this->order();
        $this->post(route('admin.preorders.tracking', $order))->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'investor']));
        $this->post(route('admin.preorders.tracking', $order))->assertRedirect(route('investor.dashboard'));
        $this->assertNull($order->fresh()->tracking_token);
        $this->admin();
        $this->post(route('admin.preorders.tracking', $order))->assertSessionHasNoErrors();
        $old = $order->fresh()->tracking_token;
        $this->assertSame(64, strlen($old));
        $this->assertArrayNotHasKey('tracking_token', $order->fresh()->toArray());
        $this->get(route('admin.preorders.show', $order))->assertInertia(fn (Assert $page) => $page
            ->where('trackingUrl', route('public.preorders.track', $old)));
        $this->post(route('admin.preorders.tracking', $order))->assertSessionHasNoErrors();
        $this->assertNotSame($old, $order->fresh()->tracking_token);
        $this->get(route('public.preorders.track', $old))->assertNotFound();
        $this->get(route('public.preorders.track', $order->fresh()->tracking_token))->assertOk();
    }

    public function test_preorder_recommendations_only_include_other_published_listings_and_are_limited(): void
    {
        $current = $this->listing();
        $allowed = [];
        for ($i = 0; $i < 10; $i++) {
            $allowed[] = 'preorder-'.$this->listing()->id;
        }
        $hidden = $this->listing();
        $hidden->update(['is_visible' => false]);
        $response = $this->get(route('public.preorders.show', $current))->assertOk();
        $recommendations = collect($response->viewData('page')['props']['relatedPreorders']);
        $this->assertCount(8, $recommendations);
        $ids = $recommendations->pluck('id')->all();
        $this->assertNotContains('preorder-'.$current->id, $ids);
        $this->assertNotContains('preorder-'.$hidden->id, $ids);
        $this->assertCount(8, array_unique($ids));
        $this->assertEmpty(array_diff($ids, $allowed));
        foreach ($recommendations as $item) {
            $this->assertSame('preorder', $item['status']);
            $this->assertArrayHasKey('url', $item);
        }
    }

    public function test_preorder_page_handles_no_other_published_listings(): void
    {
        $current = $this->listing();
        $this->get(route('public.preorders.show', $current))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('relatedPreorders', 0));
    }

    public function test_preorder_photos_use_the_current_host_and_primary_source_photo_first(): void
    {
        Storage::fake('public');
        $this->admin();
        $watch = Watch::create(['model_name' => 'Photo test', 'brand' => 'Seiko', 'slug' => (string) Str::uuid(), 'status' => 'sold']);
        Storage::disk('public')->put('first.jpg', 'secondary photo');
        Storage::disk('public')->put('primary.jpg', 'primary photo');
        $watch->images()->create(['image_path' => 'first.jpg', 'sort_order' => 1, 'is_primary' => false]);
        $watch->images()->create(['image_path' => 'primary.jpg', 'sort_order' => 2, 'is_primary' => true]);
        $this->post(route('admin.preorder-listings.store'), ['source_watch_id' => $watch->id, 'price' => 20000])->assertSessionHasNoErrors();
        $listing = PreorderListing::where('source_watch_id', $watch->id)->firstOrFail();
        $snapshot = $listing->watch_snapshot;
        $this->assertSame('primary photo', Storage::disk('public')->get($snapshot['images'][0]['path']));
        $snapshot['images'][0]['image_url'] = 'http://old-preview-host.test/storage/old.jpg';
        $snapshot['primary_image_url'] = 'http://old-preview-host.test/storage/old.jpg';
        $listing->update(['watch_snapshot' => $snapshot]);
        $this->get('https://current-shop.example.test/preorders/'.$listing->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('watch.images.0.image_url', 'https://current-shop.example.test/storage/'.$snapshot['images'][0]['path'])
            ->where('watch.primary_image_url', 'https://current-shop.example.test/storage/'.$snapshot['images'][0]['path']));
    }

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
        $first = $this->get('/?type=all')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')->where('watches.total', 22)->has('watches.data', 20));
        $second = $this->get('/?type=all&page=2')->assertOk()->assertInertia(fn (Assert $page) => $page
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

    public function test_manual_listing_can_be_published_and_fulfilled_without_a_sold_source(): void
    {
        $this->admin();
        Storage::fake('public');
        $sold = $this->listing()->sourceWatch->replicate(['slug']);
        $sold->slug = (string) Str::uuid();
        $sold->save();

        $this->post(route('admin.preorder-listings.store'), ['price' => 20000])
            ->assertSessionHasErrors(['brand', 'model_name']);
        $photo = UploadedFile::fake()->createWithContent('reference.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA5sAAAAASUVORK5CYII='
        ));
        $details = ['brand' => 'Seiko', 'model_name' => 'New model', 'price' => 20000];
        $this->post(route('admin.preorder-listings.store'), $details + ['photos' => [$photo]])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.preorder-listings.store'), array_replace($details, ['model_name' => 'Another model']))
            ->assertSessionHasNoErrors();
        $listing = PreorderListing::whereNull('source_watch_id')->oldest('id')->firstOrFail();
        $path = $listing->watch_snapshot['images'][0]['path'];
        Storage::disk('public')->assertExists($path);
        $this->get(route('public.preorders.show', $listing))->assertInertia(fn (Assert $page) => $page
            ->where('watch.model_name', 'New model')->has('watch.images', 1));
        $this->get(route('admin.preorders.index'))->assertInertia(fn (Assert $page) => $page
            ->where('soldWatches.0.id', $sold->id));

        $this->post(route('admin.preorders.store'), [
            'preorder_listing_id' => $listing->id, 'customer_name' => 'New Buyer',
            'contact' => 'Messenger', 'total_price' => 20000, 'deposit_percentage' => 20,
        ])->assertSessionHasNoErrors();
        $order = Preorder::where('preorder_listing_id', $listing->id)->firstOrFail();
        $this->entry($order, 'payment', 20000)->assertSessionHasNoErrors();
        $this->patch(route('admin.preorders.update', $order), ['status' => 'ordered'])->assertSessionHasNoErrors();
        $this->entry($order, 'cost', 13000)->assertSessionHasNoErrors();
        $this->patch(route('admin.preorders.update', $order), ['status' => 'arrived'])->assertSessionHasNoErrors();
        $this->post(route('admin.preorders.fulfill', $order), ['serial_number' => 'MANUAL-NEW'])->assertSessionHasNoErrors();
        $sale = $order->fresh()->fulfilledWatch;
        $this->assertSame('New model', $sale->model_name);
        $this->assertEquals(20000, $sale->sold_price);
        Storage::disk('public')->assertExists($sale->images()->firstOrFail()->image_path);
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
