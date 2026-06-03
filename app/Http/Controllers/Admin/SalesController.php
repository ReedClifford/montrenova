<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Watch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        try {
            $selectedMonthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $selectedMonthStart = now()->startOfMonth();
        }

        $selectedMonthEnd = $selectedMonthStart->copy()->endOfMonth();

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

        $selectedMonthSummary = $this->periodSummary(
            $selectedMonthStart,
            $selectedMonthEnd
        );

        $soldPriceSql = $this->soldPriceSql();

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

        $recentSales = Watch::query()
            ->with('primaryImage')
            ->where('status', 'sold')
            ->latest('date_sold')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($watch) {
                $soldPrice = $watch->discounted_price && $watch->discounted_price > 0
                    ? $watch->discounted_price
                    : $watch->selling_price;

                return [
                    'id' => $watch->id,
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,
                    'capital_price' => (float) $watch->capital_price,
                    'selling_price' => (float) $watch->selling_price,
                    'discounted_price' => $watch->discounted_price ? (float) $watch->discounted_price : null,
                    'sold_price' => (float) $soldPrice,
                    'profit' => (float) $soldPrice - (float) $watch->capital_price,
                    'date_sold' => $watch->date_sold?->format('Y-m-d'),
                    'primary_image' => $watch->primaryImage,
                ];
            });

        return Inertia::render('Admin/Sales/Index', [
            'selectedMonth' => $selectedMonth,
            'selectedMonthSummary' => $selectedMonthSummary,
            'salesPerformance' => $salesPerformance,
            'topSoldUnits' => $topSoldUnits,
            'recentSales' => $recentSales,
        ]);
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