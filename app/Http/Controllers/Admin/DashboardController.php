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
        $startingCash = BusinessSetting::getDecimal('starting_cash', 0);

        $totalWatches = Watch::count();
        $availableWatches = Watch::where('status', 'available')->count();
        $reservedWatches = Watch::where('status', 'reserved')->count();
        $soldWatches = Watch::where('status', 'sold')->count();

        $soldPriceSql = $this->soldPriceSql();

        $totalCapitalSpent = (float) Watch::sum('capital_price');

        $inventoryValue = (float) Watch::where('status', '!=', 'sold')
            ->sum('capital_price');

        $totalSales = (float) Watch::where('status', 'sold')
            ->selectRaw("COALESCE(SUM({$soldPriceSql}), 0) as total")
            ->value('total');

        $soldCapitalCost = (float) Watch::where('status', 'sold')
            ->sum('capital_price');

        $totalExpenses = (float) Expense::sum('amount');

        $grossProfit = $totalSales - $soldCapitalCost;
        $netProfit = $grossProfit - $totalExpenses;

        $currentMoney = $startingCash - $totalCapitalSpent + $totalSales - $totalExpenses;

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
                'current_money' => $currentMoney,
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
                $start->toDateString(),
                $end->toDateString(),
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
                        $start->toDateString(),
                        $end->toDateString(),
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