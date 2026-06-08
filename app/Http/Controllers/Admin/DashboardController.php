<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\Watch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startingCash = (float) BusinessSetting::getDecimal('starting_cash', 0);

        $totalWatches = Watch::count();
        $availableWatches = Watch::where('status', 'available')->count();
        $reservedWatches = Watch::where('status', 'reserved')->count();
        $soldWatches = Watch::where('status', 'sold')->count();

        /*
        |--------------------------------------------------------------------------
        | Sold Price Formula
        |--------------------------------------------------------------------------
        |
        | The final sold amount should come from watches.sold_price only.
        | Do not use discounted_price, selling_price, or price for sold reports.
        |
        */

        $soldPriceSql = $this->soldPriceSql();

        $topProfitingWatches = Watch::query()
            ->where('status', 'sold')
            ->get()
            ->map(function ($watch) {
                $soldPrice = (float) ($watch->sold_price ?? 0);
                $capital = (float) ($watch->capital_price ?? 0);
                $profit = $soldPrice - $capital;

                return [
                    'id' => $watch->id,
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,
                    'date_sold' => $watch->date_sold,
                    'sales_total' => $soldPrice,
                    'capital_total' => $capital,
                    'profit_total' => $profit,
                ];
            })
            ->sortByDesc('profit_total')
            ->take(5)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Main Financial Formula
        |--------------------------------------------------------------------------
        |
        | Current On-hand Money:
        | Starting Cash + Total Sales - Total Capital Spent - Total Expenses
        |
        | Total Sales:
        | SUM(sold_price) from sold watches only
        |
        | Gross Profit:
        | Total Sales - Sold Capital Cost
        |
        | Net Profit:
        | Gross Profit - Total Expenses
        |
        | Inventory Value:
        | Capital value of unsold watches
        |
        */

        $totalCapitalSpent = (float) Watch::sum('capital_price');

        $inventoryValue = (float) Watch::query()
            ->whereIn('status', ['available', 'reserved'])
            ->sum('capital_price');

        $totalSales = (float) Watch::query()
            ->where('status', 'sold')
            ->selectRaw("COALESCE(SUM({$soldPriceSql}), 0) as total")
            ->value('total');

        $soldCapitalCost = (float) Watch::query()
            ->where('status', 'sold')
            ->sum('capital_price');

        $totalExpenses = (float) Expense::sum('amount');

        $grossProfit = $totalSales - $soldCapitalCost;

        $netProfit = $grossProfit - $totalExpenses;

        $currentOnhandMoney = $startingCash + $totalSales - $totalCapitalSpent - $totalExpenses;

        $now = now();

        $salesPerformance = [
            'weekly' => $this->periodSummary(
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfWeek(Carbon::SUNDAY)
            ),

            'monthly' => $this->periodSummary(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth()
            ),

            'yearly' => $this->periodSummary(
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ),
        ];

        $selectedMonth = $request->input('month', now()->format('Y-m'));

        try {
            $selectedMonthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $selectedMonthStart = now()->startOfMonth();
        }

        $selectedMonthEnd = $selectedMonthStart->copy()->endOfMonth();

        $selectedMonthSummary = $this->periodSummary(
            $selectedMonthStart,
            $selectedMonthEnd
        );

        $topSoldUnits = Watch::query()
            ->where('status', 'sold')
            ->select([
                'brand',
                'model_name',
                'reference_number',
                DB::raw('COUNT(*) as sold_count'),
                DB::raw("COALESCE(SUM({$soldPriceSql}), 0) as sales_total"),
                DB::raw("COALESCE(SUM(({$soldPriceSql}) - COALESCE(capital_price, 0)), 0) as profit_total"),
            ])
            ->groupBy('brand', 'model_name', 'reference_number')
            ->orderByDesc('sold_count')
            ->orderByDesc('sales_total')
            ->limit(5)
            ->get();

        $recentExpenses = Expense::query()
            ->latest()
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'money' => [
                'starting_cash' => $startingCash,

                'current_money' => $currentOnhandMoney,
                'current_onhand_money' => $currentOnhandMoney,

                'total_capital_spent' => $totalCapitalSpent,
                'inventory_value' => $inventoryValue,
                'total_sales' => $totalSales,
                'sold_capital_cost' => $soldCapitalCost,
                'gross_profit' => $grossProfit,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
            ],

            'counts' => [
                'total_watches' => $totalWatches,
                'available_watches' => $availableWatches,
                'reserved_watches' => $reservedWatches,
                'sold_watches' => $soldWatches,
            ],

            'salesPerformance' => $salesPerformance,

            'salesProfitTrend' => $this->salesProfitTrend(12),

            'selectedMonth' => $selectedMonth,
            'selectedMonthSummary' => $selectedMonthSummary,

            'topSoldUnits' => $topSoldUnits,
            'recentExpenses' => $recentExpenses,
            'topProfitingWatches' => $topProfitingWatches,
        ]);
    }

    public function updateStartingCash(Request $request)
    {
        $validated = $request->validate([
            'starting_cash' => ['required', 'numeric', 'min:0'],
        ]);

        BusinessSetting::setDecimal('starting_cash', (float) $validated['starting_cash']);

        return back()->with('success', 'Starting cash updated.');
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Expense::create($validated);

        return back()->with('success', 'Expense added.');
    }

    public function destroyExpense(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }

    private function salesProfitTrend(int $months = 12): array
    {
        $months = max(3, min($months, 24));
        $soldPriceSql = $this->soldPriceSql();

        $start = now()
            ->copy()
            ->startOfMonth()
            ->subMonths($months - 1);

        $end = now()
            ->copy()
            ->endOfMonth();

        $monthlyRows = Watch::query()
            ->where('status', 'sold')
            ->whereNotNull('date_sold')
            ->whereBetween('date_sold', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->selectRaw("
                DATE_FORMAT(date_sold, '%Y-%m') as month_key,
                COUNT(*) as sold_count,
                COALESCE(SUM({$soldPriceSql}), 0) as total_sales,
                COALESCE(SUM(COALESCE(capital_price, 0)), 0) as total_capital,
                COALESCE(SUM(({$soldPriceSql}) - COALESCE(capital_price, 0)), 0) as gross_profit
            ")
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $rows = [];
        $labels = [];
        $sales = [];
        $profit = [];
        $soldCounts = [];

        $totalSales = 0;
        $totalProfit = 0;
        $totalCapital = 0;
        $totalSold = 0;
        $maxValue = 0;

        for ($index = 0; $index < $months; $index++) {
            $month = $start->copy()->addMonths($index);
            $monthKey = $month->format('Y-m');
            $record = $monthlyRows->get($monthKey);

            $soldCount = (int) ($record->sold_count ?? 0);
            $monthlySales = (float) ($record->total_sales ?? 0);
            $monthlyCapital = (float) ($record->total_capital ?? 0);
            $monthlyProfit = (float) ($record->gross_profit ?? 0);

            $labels[] = $month->format('M Y');
            $sales[] = $monthlySales;
            $profit[] = $monthlyProfit;
            $soldCounts[] = $soldCount;

            $totalSales += $monthlySales;
            $totalProfit += $monthlyProfit;
            $totalCapital += $monthlyCapital;
            $totalSold += $soldCount;

            $maxValue = max($maxValue, $monthlySales, $monthlyProfit);

            $rows[] = [
                'month' => $monthKey,
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'sold_count' => $soldCount,
                'total_sales' => $monthlySales,
                'total_capital' => $monthlyCapital,
                'gross_profit' => $monthlyProfit,
            ];
        }

        $bestMonth = collect($rows)
            ->sortByDesc('gross_profit')
            ->first();

        return [
            'labels' => $labels,
            'sales' => $sales,
            'profit' => $profit,
            'sold_counts' => $soldCounts,
            'rows' => $rows,
            'max_value' => $maxValue,
            'totals' => [
                'months' => $months,
                'sold_count' => $totalSold,
                'total_sales' => $totalSales,
                'total_capital' => $totalCapital,
                'gross_profit' => $totalProfit,
                'best_month' => $bestMonth,
            ],
        ];
    }

    private function periodSummary(Carbon $start, Carbon $end): array
    {
        $soldPriceSql = $this->soldPriceSql();

        $sales = Watch::query()
            ->where('status', 'sold')
            ->whereBetween('date_sold', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->selectRaw("
                COUNT(*) as sold_count,
                COALESCE(SUM({$soldPriceSql}), 0) as total_sales,
                COALESCE(SUM(COALESCE(capital_price, 0)), 0) as total_capital,
                COALESCE(SUM(({$soldPriceSql}) - COALESCE(capital_price, 0)), 0) as gross_profit
            ")
            ->first();

        $expenses = Expense::query()
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween('spent_at', [
                        $start->copy()->startOfDay(),
                        $end->copy()->endOfDay(),
                    ])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereNull('spent_at')
                            ->whereBetween('created_at', [
                                $start->copy()->startOfDay(),
                                $end->copy()->endOfDay(),
                            ]);
                    });
            })
            ->sum('amount');

        $totalSales = (float) ($sales->total_sales ?? 0);
        $totalCapital = (float) ($sales->total_capital ?? 0);
        $grossProfit = (float) ($sales->gross_profit ?? 0);
        $totalExpenses = (float) $expenses;

        return [
            'label' => $start->format('F Y'),
            'date_range' => $start->format('M d, Y') . ' - ' . $end->format('M d, Y'),
            'sold_count' => (int) ($sales->sold_count ?? 0),
            'total_sales' => $totalSales,
            'total_capital' => $totalCapital,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $grossProfit - $totalExpenses,
        ];
    }

    private function soldPriceSql(): string
    {
        return 'COALESCE(sold_price, 0)';
    }
}