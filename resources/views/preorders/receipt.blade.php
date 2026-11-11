<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment receipt {{ $reference }} | Montre Nova</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #202020; background: #f5f4f0; font: 15px/1.5 system-ui, sans-serif; }
        main { max-width: 740px; margin: 24px auto; padding: clamp(16px, 5vw, 40px); background: white; }
        header { display: flex; justify-content: space-between; align-items: start; gap: 16px; flex-wrap: wrap; }
        h1 { margin: 4px 0; font-size: 26px; }
        h2 { font-size: 18px; }
        p { margin: 4px 0; overflow-wrap: anywhere; }
        .brand { text-transform: uppercase; letter-spacing: .15em; font-weight: 700; }
        .muted { color: #515151; }
        section { border-top: 1px solid #d5d5d5; margin-top: 20px; padding-top: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th, td { padding: 8px 4px; text-align: left; border-bottom: 1px solid #ddd; overflow-wrap: anywhere; }
        th:last-child, td:last-child { text-align: right; }
        td:last-child { white-space: nowrap; }
        dl div { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; }
        dd { margin: 0; font-weight: 600; text-align: right; }
        button { font: inherit; padding: 10px 14px; border: 1px solid #222; border-radius: 6px; background: #222; color: white; cursor: pointer; }
        @media print {
            body { background: white; }
            main { max-width: none; margin: 0; padding: 0; }
            .print-action { display: none; }
            tr, dl div { break-inside: avoid; }
        }
        @page { margin: 15mm; }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <p class="brand">Montre Nova</p>
            <h1>Preorder payment receipt</h1>
            <p class="muted">{{ $reference }}</p>
        </div>
        <button type="button" class="print-action" onclick="window.print()">Print / Save as PDF</button>
    </header>
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
</main>
</body>
</html>
