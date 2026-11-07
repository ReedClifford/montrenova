<?php

namespace App\Services;

use App\Models\Preorder;

class PreorderAccounting
{
    /** Cash not yet represented by sold watches or cancellation expenses. */
    public static function summary($since = null): array
    {
        $orders = Preorder::with('entries')->where('status', '!=', 'fulfilled')
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))->get();
        $deposits = $orders->sum(fn ($order) => $order->paid);
        $supplierSpend = $orders->where('status', '!=', 'cancelled')->sum(fn ($order) => $order->cost);

        return ['customer_deposits' => round($deposits, 2), 'supplier_spend' => round($supplierSpend, 2),
            'cash_adjustment' => round($deposits - $supplierSpend, 2)];
    }
}
