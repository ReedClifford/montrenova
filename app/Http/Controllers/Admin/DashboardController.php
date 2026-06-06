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

        $soldPriceSql = $this->soldPriceSql();
        $topProfitingWatches = Watch::query()
            ->where('status', 'sold')
            ->get()
            ->map(function ($watch) {
                $soldPrice = (float) (
                    $watch->discounted_price
                    ?: $watch->selling_price
                    ?: $watch->price
                    ?: 0
                );

                $capital = (float) ($watch->capital_price ?: 0);
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
        | Net Profit:
        | Total Sales - Sold Capital Cost - Total Expenses
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
                DB::raw("COALESCE(SUM({$soldPriceSql} - capital_price), 0) as profit_total"),
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

                /*
                |--------------------------------------------------------------------------
                | Current On-hand Money
                |--------------------------------------------------------------------------
                |
                | current_money is kept for your existing Vue dashboard.
                | current_onhand_money is added as a clearer alias.
                |
                */

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
                COALESCE(SUM(capital_price), 0) as total_capital,
                COALESCE(SUM({$soldPriceSql} - capital_price), 0) as gross_profit
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
        return "
            CASE
                WHEN discounted_price IS NOT NULL AND discounted_price > 0
                THEN discounted_price
                ELSE selling_price
            END
        ";
    }
}