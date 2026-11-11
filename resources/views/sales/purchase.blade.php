@extends('receipt-layout')

@section('receipt-title', 'Your purchase')
@section('receipt-heading', 'Your purchase')
@section('receipt-action', '')

@section('receipt-details')
    <section aria-label="Purchase details">
        <h2>{{ trim($watch['brand'].' '.$watch['model_name']) }}</h2>
        <p><a href="{{ $invoiceUrl }}">View and print your invoice</a></p>
    </section>
    <section aria-label="Warranty and service">
        <h2>Warranty and service</h2>
        @if ($warrantyExpiresOn)
            <p><strong>Warranty expires:</strong> {{ date('M j, Y', strtotime($warrantyExpiresOn)) }}</p>
        @endif
        <p>To request service, send us your invoice number and a description of the issue.</p>
        <p><a href="https://m.me/montrenova" rel="noreferrer">Contact Montre Nova on Messenger</a></p>
    </section>
@endsection
