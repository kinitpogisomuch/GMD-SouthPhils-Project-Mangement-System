<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Statement | GMD South Phils</title>
    <link href="{{ asset('css/billing_statement.css') }}" rel="stylesheet">
</head>
<body>

    {{-- ?embed=1: shown inside the in-page file viewer — just the statement, the viewer has its own close button --}}
    @if(request()->boolean('embed'))
    <style>
        body { background: #fff; }
        .bs-sheet { margin-top: 0; box-shadow: none; }
    </style>
    @else
    <div class="bs-toolbar">
        <a href="{{ route('client.payments.show', $payment->id) }}">
            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
            Back to Payment
        </a>
        <button type="button" onclick="window.print()">
            <i data-lucide="printer" style="width:15px;height:15px;"></i>
            Print / Save as PDF
        </button>
    </div>
    @endif

    @include('partials.billing_statement_document', compact('payment', 'statement'))

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
