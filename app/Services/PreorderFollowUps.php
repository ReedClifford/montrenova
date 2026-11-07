<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class PreorderFollowUps
{
    public const LABELS = [
        'deposit' => 'Awaiting deposit',
        'supplier' => 'Place supplier order',
        'sourcing' => 'Sourcing follow-up',
        'balance' => 'Arrived · unpaid',
        'handover' => 'Paid · prepare handover',
    ];

    public static function apply(Builder $query, string $queue): Builder
    {
        // Match the immutable payment ledger; supplier costs never reduce a customer's balance.
        $paid = "COALESCE((SELECT SUM(CASE WHEN type = 'payment' THEN amount WHEN type = 'refund' THEN -amount ELSE 0 END) FROM preorder_entries WHERE preorder_entries.preorder_id = preorders.id), 0)";

        return match ($queue) {
            'deposit' => $query->where('status', 'awaiting_deposit'),
            'supplier' => $query->where('status', 'confirmed'),
            'sourcing' => $query->where('status', 'ordered')->where(fn ($q) => $q
                ->whereDate('estimated_arrival', '<', today())
                ->orWhere('created_at', '<=', now()->subDays(14))),
            'balance' => $query->where('status', 'arrived')->whereRaw("total_price > {$paid}"),
            'handover' => $query->where('status', 'arrived')->whereRaw("total_price <= {$paid}"),
            default => $query,
        };
    }
}
