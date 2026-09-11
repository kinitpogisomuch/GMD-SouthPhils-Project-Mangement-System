<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation History | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

        <div class="page-header" style="max-width:820px;margin:0 auto 24px;justify-content:center;text-align:center;">
            <div>
                <h1 class="page-title">Quotation History</h1>
                <p class="page-subtitle">Completed quotation requests — accepted or declined.</p>
            </div>
        </div>

        @if(session('success'))
        <div class="alert-banner success">
            <i data-lucide="check-circle"></i>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert-banner" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;">
            <i data-lucide="circle-alert"></i>
            {{ session('error') }}
        </div>
        @endif

        <div style="display:flex;flex-direction:column;gap:24px;max-width:820px;margin:0 auto;">
        @forelse($requests as $batch)
            @include('partials.client.quotation_batch_card', ['batch' => $batch])
        @empty
            <div class="pv-card" style="text-align:center;padding:48px 20px;">
                <i data-lucide="history" style="width:36px;height:36px;color:var(--muted);opacity:.5;display:block;margin:0 auto 12px;"></i>
                <p style="font-weight:800;color:var(--dark);margin-bottom:6px;">No completed quotations yet.</p>
                <p style="font-size:13px;color:var(--muted);">Accepted or declined requests will show up here once a decision has been made.</p>
            </div>
        @endforelse
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
