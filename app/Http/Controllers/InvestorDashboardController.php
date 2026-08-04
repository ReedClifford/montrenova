<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorSetting;
use App\Models\Watch;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InvestorDashboardController extends Controller
{
    private const SETTING_KEY = 'main';

    private const START_DATE = '2026-08-03';

    private const DEFAULT_BRAND_CUT_PERCENTAGE = 50;

    public function index(Request $request): Response
    {
        $settings = $this->getSettings();

        $startDate = Carbon::parse(
            $settings->investment_start_date
                ?? self::START_DATE
        )->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Investors
        |--------------------------------------------------------------------------
        */

        $investors = Investor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $totalCapital = (float) $investors->sum(
            fn (Investor $investor): float =>
                (float) ($investor->capital_amount ?? 0)
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Records
        |--------------------------------------------------------------------------
        |
        | Existing scope is preserved:
        | only records created on or after the investment start date are included.
        |
        */

        $watches = Watch::query()
            ->with('primaryImage')
            ->where('created_at', '>=', $startDate)
            ->latest('created_at')
            ->get();

        $expenses = Expense::query()
            ->where('created_at', '>=', $startDate)
            ->latest('created_at')
            ->get();

        $soldWatches = $watches
            ->where('status', 'sold')
            ->values();

        $inventoryWatches = $watches
            ->where('status', '!=', 'sold')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Core Totals
        |--------------------------------------------------------------------------
        */

        $totalWatchCapital = (float) $watches->sum(
            fn (Watch $watch): float =>
                (float) ($watch->capital_price ?? 0)
        );

        $soldWatchCapital = (float) $soldWatches->sum(
            fn (Watch $watch): float =>
                (float) ($watch->capital_price ?? 0)
        );

        $inventoryCapital = (float) $inventoryWatches->sum(
            fn (Watch $watch): float =>
                (float) ($watch->capital_price ?? 0)
        );

        $totalSales = (float) $soldWatches->sum(
            fn (Watch $watch): float =>
                (float) ($watch->sold_price ?? 0)
        );

        $grossProfit =
            $totalSales - $soldWatchCapital;

        $totalExpenses = (float) $expenses->sum(
            fn (Expense $expense): float =>
                (float) ($expense->amount ?? 0)
        );

        $netProfit =
            $grossProfit - $totalExpenses;

        $currentOnHandMoney =
            $totalCapital
            + $totalSales
            - $totalWatchCapital
            - $totalExpenses;

        $currentFundValue =
            $currentOnHandMoney
            + $inventoryCapital;

        /*
        |--------------------------------------------------------------------------
        | Profit Distribution
        |--------------------------------------------------------------------------
        */

        $brandCutPercentage =
            (float) ($settings->brand_cut_percentage
                ?? self::DEFAULT_BRAND_CUT_PERCENTAGE);

        $profitDistributionBase =
            max($netProfit, 0);

        $brandCut = round(
            $profitDistributionBase
            * ($brandCutPercentage / 100),
            2
        );

        $distributableProfit = round(
            $profitDistributionBase - $brandCut,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Investor Allocation
        |--------------------------------------------------------------------------
        */

        $investorRecords = $investors
            ->values()
            ->map(function (
                Investor $investor
            ) use (
                $totalCapital,
                $distributableProfit
            ): array {
                $capitalAmount =
                    (float) ($investor->capital_amount ?? 0);

                $capitalRatio = $totalCapital > 0
                    ? $capitalAmount / $totalCapital
                    : 0;

                return [
                    'id' =>
                        $investor->id,

                    'name' =>
                        $investor->name,

                    'capital_amount' =>
                        round($capitalAmount, 2),

                    'capital_share_percentage' =>
                        round($capitalRatio * 100, 4),

                    'profit_share' =>
                        round(
                            $distributableProfit
                            * $capitalRatio,
                            2
                        ),

                    'is_active' =>
                        (bool) $investor->is_active,
                ];
            });

        $investorRecords =
            $this->applyRoundingAdjustment(
                $investorRecords,
                $distributableProfit
            );

        $totalAllocatedProfit = round(
            (float) $investorRecords->sum('profit_share'),
            2
        );

        $unallocatedProfit =
            $investorRecords->isEmpty()
                ? $distributableProfit
                : round(
                    $distributableProfit
                    - $totalAllocatedProfit,
                    2
                );

        /*
        |--------------------------------------------------------------------------
        | Analytics Dates
        |--------------------------------------------------------------------------
        |
        | Important fix:
        | a sold watch uses date_sold when available.
        | If an older sold record has no date_sold, created_at is used so the
        | chart does not silently lose an otherwise valid sold record.
        |
        */

   $soldActivityYears = $soldWatches
    ->toBase()
    ->map(
        fn (Watch $watch): ?int =>
            $this->soldActivityDate($watch)?->year
    )
    ->filter()
    ->map(fn ($year): int => (int) $year)
    ->values();

$expenseActivityYears = $expenses
    ->toBase()
    ->map(
        fn (Expense $expense): ?int =>
            $this->expenseActivityDate($expense)?->year
    )
    ->filter()
    ->map(fn ($year): int => (int) $year)
    ->values();

$activityYears = $soldActivityYears
    ->merge($expenseActivityYears)
    ->unique()
    ->sort()
    ->values();

        $latestYear = max(
            now()->year,
            $startDate->year,
            (int) ($activityYears->max() ?? $startDate->year)
        );

        $availableYears = collect(
            range(
                $startDate->year,
                $latestYear
            )
        )
            ->reverse()
            ->values();

        /*
        | Default to the latest year that actually has activity.
        | This prevents opening the dashboard on an empty current-year chart
        | while valid sold records exist in another available year.
        */

        $defaultAnalyticsYear =
            (int) (
                $activityYears->max()
                ?? $availableYears->first()
                ?? now()->year
            );

        $selectedYear = (int) $request->query(
            'year',
            $defaultAnalyticsYear
        );

        if (! $availableYears->contains($selectedYear)) {
            $selectedYear =
                $defaultAnalyticsYear;
        }

        /*
        |--------------------------------------------------------------------------
        | Monthly Analytics
        |--------------------------------------------------------------------------
        */

        $monthlyAnalytics = collect(
            range(1, 12)
        )->map(function (
            int $month
        ) use (
            $selectedYear,
            $soldWatches,
            $expenses,
            $brandCutPercentage
        ): array {
            $monthlySoldWatches = $soldWatches
                ->filter(function (
                    Watch $watch
                ) use (
                    $selectedYear,
                    $month
                ): bool {
                    $date =
                        $this->soldActivityDate($watch);

                    return $date
                        && $date->year === $selectedYear
                        && $date->month === $month;
                })
                ->values();

            $monthlyExpenseRecords = $expenses
                ->filter(function (
                    Expense $expense
                ) use (
                    $selectedYear,
                    $month
                ): bool {
                    $date =
                        $this->expenseActivityDate($expense);

                    return $date
                        && $date->year === $selectedYear
                        && $date->month === $month;
                })
                ->values();

            $sales = (float) $monthlySoldWatches->sum(
                fn (Watch $watch): float =>
                    (float) ($watch->sold_price ?? 0)
            );

            $soldCapital = (float) $monthlySoldWatches->sum(
                fn (Watch $watch): float =>
                    (float) ($watch->capital_price ?? 0)
            );

            $monthlyExpenses = (float) $monthlyExpenseRecords->sum(
                fn (Expense $expense): float =>
                    (float) ($expense->amount ?? 0)
            );

            $monthlyGrossProfit =
                $sales - $soldCapital;

            $monthlyNetProfit =
                $monthlyGrossProfit
                - $monthlyExpenses;

            $monthlyDistributionBase =
                max($monthlyNetProfit, 0);

            $monthlyBrandCut = round(
                $monthlyDistributionBase
                * ($brandCutPercentage / 100),
                2
            );

            $monthlyDistributableProfit = round(
                $monthlyDistributionBase
                - $monthlyBrandCut,
                2
            );

            $monthDate = Carbon::create(
                $selectedYear,
                $month,
                1
            );

            return [
                'month_number' =>
                    $month,

                'month' =>
                    $monthDate->format('F'),

                'month_short' =>
                    $monthDate->format('M'),

                'sold_watches' =>
                    $monthlySoldWatches->count(),

                'expense_records' =>
                    $monthlyExpenseRecords->count(),

                'sales' =>
                    round($sales, 2),

                'sold_capital' =>
                    round($soldCapital, 2),

                'gross_profit' =>
                    round($monthlyGrossProfit, 2),

                'expenses' =>
                    round($monthlyExpenses, 2),

                'net_profit' =>
                    round($monthlyNetProfit, 2),

                'brand_cut' =>
                    $monthlyBrandCut,

                'distributable_profit' =>
                    $monthlyDistributableProfit,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Selected Year Summary
        |--------------------------------------------------------------------------
        |
        | Annual distribution is calculated from annual Net Profit as one total.
        | It is not created by adding rounded monthly Brand Cuts.
        |
        */

        $yearSales =
            (float) $monthlyAnalytics->sum('sales');

        $yearSoldCapital =
            (float) $monthlyAnalytics->sum('sold_capital');

        $yearGrossProfit =
            $yearSales - $yearSoldCapital;

        $yearExpenses =
            (float) $monthlyAnalytics->sum('expenses');

        $yearNetProfit =
            $yearGrossProfit - $yearExpenses;

        $yearDistributionBase =
            max($yearNetProfit, 0);

        $yearBrandCut = round(
            $yearDistributionBase
            * ($brandCutPercentage / 100),
            2
        );

        $yearDistributableProfit = round(
            $yearDistributionBase
            - $yearBrandCut,
            2
        );

        $yearSummary = [
            'sales' =>
                round($yearSales, 2),

            'sold_capital' =>
                round($yearSoldCapital, 2),

            'gross_profit' =>
                round($yearGrossProfit, 2),

            'expenses' =>
                round($yearExpenses, 2),

            'net_profit' =>
                round($yearNetProfit, 2),

            'brand_cut' =>
                $yearBrandCut,

            'distributable_profit' =>
                $yearDistributableProfit,

            'monthly_distributable_total' =>
                round(
                    (float) $monthlyAnalytics
                        ->sum('distributable_profit'),
                    2
                ),

            'sold_watches' =>
                (int) $monthlyAnalytics
                    ->sum('sold_watches'),

            'expense_records' =>
                (int) $monthlyAnalytics
                    ->sum('expense_records'),
        ];

        $monthsWithActivity = $monthlyAnalytics
            ->filter(
                fn (array $record): bool =>
                    (int) $record['sold_watches'] > 0
                    || (int) $record['expense_records'] > 0
                    || (float) $record['sales'] != 0.0
                    || (float) $record['expenses'] != 0.0
            )
            ->values();

        $bestMonth = $monthsWithActivity
            ->sortByDesc('net_profit')
            ->first();

        $worstMonth = $monthsWithActivity
            ->sortBy('net_profit')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Yearly Analytics
        |--------------------------------------------------------------------------
        */

        $yearlyAnalytics = $availableYears
            ->sort()
            ->values()
            ->map(function (
                int $year
            ) use (
                $soldWatches,
                $expenses,
                $brandCutPercentage
            ): array {
                $yearSoldWatches = $soldWatches
                    ->filter(function (
                        Watch $watch
                    ) use (
                        $year
                    ): bool {
                        $date =
                            $this->soldActivityDate($watch);

                        return $date
                            && $date->year === $year;
                    })
                    ->values();

                $yearExpenseRecords = $expenses
                    ->filter(function (
                        Expense $expense
                    ) use (
                        $year
                    ): bool {
                        $date =
                            $this->expenseActivityDate($expense);

                        return $date
                            && $date->year === $year;
                    })
                    ->values();

                $sales = (float) $yearSoldWatches->sum(
                    fn (Watch $watch): float =>
                        (float) ($watch->sold_price ?? 0)
                );

                $soldCapital = (float) $yearSoldWatches->sum(
                    fn (Watch $watch): float =>
                        (float) ($watch->capital_price ?? 0)
                );

                $yearExpenses = (float) $yearExpenseRecords->sum(
                    fn (Expense $expense): float =>
                        (float) ($expense->amount ?? 0)
                );

                $grossProfit =
                    $sales - $soldCapital;

                $netProfit =
                    $grossProfit - $yearExpenses;

                $distributionBase =
                    max($netProfit, 0);

                $brandCut = round(
                    $distributionBase
                    * ($brandCutPercentage / 100),
                    2
                );

                $distributableProfit = round(
                    $distributionBase
                    - $brandCut,
                    2
                );

                return [
                    'year' =>
                        $year,

                    'sold_watches' =>
                        $yearSoldWatches->count(),

                    'expense_records' =>
                        $yearExpenseRecords->count(),

                    'sales' =>
                        round($sales, 2),

                    'sold_capital' =>
                        round($soldCapital, 2),

                    'gross_profit' =>
                        round($grossProfit, 2),

                    'expenses' =>
                        round($yearExpenses, 2),

                    'net_profit' =>
                        round($netProfit, 2),

                    'brand_cut' =>
                        $brandCut,

                    'distributable_profit' =>
                        $distributableProfit,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Frontend Records
        |--------------------------------------------------------------------------
        */

        $watchRecords = $watches
            ->map(function (
                Watch $watch
            ): array {
                $capitalPrice =
                    (float) ($watch->capital_price ?? 0);

                $sellingPrice =
                    (float) ($watch->selling_price ?? 0);

                $discountedPrice =
                    (float) ($watch->discounted_price ?? 0);

                $soldPrice =
                    (float) ($watch->sold_price ?? 0);

                $isSold =
                    $watch->status === 'sold';

                $expectedSellingPrice =
                    $discountedPrice > 0
                        ? $discountedPrice
                        : $sellingPrice;

                return [
                    'id' =>
                        $watch->id,

                    'brand' =>
                        $watch->brand,

                    'model_name' =>
                        $watch->model_name,

                    'reference_number' =>
                        $watch->reference_number,

                    'status' =>
                        $watch->status,

                    'capital_price' =>
                        $capitalPrice,

                    'selling_price' =>
                        $sellingPrice,

                    'discounted_price' =>
                        $discountedPrice,

                    'sold_price' =>
                        $isSold
                            ? $soldPrice
                            : null,

                    'gross_profit' =>
                        $isSold
                            ? round(
                                $soldPrice - $capitalPrice,
                                2
                            )
                            : null,

                    'potential_profit' =>
                        ! $isSold
                            ? round(
                                $expectedSellingPrice
                                - $capitalPrice,
                                2
                            )
                            : null,

                    'date_sold' =>
                        $watch->date_sold,

                    'analytics_date' =>
                        $isSold
                            ? $this
                                ->soldActivityDate($watch)
                                ?->format('Y-m-d')
                            : null,

                    'created_at' =>
                        $watch->created_at
                            ?->format('Y-m-d H:i:s'),

                    'primary_image' =>
                        $watch->primaryImage,
                ];
            })
            ->values();

        $expenseRecords = $expenses
            ->map(function (
                Expense $expense
            ): array {
                return [
                    'id' =>
                        $expense->id,

                    'title' =>
                        $expense->title,

                    'category' =>
                        $expense->category,

                    'amount' =>
                        (float) $expense->amount,

                    'spent_at' =>
                        $expense->spent_at
                            ?->format('Y-m-d'),

                    'analytics_date' =>
                        $this
                            ->expenseActivityDate($expense)
                            ?->format('Y-m-d'),

                    'notes' =>
                        $expense->notes,

                    'created_at' =>
                        $expense->created_at
                            ?->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        return Inertia::render(
            'Investor/Dashboard',
            [
                'permissions' => [
                    'can_edit' =>
                        $request->user()->role
                        === 'owner',

                    'can_manage_investors' =>
                        $request->user()->role
                        === 'owner',

                    'is_owner' =>
                        $request->user()->role
                        === 'owner',

                    'is_investor' =>
                        $request->user()->role
                        === 'investor',
                ],

                'settings' => [
                    'brand_cut_percentage' =>
                        $brandCutPercentage,

                    'investment_start_date' =>
                        $startDate->format('Y-m-d'),
                ],

                'summary' => [
                    'total_capital' =>
                        round($totalCapital, 2),

                    'total_watch_capital' =>
                        round($totalWatchCapital, 2),

                    'sold_watch_capital' =>
                        round($soldWatchCapital, 2),

                    'inventory_capital' =>
                        round($inventoryCapital, 2),

                    'total_sales' =>
                        round($totalSales, 2),

                    'gross_profit' =>
                        round($grossProfit, 2),

                    'total_expenses' =>
                        round($totalExpenses, 2),

                    'net_profit' =>
                        round($netProfit, 2),

                    'current_on_hand_money' =>
                        round($currentOnHandMoney, 2),

                    'current_fund_value' =>
                        round($currentFundValue, 2),

                    'total_investors' =>
                        $investors->count(),

                    'total_watches' =>
                        $watches->count(),

                    'sold_watches' =>
                        $soldWatches->count(),

                    'inventory_watches' =>
                        $inventoryWatches->count(),
                ],

                'waterfall' => [
                    'distribution_base' =>
                        round(
                            $profitDistributionBase,
                            2
                        ),

                    'net_profit' =>
                        round($netProfit, 2),

                    'brand_cut_percentage' =>
                        $brandCutPercentage,

                    'brand_cut' =>
                        $brandCut,

                    'distributable_profit' =>
                        $distributableProfit,

                    'total_investor_profit' =>
                        $totalAllocatedProfit,

                    'unallocated_profit' =>
                        round($unallocatedProfit, 2),

                    'montre_nova_profit' =>
                        $brandCut,
                ],

                'analytics' => [
                    'selected_year' =>
                        $selectedYear,

                    'available_years' =>
                        $availableYears->values(),

                    'has_activity' =>
                        $monthsWithActivity->isNotEmpty(),

                    'year_summary' =>
                        $yearSummary,

                    'best_month' =>
                        $bestMonth,

                    'worst_month' =>
                        $worstMonth,

                    'monthly' =>
                        $monthlyAnalytics->values(),

                    'yearly' =>
                        $yearlyAnalytics->values(),
                ],

                'investors' =>
                    $investorRecords->values(),

                'watches' =>
                    $watchRecords,

                'expenses' =>
                    $expenseRecords,

                'startDate' =>
                    $startDate->format('Y-m-d'),
            ]
        );
    }

    public function updateSettings(
        Request $request
    ): RedirectResponse {
        abort_unless(
            $request->user()?->role
                === 'owner',
            403,
            'Only the owner can update the Brand Cut.'
        );

        $validated = $request->validate([
            'brand_cut_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        $settings =
            $this->getSettings();

        $settings->update([
            'brand_cut_percentage' =>
                $validated[
                    'brand_cut_percentage'
                ],

            'updated_by' =>
                $request->user()->id,
        ]);

        return back()->with(
            'success',
            'Brand Cut percentage updated successfully.'
        );
    }

    public function storeInvestor(
        Request $request
    ): RedirectResponse {
        abort_unless(
            $request->user()?->role
                === 'owner',
            403,
            'Only the owner can add investors.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'capital_amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $nextSortOrder =
            ((int) Investor::query()
                ->max('sort_order')) + 1;

        Investor::query()->create([
            'name' =>
                $validated['name'],

            'capital_amount' =>
                $validated[
                    'capital_amount'
                ],

            'is_active' =>
                true,

            'sort_order' =>
                $nextSortOrder,
        ]);

        return back()->with(
            'success',
            'Investor added successfully.'
        );
    }

    public function updateInvestor(
        Request $request,
        Investor $investor
    ): RedirectResponse {
        abort_unless(
            $request->user()?->role
                === 'owner',
            403,
            'Only the owner can update investors.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'capital_amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $investor->update([
            'name' =>
                $validated['name'],

            'capital_amount' =>
                $validated[
                    'capital_amount'
                ],
        ]);

        return back()->with(
            'success',
            'Investor updated successfully.'
        );
    }

    public function destroyInvestor(
        Request $request,
        Investor $investor
    ): RedirectResponse {
        abort_unless(
            $request->user()?->role
                === 'owner',
            403,
            'Only the owner can delete investors.'
        );

        $investor->delete();

        return back()->with(
            'success',
            'Investor removed successfully.'
        );
    }

    private function getSettings(): InvestorSetting
    {
        return InvestorSetting::query()
            ->firstOrCreate(
                [
                    'setting_key' =>
                        self::SETTING_KEY,
                ],
                [
                    'capital_amount' =>
                        0,

                    'investor_profit_percentage' =>
                        0,

                    'management_fee_percentage' =>
                        0,

                    'brand_cut_percentage' =>
                        self::DEFAULT_BRAND_CUT_PERCENTAGE,

                    'investment_start_date' =>
                        self::START_DATE,
                ]
            );
    }

    private function soldActivityDate(
        Watch $watch
    ): ?Carbon {
        $value =
            $watch->date_sold
            ?: $watch->created_at;

        return $value
            ? Carbon::parse($value)
            : null;
    }

    private function expenseActivityDate(
        Expense $expense
    ): ?Carbon {
        $value =
            $expense->spent_at
            ?: $expense->created_at;

        return $value
            ? Carbon::parse($value)
            : null;
    }

    private function applyRoundingAdjustment(
        Collection $investorRecords,
        float $distributableProfit
    ): Collection {
        if ($investorRecords->isEmpty()) {
            return $investorRecords;
        }

        $allocated = round(
            (float) $investorRecords
                ->sum('profit_share'),
            2
        );

        $difference = round(
            $distributableProfit - $allocated,
            2
        );

        if (abs($difference) < 0.01) {
            return $investorRecords;
        }

        $lastIndex =
            $investorRecords->count() - 1;

        $lastInvestor =
            $investorRecords->get($lastIndex);

        $lastInvestor['profit_share'] = round(
            (float) $lastInvestor['profit_share']
            + $difference,
            2
        );

        $investorRecords->put(
            $lastIndex,
            $lastInvestor
        );

        return $investorRecords;
    }
}
