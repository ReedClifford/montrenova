<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        try {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }

        $monthEnd = $monthStart->copy()->endOfMonth();

        $baseQuery = Expense::query()
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query
                    ->whereBetween('spent_at', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ])
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->whereNull('spent_at')
                            ->whereBetween('created_at', [
                                $monthStart->copy()->startOfDay(),
                                $monthEnd->copy()->endOfDay(),
                            ]);
                    });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('category', $category));

        $totalExpenses = (float) (clone $baseQuery)->sum('amount');
        $expenseCount = (clone $baseQuery)->count();

        $expenses = $baseQuery
            ->latest('spent_at')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($expense) => [
                'id' => $expense->id,
                'title' => $expense->title,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'spent_at' => $expense->spent_at?->format('Y-m-d'),
                'notes' => $expense->notes,
            ]);

        $categories = Expense::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $categoryBreakdown = Expense::query()
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query
                    ->whereBetween('spent_at', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ])
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->whereNull('spent_at')
                            ->whereBetween('created_at', [
                                $monthStart->copy()->startOfDay(),
                                $monthEnd->copy()->endOfDay(),
                            ]);
                    });
            })
            ->selectRaw('COALESCE(NULLIF(category, ""), "General") as category_name, SUM(amount) as total')
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'summary' => [
                'selected_month' => $selectedMonth,
                'month_label' => $monthStart->format('F Y'),
                'date_range' => $monthStart->format('M d, Y') . ' - ' . $monthEnd->format('M d, Y'),
                'total_expenses' => $totalExpenses,
                'expense_count' => $expenseCount,
                'average_expense' => $expenseCount > 0 ? $totalExpenses / $expenseCount : 0,
            ],
            'categories' => $categories,
            'categoryBreakdown' => $categoryBreakdown,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'month' => $selectedMonth,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Expense::create($validated);

        return back()->with('success', 'Expense added successfully.');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'spent_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense->update($validated);

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted successfully.');
    }
}