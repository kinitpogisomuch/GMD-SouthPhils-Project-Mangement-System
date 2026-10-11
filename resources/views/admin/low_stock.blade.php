<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <style>
        .ls-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
        .ls-stat { background: var(--white); border: 1px solid var(--border); border-radius: 18px; padding: 16px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 8px 20px var(--shadow); }
        .ls-stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ls-stat-icon svg { width: 20px; height: 20px; }
        .ls-stat strong { display: block; font-size: 24px; font-weight: 900; color: var(--dark); line-height: 1.1; }
        .ls-stat span { font-size: 12px; font-weight: 700; color: var(--muted); }

        .ls-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
        .ls-pill svg { width: 12px; height: 12px; }
        .ls-pill.out { background: #FEE4E2; color: #B42318; }
        .ls-pill.low { background: #FEF3C7; color: #92400E; }

        .ls-bar { height: 6px; border-radius: 999px; background: var(--cream-deep); overflow: hidden; margin-top: 6px; min-width: 90px; }
        .ls-bar span { display: block; height: 100%; border-radius: 999px; }
        .ls-qty { font-weight: 800; color: var(--dark); white-space: nowrap; }
        .ls-sub { font-size: 11.5px; color: var(--muted); }

        .ls-empty { text-align: center; padding: 56px 20px; }
        .ls-empty-icon { width: 64px; height: 64px; border-radius: 50%; background: #E7F6EC; color: #207A3A; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .ls-empty-icon svg { width: 30px; height: 30px; }
        .ls-empty h3 { font-size: 17px; font-weight: 900; color: var(--dark); margin: 0 0 6px; }
        .ls-empty p { font-size: 13px; color: var(--muted); margin: 0; }

        @media (max-width: 760px) {
            .ls-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Low Stock Alert</h1>
                    <p class="page-subtitle">
                        Materials on in-progress projects with {{ (int) (\App\Services\LowStockService::LOW_THRESHOLD * 100) }}% or less of their purchased stock left. This list updates automatically as materials are used or restocked.
                    </p>
                </div>
            </div>

            <div class="ls-stats">
                <div class="ls-stat">
                    <div class="ls-stat-icon" style="background:#FEF3C7;color:#92400E;"><i data-lucide="package-search"></i></div>
                    <div><strong>{{ $items->count() }}</strong><span>Need attention</span></div>
                </div>
                <div class="ls-stat">
                    <div class="ls-stat-icon" style="background:#FEE4E2;color:#B42318;"><i data-lucide="package-x"></i></div>
                    <div><strong>{{ $outCount }}</strong><span>Out of stock</span></div>
                </div>
                <div class="ls-stat">
                    <div class="ls-stat-icon" style="background:#FEF3C7;color:#B45309;"><i data-lucide="alert-triangle"></i></div>
                    <div><strong>{{ $lowCount }}</strong><span>Low stock</span></div>
                </div>
            </div>

            <div class="table-card">
                @if($items->isEmpty())
                    <div class="ls-empty">
                        <div class="ls-empty-icon"><i data-lucide="package-check"></i></div>
                        <h3>All materials are sufficiently stocked</h3>
                        <p>No material on an in-progress project is low or out of stock right now.</p>
                    </div>
                @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Project</th>
                                <th style="text-align:right;">Current Stock</th>
                                <th style="text-align:right;">Minimum Level</th>
                                <th style="text-align:right;">Purchased / Used</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            @php
                                $fmt   = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
                                $unit  = $item['unit'] ? ' ' . $item['unit'] : '';
                                $share = $item['purchased'] > 0 ? max(0, min(100, $item['remaining'] / $item['purchased'] * 100)) : 0;
                                $isOut = $item['status'] === 'out';
                            @endphp
                            <tr>
                                <td><strong style="color:var(--dark);">{{ $item['material_name'] }}</strong></td>
                                <td><span class="ls-sub">{{ $item['project_name'] }}</span></td>
                                <td style="text-align:right;">
                                    <span class="ls-qty" style="color:{{ $isOut ? '#B42318' : '#B45309' }};">{{ $fmt($item['remaining']) }}{{ $unit }}</span>
                                    <div class="ls-bar"><span style="width:{{ $share }}%;background:{{ $isOut ? '#B42318' : '#F59E0B' }};"></span></div>
                                </td>
                                <td style="text-align:right;"><span class="ls-qty">{{ $fmt($item['minimum']) }}{{ $unit }}</span><div class="ls-sub">25% of purchased</div></td>
                                <td style="text-align:right;"><span class="ls-sub">{{ $fmt($item['purchased']) }} / {{ $fmt($item['used']) }}{{ $unit }}</span></td>
                                <td style="text-align:center;">
                                    @if($isOut)
                                    <span class="ls-pill out"><i data-lucide="package-x"></i>Out of Stock</span>
                                    @else
                                    <span class="ls-pill low"><i data-lucide="alert-triangle"></i>Low Stock</span>
                                    @endif
                                </td>
                                <td class="action-cell" style="text-align:center;">
                                    <a class="action-btn view" href="{{ route('admin.material_usage.detail', $item['project_id']) }}" title="Open this project's materials">
                                        <i data-lucide="arrow-up-right"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>
