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
                DB::raw("COALESCE(SUM(COALESCE(capital_price, 0)), 0) as capital_total"),
                DB::raw("COALESCE(SUM({$soldPriceSql} - COALESCE(capital_price, 0)), 0) as profit_total"),
            ])
            ->groupBy('brand', 'model_name', 'reference_number')
            ->orderByDesc('sold_count')
            ->orderByDesc('sales_total')
            ->limit(5)
            ->get()
            ->map(fn ($unit) => [
                'brand' => $unit->brand,
                'model_name' => $unit->model_name,
                'reference_number' => $unit->reference_number,
                'sold_count' => (int) $unit->sold_count,
                'sales_total' => (float) $unit->sales_total,
                'capital_total' => (float) $unit->capital_total,
                'profit_total' => (float) $unit->profit_total,
            ]);

        $recentSales = Watch::query()
            ->with('primaryImage')
            ->where('status', 'sold')
            ->latest('date_sold')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($watch) {
                $soldPrice = (float) ($watch->sold_price ?? 0);
                $capitalPrice = (float) ($watch->capital_price ?? 0);

                return [
                    'id' => $watch->id,
                    'brand' => $watch->brand,
                    'model_name' => $watch->model_name,
                    'reference_number' => $watch->reference_number,

                    'capital_price' => $capitalPrice,
                    'selling_price' => (float) ($watch->selling_price ?? 0),
                    'discounted_price' => $watch->discounted_price !== null
                        ? (float) $watch->discounted_price
                        : null,

                    // IMPORTANT:
                    // Sales analytics must use the actual final sold amount.
                    'sold_price' => $soldPrice,
                    'profit' => $soldPrice - $capitalPrice,

                    'date_sold' => $this->formatDateValue($watch->date_sold),
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
                COALESCE(SUM(COALESCE(capital_price, 0)), 0) as total_capital,
                COALESCE(SUM({$soldPriceSql} - COALESCE(capital_price, 0)), 0) as gross_profit
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

            // Based on actual final sold amount.
            'total_sales' => $totalSales,

            // Based on capital of sold watches.
            'total_capital' => $totalCapital,

            // sold_price - capital_price
            'gross_profit' => $grossProfit,

            // Expenses in the same period.
            'total_expenses' => $totalExpenses,

            // gross_profit - expenses
            'net_profit' => $grossProfit - $totalExpenses,
        ];
    }

    private function soldPriceSql(): string
    {
        /*
        |--------------------------------------------------------------------------
        | Final sold amount only
        |--------------------------------------------------------------------------
        | Do not use selling_price or discounted_price for sales analytics.
        | sold_price is the actual final amount collected when the watch was sold.
        */
        return 'COALESCE(sold_price, 0)';
    }

    private function formatDateValue($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}