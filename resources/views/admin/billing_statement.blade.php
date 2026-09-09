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

    @if(session('success'))
    <div style="max-width:850px;margin:16px auto 0;background:#dcfce7;border:1px solid #86efac;color:#15803d;border-radius:10px;padding:12px 18px;font-size:13.5px;font-weight:700;display:flex;align-items:center;gap:8px;">
        <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
        {{ session('success') }}
    </div>
    @endif

    <div class="bs-toolbar">
        <a href="{{ route('admin.payments.show', $payment->id) }}">
            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
            Back to Payment
        </a>
        <div style="display:flex;align-items:center;gap:12px;">
            <button type="button" onclick="window.print()">
                <i data-lucide="printer" style="width:15px;height:15px;"></i>
                Print / Save as PDF
            </button>
            @if($statement->sent_at)
            <span style="display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:700;font-size:13px;">
                <i data-lucide="check-circle-2" style="width:15px;height:15px;"></i>
                Sent {{ $statement->sent_at->format('M d, Y g:i A') }}
            </span>
            @else
            <form method="POST" action="{{ route('admin.payments.billing_statements.send', [$payment->id, $statement->id]) }}"
                  onsubmit="return confirm('Send this billing statement to the client?');" style="display:inline;">
                @csrf
                <button type="submit">
                    <i data-lucide="send" style="width:15px;height:15px;"></i>
                    Send to Client
                </button>
            </form>
            @endif
        </div>
    </div>

    @include('partials.billing_statement_document', compact('payment', 'statement'))

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
