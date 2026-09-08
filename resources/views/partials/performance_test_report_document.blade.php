<div class="bs-sheet">
    <!-- Letterhead -->
    <div class="bs-letterhead">
        <div class="bs-logo-group">
            <img src="{{ asset('images/logo-left.png') }}" alt="GMD South Phils" class="bs-logo">
            <img src="{{ asset('images/logo-right.png') }}" alt="" class="bs-logo">
            @if(file_exists(public_path('images/logo-best.png')))
            <img src="{{ asset('images/logo-best.png') }}" alt="" class="bs-logo bs-logo-badge">
            @endif
        </div>
        <div class="bs-company-info">
            <div class="bs-company-name">GMD South Phils Metal Fabrication Works</div>
            <div>National Hi-way, Brgy. Masiit, Calauan, Laguna</div>
            <div>TIN CERTIFICATE REG. TIN # 279-809-827-000</div>
            <div>DTI REGISTRATION CERTIFICATE NO. 1019791</div>
            <div>BUSINESS ID. NO,. 19-07-066</div>
        </div>
    </div>

    <!-- Fields -->
    <table class="ptr-fields">
        <tr>
            <td class="ptr-field-label">CLIENT NAME:</td>
            <td class="ptr-field-value">{{ strtoupper($report->client_name ?: '—') }}</td>
        </tr>
        <tr>
            <td class="ptr-field-label">PROJECT LOCATION:</td>
            <td class="ptr-field-value">{{ strtoupper($report->project_location ?: '—') }}</td>
        </tr>
        <tr>
            <td class="ptr-field-label">SUBJECT:</td>
            <td class="ptr-field-value">{{ strtoupper($report->subject ?: '—') }}</td>
        </tr>
        <tr>
            <td class="ptr-field-label">DATE:</td>
            <td class="ptr-field-value">{{ strtoupper($report->report_date->format('F j, Y')) }}</td>
        </tr>
    </table>

    <!-- Title bar -->
    <div class="ptr-title-bar">PERFORMANCE TEST REPORT</div>

    <!-- Test items table -->
    <table class="ptr-table">
        <thead>
            <tr>
                <th style="width:10%;">ITEM</th>
                <th style="width:14%;">TANK CAPACITY</th>
                <th style="width:16%;">APPLIED PRESSURE (PSIG)</th>
                <th style="width:22%;">DATE/TIME OF TESTING</th>
                <th style="width:16%;">NO. OF HOURS OBSERVED</th>
                <th>REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report->test_items as $item)
            <tr>
                <td>{{ $item['item'] ?? '—' }}</td>
                <td>{{ $item['tank_capacity'] ?? '—' }}</td>
                <td>{{ $item['applied_pressure'] ?? '—' }}</td>
                <td>{{ $item['tested_at'] ?? '—' }}</td>
                <td>{{ $item['hours_observed'] ?? '—' }}</td>
                <td>{{ $item['remarks'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="ptr-signatures">
        <div class="ptr-sig-block">
            <div style="font-weight:700;margin-bottom:26px;">TEST CONDUCTED BY:</div>
            <div class="ptr-sig-name">{{ $report->conducted_by_name ?: '—' }}</div>
            <div class="ptr-sig-role">{{ $report->conducted_by_role ?: '—' }}</div>
        </div>
        <div class="ptr-sig-block">
            <div style="font-weight:700;margin-bottom:26px;">Noted by:</div>
            <div class="ptr-sig-name">{{ $report->noted_by_name ?: '—' }}</div>
            <div class="ptr-sig-role">{{ $report->noted_by_role ?: '—' }}</div>
        </div>
    </div>
</div>

@if(!empty($report->test_photos))
<div class="bs-sheet ptr-photo-page">
    <!-- Letterhead -->
    <div class="bs-letterhead">
        <div class="bs-logo-group">
            <img src="{{ asset('images/logo-left.png') }}" alt="GMD South Phils" class="bs-logo">
            <img src="{{ asset('images/logo-right.png') }}" alt="" class="bs-logo">
            @if(file_exists(public_path('images/logo-best.png')))
            <img src="{{ asset('images/logo-best.png') }}" alt="" class="bs-logo bs-logo-badge">
            @endif
        </div>
        <div class="bs-company-info">
            <div class="bs-company-name">GMD South Phils Metal Fabrication Works</div>
            <div>National Hi-way, Brgy. Masiit, Calauan, Laguna</div>
            <div>TIN CERTIFICATE REG. TIN # 279-809-827-000</div>
            <div>DTI REGISTRATION CERTIFICATE NO. 1019791</div>
            <div>BUSINESS ID. NO,. 19-07-066</div>
        </div>
    </div>

    <div class="ptr-photo-page-title">ACTUAL TEST — SUPPORTING PHOTOS</div>
    <div class="ptr-photo-grid">
        @foreach($report->test_photos as $url)
        <img src="{{ $url }}" alt="Actual test photo">
        @endforeach
    </div>
</div>
@endif
