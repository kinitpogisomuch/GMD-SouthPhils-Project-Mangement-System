<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $doc['quotation_number'] }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('partials.quotation_document', ['doc' => $doc, 'forPdf' => true])
</body>
</html>
