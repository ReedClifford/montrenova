<?php

namespace App\Http\Controllers;

use App\Models\SaleInvoice;
use Illuminate\Http\Response;

class PublicPurchaseController extends Controller
{
    public function show(string $token): Response
    {
        $invoice = $this->purchase($token);

        return response()->view('sales.purchase', [
            'reference' => $invoice->number,
            'watch' => $invoice->snapshot,
            'warrantyExpiresOn' => $invoice->watch->warrantyExpiresOn(),
            'invoiceUrl' => route('public.sales.invoice', $token),
        ], 200, SaleInvoice::PRIVATE_HEADERS);
    }

    public function invoice(string $token): Response
    {
        $invoice = $this->purchase($token);

        return response()->view('sales.receipt', [
            'reference' => $invoice->number,
            'issuedOn' => $invoice->created_at->format('M j, Y'),
            'watch' => $invoice->snapshot,
        ], 200, SaleInvoice::PRIVATE_HEADERS);
    }

    private function purchase(string $token): SaleInvoice
    {
        $invoice = SaleInvoice::with('watch')->where('customer_token', $token)->firstOrFail();
        abort_unless($invoice->watch?->status === 'sold'
            && $invoice->watch->sale_cycle === $invoice->sale_cycle, 404);

        return $invoice;
    }
}
