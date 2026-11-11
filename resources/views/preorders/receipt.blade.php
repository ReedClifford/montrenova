@extends('receipt-layout')

@section('receipt-title', 'Preorder Sales Invoice')
@section('receipt-heading', 'Preorder Sales Invoice')

@section('receipt-details')
    <section aria-label="Order details">
        <p><strong>Customer:</strong> {{ $order->customer_name }}</p>
        <p><strong>Watch:</strong> {{ trim(($watch['brand'] ?? '').' '.($watch['model_name'] ?? '')) }}</p>
        @if (!empty($watch['reference_number']))
            <p><strong>Watch reference:</strong> {{ $watch['reference_number'] }}</p>
        @endif
        <p><strong>Order status:</strong> {{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
    </section>
    <section aria-labelledby="payments">
        <h2 id="payments">Payments and refunds</h2>
        <table>
            <thead>
                <tr><th scope="col">Date</th><th scope="col">Type</th><th scope="col">Method</th><th scope="col">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($transactions as $entry)
                    <tr>
                        <td>{{ $entry->paid_at?->format('M j, Y') }}</td>
                        <td>{{ ucfirst($entry->type) }}</td>
                        <td>{{ $entry->method }}</td>
                        <td>{{ $entry->type === 'refund' ? '−' : '' }}₱{{ number_format((float) $entry->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <dl>
            <div><dt>Agreed price</dt><dd>₱{{ number_format((float) $order->total_price, 2) }}</dd></div>
            <div><dt>Payments less refunds</dt><dd>₱{{ number_format($order->paid, 2) }}</dd></div>
            @if ($order->status === 'cancelled')
                <div><dt>Refund pending</dt><dd>₱{{ number_format($order->refundable, 2) }}</dd></div>
            @else
                <div><dt>Remaining balance</dt><dd>₱{{ number_format($order->balance, 2) }}</dd></div>
            @endif
        </dl>
    </section>
    <p class="muted">Acknowledgment of payments recorded for this preorder.</p>
@endsection
