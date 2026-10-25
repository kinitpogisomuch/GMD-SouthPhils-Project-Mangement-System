<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acknowledgement Receipt {{ $transaction->receiptNumber() }} | GMD South Phils</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9e9e9; font-family: 'Inter', Arial, sans-serif; color: #1a1a1a; }

        .ar-toolbar { max-width: 980px; margin: 0 auto; padding: 18px 16px 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .ar-toolbar a, .ar-toolbar button { display: inline-flex; align-items: center; gap: 8px; font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .ar-toolbar a { color: #444; }
        .ar-toolbar button { border: 0; border-radius: 12px; padding: 11px 18px; background: #1a1a1a; color: #fff; }
        /* the receipt sheet (landscape, like the printed booklet) */
        .ar-sheet { max-width: 980px; margin: 0 auto 40px; background: #fff; padding: 30px 34px; display: grid; grid-template-columns: 290px 1fr; gap: 34px; box-shadow: 0 6px 30px rgba(0,0,0,.08); }

        /* left: In Settlement of the following */
        .ar-settle { border: 1.5px solid #222; font-size: 11.5px; align-self: start; }
        .ar-settle table { width: 100%; border-collapse: collapse; }
        .ar-settle td, .ar-settle th { border: 1px solid #222; padding: 6px 8px; height: 30px; vertical-align: middle; }
        .ar-settle th { font-weight: 600; text-align: center; }
        .ar-settle .ar-cap { border: 0; border-bottom: 1px solid #222; padding: 6px 8px; font-weight: 600; }
        .ar-settle .ar-small { font-size: 10px; font-weight: 600; }
        .ar-settle .ar-strong { font-size: 12.5px; font-weight: 800; }
        .ar-settle .ar-amt { text-align: right; font-size: 12px; font-weight: 700; color: #111; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .ar-settle .ar-hand { font-size: 11.5px; font-weight: 600; color: #111; line-height: 1.3; }
        .ar-settle .ar-section { text-align: center; font-weight: 800; font-size: 12.5px; }
        .ar-box { display: inline-block; width: 18px; height: 13px; border: 1px solid #222; vertical-align: middle; margin-left: 6px; position: relative; }
        .ar-box.is-checked::after { content: "✓"; position: absolute; left: 3px; top: -7px; font-size: 16px; font-weight: 800; color: #111; }

        /* right: the acknowledgement */
        .ar-main { display: flex; flex-direction: column; }
        .ar-company { text-align: center; }
        .ar-company h1 { margin: 0; font-family: 'Oswald', 'Arial Narrow', sans-serif; font-weight: 700; font-size: 40px; line-height: 1.02; letter-spacing: -.5px; text-transform: uppercase; }
        .ar-company p { margin: 6px 0 0; font-size: 13px; }
        .ar-headrow { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-top: 34px; }
        .ar-title { font-weight: 800; font-size: 17px; letter-spacing: .01em; }
        .ar-line { display: flex; align-items: flex-end; gap: 10px; font-size: 15.5px; margin-top: 26px; }
        .ar-line .ar-label { white-space: nowrap; padding-bottom: 3px; }
        .ar-fill { flex: 1; min-width: 0; border-bottom: 1.5px solid #222; padding: 0 10px 4px; font-size: 15.5px; font-weight: 600; color: #111; line-height: 1.35; }
        .ar-headrow .ar-fill { min-width: 200px; }
        .ar-amount-box { flex: 0 0 240px; text-align: center; font-size: 19px; font-weight: 800; }
        .ar-pf-word { text-decoration: underline; text-decoration-thickness: 1.5px; text-underline-offset: 3px; font-weight: 700; }
        .ar-indent { padding-left: 80px; }
        .ar-foot { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; margin-top: 34px; }
        .ar-no { font-weight: 800; font-size: 17px; }
        .ar-no span { color: #c1121f; font-family: 'Courier New', monospace; font-size: 26px; letter-spacing: 2px; margin-left: 8px; }
        .ar-sign { width: 300px; text-align: center; }
        .ar-sign-name { font-size: 15px; font-weight: 800; color: #111; text-transform: uppercase; letter-spacing: .04em; line-height: 1.2; }
        .ar-sign-line { border-top: 1.5px solid #222; margin-top: 4px; padding-top: 5px; font-size: 14px; }
        .ar-disclaimer { text-align: center; font-weight: 800; font-size: 12px; margin-top: 18px; }
        .ar-system { margin-top: 12px; padding: 9px 12px; border-radius: 8px; background: #f3f6ff; border: 1px solid #dbe4fb; font-size: 11.5px; color: #2a4eaa; text-align: center; line-height: 1.5; }

        @media (max-width: 760px) {
            .ar-sheet { grid-template-columns: minmax(0, 1fr); padding: 22px 16px; gap: 24px; }
            .ar-settle { order: 2; }
            .ar-company h1 { font-size: 28px; }
            .ar-headrow, .ar-foot { flex-wrap: wrap; }
            .ar-line { flex-wrap: wrap; font-size: 14px; }
            .ar-amount-box { flex: 1 1 100%; }
            .ar-indent { padding-left: 0; }
            .ar-sign { width: 100%; }
        }

        @media print {
            @page { size: landscape; margin: 10mm; }
            body { background: #fff; }
            .ar-toolbar { display: none; }
            .ar-sheet { box-shadow: none; margin: 0; max-width: none; }
            .ar-system { background: none; }
        }
    </style>
</head>
<body>
@php
    $amount     = (float) $transaction->amount_paid;
    $stageLabel = \App\Models\PaymentTransaction::stageLabel($transaction->payment_stage);
    $stageAmts  = $payment->stageAmounts();
    $stagePct   = $payment->contract_amount > 0 ? round((($stageAmts[$transaction->payment_stage] ?? 0) / $payment->contract_amount) * 100) : null;
    // "full" when this payment settles the whole contract, otherwise "partial"
    $paidSoFar  = (float) $payment->transactions->where('id', '<=', $transaction->id)->sum('amount_paid');
    $isFull     = $paidSoFar + 0.01 >= (float) $payment->contract_amount;
    $paidOn     = $online?->paid_at ?? $transaction->payment_date;
    $method     = $online ? $online->methodLabel() : 'Online';
    $reference  = $online?->provider_payment_id ?: $transaction->reference_number;
    $signatory  = $payment->billingStatements()->whereNotNull('approved_by_name')->latest('id')->value('approved_by_name') ?: 'Vince Duetiz';
    $clientName = $payment->project?->live_client_name ?? $payment->client;
@endphp

    {{-- ?embed=1: shown inside the in-page file viewer — just the receipt, the viewer has its own close button --}}
    @php $embedded = request()->boolean('embed'); @endphp
    @if($embedded)
    <style>
        body { background: #fff; }
        .ar-sheet { margin: 0 auto; box-shadow: none; }
    </style>
    @else
    <div class="ar-toolbar">
        <a href="{{ $backUrl }}">← Back to Payment</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>
    @endif

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
                        Cash<span class="ar-box"></span>
                        &nbsp;&nbsp; Check<span class="ar-box"></span>
                        &nbsp;&nbsp; Online<span class="ar-box is-checked"></span>
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
                <div class="ar-no">No.<span>{{ $transaction->receiptNumber() }}</span></div>
                <div class="ar-sign">
                    <div class="ar-sign-name">{{ $signatory }}</div>
                    <div class="ar-sign-line">Signature over printed name</div>
                </div>
            </div>

            <div class="ar-disclaimer">“NOT VALID TO CLAIM INPUT TAXES”</div>
            <div class="ar-system">
                System-generated receipt — this payment was made online via PayMongo ({{ $method }}) and verified with the bank automatically.
                @if($reference) Payment ref: {{ $reference }}.@endif
            </div>
        </div>
    </div>
</body>
</html>
