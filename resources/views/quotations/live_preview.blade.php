{{-- The Quotation Document step's live preview: just the document sheet, shown inside the preview frame
     beside the form. Built from the details as typed (QuotationRequestController::livePreview). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/billing_statement.css') }}?v={{ @filemtime(public_path('css/billing_statement.css')) }}" rel="stylesheet">
</head>
<body class="pg-quotations-live-preview">
    <div class="lp-sheet">
        @include('partials.quotation_document', ['doc' => $doc])
    </div>
</body>
</html>
