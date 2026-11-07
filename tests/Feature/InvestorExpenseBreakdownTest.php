<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\InvestorSetting;
use App\Models\InvestorSetting2;
use App\Models\Preorder;
use App\Models\PreorderListing;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvestorExpenseBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $status, float $cost, string $createdAt = '2026-11-06'): Preorder
    {
        $watch = Watch::create(['brand' => 'Seiko', 'model_name' => 'Presage', 'slug' => (string) Str::uuid(), 'status' => 'sold']);
        $listing = PreorderListing::create(['source_watch_id' => $watch->id, 'price' => 20000,
            'watch_snapshot' => ['brand' => 'Seiko', 'model_name' => 'Presage', 'buyer_name' => 'Private customer']]);
        $order = Preorder::create(['preorder_listing_id' => $listing->id, 'customer_name' => 'Private customer',
            'total_price' => 20000, 'status' => $status, 'created_at' => $createdAt]);
        $order->entries()->create(['type' => 'cost', 'amount' => $cost, 'paid_at' => '2026-11-06',
            'method' => 'Bank transfer', 'notes' => 'Acquisition cost', 'reference' => 'PRIVATE-REFERENCE',
            'recorded_by' => User::factory()->create()->id, 'request_key' => (string) Str::uuid()]);

        return $order;
    }

    private function expense(string $title, float $amount, string $createdAt, ?int $preorderId = null): Expense
    {
        $expense = new Expense;
        $expense->forceFill(['title' => $title, 'category' => 'Operations', 'amount' => $amount,
            'notes' => 'Full expense description', 'spent_at' => $createdAt,
            'created_at' => $createdAt, 'preorder_id' => $preorderId])->save();

        return $expense;
    }

    public function test_both_dashboards_itemize_all_expenses_and_supplier_costs_without_double_counting(): void
    {
        $this->expense('Delivery', 100, '2026-11-06');
        $this->order('confirmed', 200);
        $this->order('fulfilled', 300);
        $cancelled = $this->order('cancelled', 400);
        $this->expense('Cancelled preorder', 400, '2026-11-06', $cancelled->id);

        foreach (['investor', 'investor2'] as $group) {
            $response = $this->actingAs(User::factory()->create(['role' => $group]))
                ->get(route($group.'.dashboard'))->assertOk();
            $props = $response->viewData('page')['props'];
            $records = collect($props['expenseBreakdown']);
            $this->assertCount(4, $records);
            $this->assertEquals(1000, $records->sum('amount'));
            $this->assertEquals(500, $props['summary']['total_expenses']);
            $this->assertEquals(200, $records->where('treatment', 'pending_capital')->sum('amount'));
            $this->assertEquals(300, $records->where('treatment', 'fulfilled_capital')->sum('amount'));
            $cancellation = $records->firstWhere('title', 'Cancelled preorder');
            $this->assertCount(1, $cancellation['details']);
            $this->assertEquals(400, $cancellation['details'][0]['amount']);
            $this->assertSame('Acquisition cost', $cancellation['details'][0]['notes']);
            $this->assertStringNotContainsString('Private customer', json_encode($records));
            $this->assertStringNotContainsString('PRIVATE-REFERENCE', json_encode($records));
        }
    }

    public function test_all_operating_expenses_are_returned_without_a_row_limit_or_selected_year_filter(): void
    {
        for ($i = 0; $i < 55; $i++) {
            $this->expense('Expense '.$i, 10, '2026-11-06');
        }
        $this->expense('Older in-scope expense', 20, '2026-09-01');
        $this->expense('Before investment scope', 999, '2026-01-01');
        foreach (['investor', 'investor2'] as $group) {
            $response = $this->actingAs(User::factory()->create(['role' => $group]))
                ->get(route($group.'.dashboard', ['year' => 2027]))->assertOk();
            $records = collect($response->viewData('page')['props']['expenseBreakdown']);
            $this->assertCount(56, $records);
            $this->assertEquals(570, $records->sum('amount'));
            $this->assertNotNull($records->firstWhere('title', 'Older in-scope expense'));
            $this->assertNull($records->firstWhere('title', 'Before investment scope'));
            $this->assertSame('Full expense description', $records->first()['notes']);
        }
    }

    public function test_each_dashboard_keeps_its_own_expense_and_order_cutoff(): void
    {
        InvestorSetting::create(['setting_key' => 'main', 'investment_start_date' => '2026-11-01']);
        InvestorSetting2::create(['setting_key' => 'main', 'investment_start_date' => '2026-11-05']);
        $this->expense('First scope only', 100, '2026-11-03');
        $this->expense('Both scopes', 200, '2026-11-06');
        $this->order('confirmed', 300, '2026-11-03');
        $first = $this->actingAs(User::factory()->create(['role' => 'investor']))
            ->get(route('investor.dashboard'))->assertOk()->viewData('page')['props']['expenseBreakdown'];
        $second = $this->actingAs(User::factory()->create(['role' => 'investor2']))
            ->get(route('investor2.dashboard'))->assertOk()->viewData('page')['props']['expenseBreakdown'];
        $this->assertCount(3, $first);
        $this->assertCount(1, $second);
        $this->assertEquals(600, collect($first)->sum('amount'));
        $this->assertEquals(200, collect($second)->sum('amount'));
    }
}
