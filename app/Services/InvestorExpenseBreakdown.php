<?php

namespace App\Services;

use App\Models\Preorder;
use Illuminate\Support\Collection;

class InvestorExpenseBreakdown
{
    /** Itemize cash costs without counting cancelled supplier spending twice. */
    public static function records(Collection $expenses, $startDate): array
    {
        $orders = Preorder::with(['listing', 'entries' => fn ($query) => $query->where('type', 'cost')])
            ->where('created_at', '>=', $startDate)->get()->keyBy('id');

        $costDetails = fn ($order) => $order->entries->map(fn ($entry) => [
            'id' => $entry->id,
            'amount' => (float) $entry->amount,
            'date' => $entry->paid_at->format('Y-m-d'),
            'method' => $entry->method,
            'notes' => $entry->notes,
        ])->values()->all();

        $records = $expenses->map(function ($expense) use ($orders, $costDetails) {
            $order = $expense->preorder_id ? $orders->get($expense->preorder_id) : null;

            return [
                'id' => 'expense-'.$expense->id,
                'title' => $expense->title,
                'category' => trim((string) $expense->category) ?: 'Uncategorized',
                'amount' => (float) $expense->amount,
                'date' => ($expense->spent_at ?? $expense->created_at)->format('Y-m-d'),
                'notes' => $expense->notes,
                'treatment' => 'operating',
                'order_id' => $expense->preorder_id,
                'method' => null,
                'details' => $order ? $costDetails($order) : [],
            ];
        });

        foreach ($orders as $order) {
            if ($order->status === 'cancelled') {
                continue; // Its costs are already itemized under the cancellation expense.
            }
            $watch = $order->listing->watch_snapshot;
            foreach ($costDetails($order) as $cost) {
                $records->push([
                    'id' => 'preorder-cost-'.$cost['id'],
                    'title' => trim(($watch['brand'] ?? '').' '.($watch['model_name'] ?? 'Watch')).' · Preorder #'.$order->id,
                    'category' => 'Preorder supplier / direct costs',
                    'amount' => $cost['amount'],
                    'date' => $cost['date'],
                    'notes' => $cost['notes'],
                    'treatment' => $order->status === 'fulfilled' ? 'fulfilled_capital' : 'pending_capital',
                    'order_id' => $order->id,
                    'method' => $cost['method'],
                    'details' => [],
                ]);
            }
        }

        return $records->sortByDesc('date')->values()->all();
    }
}
