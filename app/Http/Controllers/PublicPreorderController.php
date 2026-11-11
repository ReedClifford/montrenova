<?php

namespace App\Http\Controllers;

use App\Models\PreorderListing;
use App\Models\Preorder;
use Inertia\Inertia;

class PublicPreorderController extends Controller
{
    public function track(string $token)
    {
        $order = Preorder::with(['listing', 'entries'])->where('tracking_token', $token)->firstOrFail();
        $watch = $order->listing->watch_snapshot;
        $transactions = $order->entries->whereIn('type', ['payment', 'refund'])->values();

        return Inertia::render('Public/PreorderTracking', ['order' => [
            'reference' => 'MN-PO-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'watch_name' => trim(($watch['brand'] ?? '').' '.($watch['model_name'] ?? '')),
            'watch_reference' => $watch['reference_number'] ?? null,
            'image' => $watch['primary_image_url'] ?? null,
            'total_price' => (float) $order->total_price,
            'paid' => $order->paid, 'balance' => $order->balance,
            'required_deposit' => $order->required_deposit,
            'refundable' => $order->refundable,
            'estimated_arrival' => $order->estimated_arrival?->toDateString(),
            'updated_at' => collect([$order->updated_at?->toISOString(), ...$transactions->pluck('created_at')->map->toISOString()->all()])->filter()->max(),
            'transactions' => $transactions->map(fn ($entry) => $entry->only(['id', 'type', 'amount', 'paid_at', 'method'])),
        ], 'receiptUrl' => $transactions->contains('type', 'payment')
            ? route('public.preorders.receipt', $token)
            : null,
        ])->toResponse(request())->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function show(PreorderListing $listing)
    {
        abort_unless($listing->is_visible, 404);

        return Inertia::render('Public/PreorderShow', [
            'watch' => $listing->publicCard(),
            'relatedPreorders' => PreorderListing::where('is_visible', true)
                ->whereKeyNot($listing->id)->inRandomOrder()->limit(8)->get()->map->publicCard()->values(),
        ]);
    }
}
