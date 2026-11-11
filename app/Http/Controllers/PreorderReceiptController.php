<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\SaleInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PreorderReceiptController extends Controller
{
    public function admin(Request $request, Preorder $preorder): Response
    {
        return $this->receipt($request, $preorder);
    }

    public function track(Request $request, string $token): Response
    {
        return $this->receipt($request, Preorder::where('tracking_token', $token)->firstOrFail(), $token);
    }

    private function receipt(Request $request, Preorder $preorder, ?string $token = null): Response
    {
        $number = $request->query('invoice');
        abort_if($number !== null && ! is_string($number), 404);

        $invoice = DB::transaction(function () use ($preorder, $number, $token) {
            $order = Preorder::lockForUpdate()->findOrFail($preorder->id);
            abort_if($token !== null && $order->tracking_token !== $token, 404);

            $invoices = SaleInvoice::where('preorder_id', $order->id);
            if ($number !== null) {
                return $invoices->where('number', $number)->firstOrFail();
            }

            $order->load(['listing', 'entries']);
            $transactions = $order->entries->whereIn('type', ['payment', 'refund'])->sortBy('id');
            abort_unless($transactions->contains('type', 'payment'), 404);

            $snapshot = [
                'order' => $order->only(['customer_name', 'status', 'total_price', 'paid', 'balance', 'refundable']),
                'watch' => array_intersect_key($order->listing->watch_snapshot, array_flip(['brand', 'model_name', 'reference_number'])),
                'transactions' => $transactions->map(fn ($entry) => $entry->only(['type', 'method', 'amount'])
                    + ['paid_at' => $entry->paid_at?->format('M j, Y')])->values()->all(),
            ];
            $fingerprint = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
            $sequence = $invoices->count() + 1;
            $reference = 'MN-PO-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);

            return SaleInvoice::firstOrCreate(['preorder_id' => $order->id, 'fingerprint' => $fingerprint], [
                'number' => $sequence === 1 ? $reference : $reference.'-'.$sequence,
                'snapshot' => $snapshot,
            ]);
        });

        return response()->view('preorders.receipt', $invoice->snapshot + [
            'reference' => $invoice->number,
            'issuedOn' => $invoice->created_at->format('M j, Y'),
            'versionUrl' => $request->url().'?'.http_build_query(['invoice' => $invoice->number]),
        ], 200, SaleInvoice::PRIVATE_HEADERS);
    }
}
