<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use Illuminate\Http\Response;

class PreorderReceiptController extends Controller
{
    public function admin(Preorder $preorder): Response
    {
        return $this->receipt($preorder);
    }

    public function track(string $token): Response
    {
        return $this->receipt(Preorder::where('tracking_token', $token)->firstOrFail());
    }

    private function receipt(Preorder $order): Response
    {
        $order->load(['listing', 'entries']);
        $transactions = $order->entries->whereIn('type', ['payment', 'refund'])->sortBy('id');
        abort_unless($transactions->contains('type', 'payment'), 404);

        return response()->view('preorders.receipt', [
            'order' => $order,
            'reference' => 'MN-PO-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
            'watch' => $order->listing->watch_snapshot,
            'transactions' => $transactions,
        ], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
