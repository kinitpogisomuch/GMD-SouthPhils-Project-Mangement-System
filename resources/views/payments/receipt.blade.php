<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acknowledgement Receipt {{ $transaction->receiptNumber() }} | GMD South Phils</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/receipt.css') }}?v={{ @filemtime(public_path('css/receipt.css')) }}">
</head>
<body class="pg-payments-receipt{{ request()->boolean('embed') ? ' pg-payments-receipt-embed' : '' }}">
@php
    $amount     = (float) $transaction->amount_paid;
    $stageLabel = \App\Models\PaymentTransaction::stageLabel($transaction->payment_stage);
    $stageAmts  = $payment->stageAmounts();
    $stagePct   = $payment->contract_amount > 0 ? round((($stageAmts[$transaction->payment_stage] ?? 0) / $payment->contract_amount) * 100) : null;
    // "full" when this payment settles the whole contract, otherwise "partial"
    $paidSoFar  = (float) $payment->transactions->where('id', '<=', $transaction->id)->sum('amount_paid');
    $isFull     = $paidSoFar + 0.01 >= (float) $payment->contract_amount;
    $paidOn     = $online?->paid_at ?? $transaction->payment_date;
    $isOnline   = $transaction->isOnline();
    $method     = $online ? $online->methodLabel() : ($isOnline ? 'Online' : '');
    $reference  = $online?->provider_payment_id ?: $transaction->reference_number;
    $signatory  = $payment->billingStatements()->whereNotNull('approved_by_name')->latest('id')->value('approved_by_name') ?: 'Vince Duetiz';
    $clientName = $payment->project?->live_client_name ?? $payment->client;
@endphp

    {{-- ?embed=1: shown inside the in-page file viewer — just the receipt, the viewer has its own close button --}}
    @php $embedded = request()->boolean('embed'); @endphp
    {{-- embedded styling: body.pg-payments-receipt-embed in billing_statement.css --}}
    @unless($embedded)
    <div class="ar-toolbar">
        <a href="{{ $backUrl }}">← Back to Payment</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>
    @endunless

    <div class="ar-sheet">
        {{-- In settlement of the following --}}
        <div class="ar-settle">
            <div class="ar-cap">In Settlement of the following:</div>
            <table>
                <tr><th>Particulars</th><th style="width:44%;">Amount</th></tr>
                <tr>
                    <td class="ar-hand">{{ $stageLabel }}@if($stagePct) ({{ $stagePct }}%)@endif</td>
                    <td class="ar-amt">{{ number_format($amount, 2) }}</td>
                </tr>
                <tr><td class="ar-hand" colspan="2">{{ $payment->project->name ?? '' }}</td></tr>
                <tr><td></td><td></td></tr>
                <tr><td class="ar-small">Total Sales</td><td class="ar-amt">{{ number_format($amount, 2) }}</td></tr>
                <tr><td class="ar-small">Less: SC/PWD Discount</td><td></td></tr>
                <tr><td class="ar-small">Total Due</td><td class="ar-amt">{{ number_format($amount, 2) }}</td></tr>
                <tr><td class="ar-small">Less: Witholding Tax</td><td></td></tr>
                <tr><td class="ar-strong">PAYMENT DUE</td><td class="ar-amt">{{ number_format($amount, 2) }}</td></tr>
                <tr><td colspan="2" class="ar-section">FORM OF PAYMENT</td></tr>
                <tr>
                    <td colspan="2" style="font-size:11.5px;">
                        Cash<span class="ar-box {{ $transaction->mode_of_payment === 'cash' ? 'is-checked' : '' }}"></span>
                        &nbsp;&nbsp; Check<span class="ar-box {{ $transaction->mode_of_payment === 'cheque' ? 'is-checked' : '' }}"></span>
                        &nbsp;&nbsp; Online<span class="ar-box {{ $isOnline ? 'is-checked' : '' }}"></span>
                    </td>
                </tr>
                <tr><td class="ar-small">Ref. #</td><td class="ar-hand" style="font-size:10.5px;word-break:break-all;">{{ $reference }}</td></tr>
                <tr><td class="ar-small">Bank</td><td class="ar-hand">{{ $method }}</td></tr>
                <tr><td class="ar-small">Date</td><td class="ar-hand">{{ \Carbon\Carbon::parse($paidOn)->format('m/d/Y') }}</td></tr>
                <tr><td class="ar-small">Amount</td><td class="ar-amt">{{ number_format($amount, 2) }}</td></tr>
            </table>
        </div>

        {{-- The acknowledgement --}}
        <div class="ar-main">
            <div class="ar-company">
                <h1>GMD South Phils Metal<br>Fabrication Works</h1>
                <p>Bondad Compound, National Hi-Way, Brgy. Masiit, Calauan, Laguna</p>
            </div>

            <div class="ar-headrow">
                <div class="ar-title">ACKNOWLEDGEMENT RECEIPT</div>
                <div class="ar-line" style="margin-top:0;">
                    <span class="ar-label">Date:</span>
                    <span class="ar-fill">{{ \Carbon\Carbon::parse($paidOn)->format('m/d/Y') }}</span>
                </div>
            </div>

            <div class="ar-line ar-indent">
                <span class="ar-label">Received from</span>
                <span class="ar-fill">{{ $clientName }}</span>
            </div>
            <div class="ar-line">
                <span class="ar-label">the amount of</span>
                <span class="ar-fill">{{ \App\Support\AmountInWords::pesos($amount) }}</span>
            </div>
            <div class="ar-line">
                <span class="ar-label">( P</span>
                <span class="ar-fill ar-amount-box">{{ number_format($amount, 2) }}</span>
                <span class="ar-label ar-pf">) representing the <span class="ar-pf-word">{{ $isFull ? 'full' : 'partial' }}</span> payment for :</span>
            </div>
            <div class="ar-line">
                <span class="ar-fill">{{ $stageLabel }} — {{ $payment->project->name ?? 'Project' }}</span>
            </div>

            <div class="ar-foot">
                <div class="ar-no">No.<span class="{{ $isOnline ? '' : 'is-or' }}">{{ $transaction->receiptNumber() }}</span></div>
                <div class="ar-sign">
                    <div class="ar-sign-name">{{ $signatory }}</div>
                    <div class="ar-sign-line">Signature over printed name</div>
                </div>
            </div>

            <div class="ar-disclaimer">“NOT VALID TO CLAIM INPUT TAXES”</div>
            <div class="ar-system">
                @if($isOnline)
                System-generated receipt — this payment was made online via PayMongo ({{ $method }}) and verified with the bank automatically.
                @if($reference) Payment ref: {{ $reference }}.@endif
                @else
                System-generated receipt — this payment was received and recorded by GMD South Phils.
                @endif
            </div>
        </div>
    </div>
</body>
</html>
