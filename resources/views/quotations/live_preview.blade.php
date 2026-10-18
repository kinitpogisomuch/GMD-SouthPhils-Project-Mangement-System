{{-- The Quotation Document step's live preview: just the document sheet, shown inside the preview frame
     beside the form. Built from the details as typed (QuotationRequestController::livePreview). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/billing_statement.css') }}" rel="stylesheet">
    <style>
        html, body { margin: 0; background: #eef0f3; }
        .lp-sheet { background: #fff; margin: 18px auto; padding: 30px 36px; max-width: 820px; box-shadow: 0 6px 24px rgba(0,0,0,.10); }
        @media (max-width: 640px) { .lp-sheet { margin: 0; padding: 18px; } }
    </style>
</head>
<body>
    <div class="lp-sheet">
        @include('partials.quotation_document', ['doc' => $doc])
    </div>
</body>
</html>
