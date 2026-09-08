<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Test Report | GMD South Phils</title>
    <link href="{{ asset('css/billing_statement.css') }}" rel="stylesheet">
</head>
<body>

    <div class="bs-toolbar">
        <a href="{{ route('admin.project_view', $project->id) }}">
            <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
            Back to Project
        </a>
        <button type="button" onclick="window.print()">
            <i data-lucide="printer" style="width:15px;height:15px;"></i>
            Print / Save as PDF
        </button>
    </div>

    @include('partials.performance_test_report_document', compact('project', 'report'))

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
