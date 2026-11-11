@extends('receipt-layout')

@section('receipt-title', 'Watch sale invoice')
@section('receipt-heading', 'Watch sale invoice')

@section('receipt-details')
    <section aria-label="Sale details">
        <p><strong>Sale date:</strong> {{ date('M j, Y', strtotime($watch['date_sold'])) }}</p>
        @if (filled($watch['buyer_name']))
            <p><strong>Customer:</strong> {{ $watch['buyer_name'] }}</p>
        @endif
        <p><strong>Watch:</strong> {{ trim($watch['brand'].' '.$watch['model_name']) }}</p>
        @if (filled($watch['reference_number']))
            <p><strong>Watch reference:</strong> {{ $watch['reference_number'] }}</p>
        @endif
        @if (filled($watch['serial_number']))
            <p><strong>Serial number:</strong> {{ $watch['serial_number'] }}</p>
        @endif
    </section>
    <section aria-label="Payment">
        <dl>
            <div><dt>Amount paid</dt><dd>₱{{ number_format((float) $watch['sold_price'], 2) }}</dd></div>
        </dl>
    </section>
    <p class="muted">Acknowledgment of the recorded watch sale.</p>
    @isset($customerUrl)
        <section class="print-action" aria-label="Customer purchase link">
            <label for="purchase-link"><strong>Private customer link</strong></label>
            <p>Share this link with the buyer for their invoice and warranty details.</p>
            <input id="purchase-link" type="url" value="{{ $customerUrl }}" readonly style="width: 100%; padding: 8px">
            <p><a href="{{ $customerUrl }}">Open customer page</a></p>
        </section>
    @endisset
@endsection
