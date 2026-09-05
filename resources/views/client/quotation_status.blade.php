<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Requests | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

        <div class="page-header">
            <div>
                <h1 class="page-title">Your Quotation Requests</h1>
                <p class="page-subtitle">Each tank you requested is reviewed and quoted independently.</p>
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

        @php
            $statusMeta = [
                'pending'        => ['label' => 'Under Review', 'icon' => 'clock', 'bg' => '#FFF3D6', 'color' => '#8A6100'],
                'quotation_sent' => ['label' => 'Quotation Ready for Review', 'icon' => 'file-text', 'bg' => '#EAF0FF', 'color' => '#1e40af'],
                'approved'       => ['label' => 'Quotation Approved', 'icon' => 'thumbs-up', 'bg' => '#dcfce7', 'color' => '#16a34a'],
                'converted'      => ['label' => 'Accepted — Project Created', 'icon' => 'check-circle-2', 'bg' => '#dcfce7', 'color' => '#16a34a'],
                'declined'       => ['label' => 'Not Approved', 'icon' => 'x-circle', 'bg' => '#fee2e2', 'color' => '#dc2626'],
            ];
        @endphp

        <div style="display:flex;flex-direction:column;gap:24px;max-width:820px;margin:0 auto;">
        @foreach($requests as $batch)
            @php $first = $batch->first(); @endphp
            <div class="pv-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                    <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);font-weight:700;">
                        <i data-lucide="calendar" style="width:14px;height:14px;"></i>
                        Submitted {{ $first->created_at->format('M d, Y \a\t g:i A') }}
                    </div>
                    <span style="background:var(--cream-soft);border:1px solid var(--border);border-radius:20px;padding:4px 13px;font-size:11.5px;font-weight:800;color:var(--dark);text-transform:uppercase;letter-spacing:.04em;">
                        {{ $batch->count() }} {{ Str::plural('tank', $batch->count()) }} requested
                    </span>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach($batch as $qr)
                    @php $meta = $statusMeta[$qr->status] ?? $statusMeta['pending']; @endphp
                    <div style="border:1px solid var(--border);border-left:4px solid {{ $meta['color'] }};border-radius:14px;padding:14px 18px;background:var(--surface-2);">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:38px;height:38px;border-radius:50%;background:{{ $meta['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i data-lucide="{{ $meta['icon'] }}" style="width:18px;height:18px;color:{{ $meta['color'] }};"></i>
                                </div>
                                <div>
                                    <div class="qr-tank-row" style="margin-top:0;">
                                        <span class="qr-spec-chip qr-chip-type">
                                            <i data-lucide="package" style="width:11px;height:11px;"></i>
                                            {{ $qr->tank_type ?? '—' }}{{ $qr->quantity > 1 ? ' ×' . $qr->quantity : '' }}
                                        </span>
                                        @if(!empty($qr->capacity))
                                        <span class="qr-spec-chip qr-chip-capacity">
                                            <i data-lucide="droplet" style="width:11px;height:11px;"></i>
                                            Capacity: {{ $qr->capacity }}
                                        </span>
                                        @endif
                                        @if(!empty($qr->target_timeline))
                                        <span class="qr-spec-chip qr-chip-timeline">
                                            <i data-lucide="clock" style="width:11px;height:11px;"></i>
                                            Timeline: {{ $qr->target_timeline }}
                                        </span>
                                        @endif
                                    </div>
                                    <div style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:800;color:{{ $meta['color'] }};background:{{ $meta['bg'] }};border-radius:8px;padding:3px 9px;margin-top:8px;text-transform:uppercase;letter-spacing:.03em;">
                                        {{ $meta['label'] }}
                                    </div>
                                </div>
                            </div>

                            @if($qr->status === 'quotation_sent')
                            <form method="POST" action="{{ route('client.quotation.approve', $qr->id) }}">
                                @csrf
                                <button type="submit" class="save-btn">
                                    <i data-lucide="thumbs-up"></i>
                                    Approve
                                </button>
                            </form>
                            @endif
                        </div>

                        @if(!empty($qr->quotation_files))
                        <div style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--border);display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach($qr->quotation_files as $i => $file)
                            <a href="{{ $file }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--accent);text-decoration:none;background:#fff;border:1px solid var(--border);border-radius:20px;padding:6px 13px;">
                                <i data-lucide="file-text" style="width:12px;height:12px;"></i>
                                Quotation File {{ $i + 1 }}
                            </a>
                            @endforeach
                        </div>
                        @endif

                        @if($qr->status === 'declined' && $qr->decline_reason)
                        <div style="margin-top:12px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:12.5px;color:#dc2626;display:flex;align-items:flex-start;gap:8px;">
                            <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;"></i>
                            <span><strong>Reason:</strong> {{ $qr->decline_reason }}</span>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="form-grid" style="margin-top:18px;">
                    <div class="form-group form-group-full">
                        <label>Project / Delivery Location</label>
                        <textarea disabled rows="2">{{ $first->location }}</textarea>
                    </div>
                    @if($first->notes)
                    <div class="form-group form-group-full">
                        <label>Additional Notes</label>
                        <textarea disabled rows="2">{{ $first->notes }}</textarea>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
        </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
