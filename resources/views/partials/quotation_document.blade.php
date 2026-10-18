{{-- GMD's quotation, generated from a QuotationDocument snapshot ($doc). Used for the on-screen preview
     (admin + client) and, with $forPdf, the PDF — so it is built from tables and plain CSS that dompdf
     renders the same way a browser does. Everything shown comes from the snapshot; nothing is typed here. --}}
@php
    $forPdf  = $forPdf ?? false;
    $peso    = fn ($v) => number_format((float) $v, 2);
    $num     = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $date    = \Carbon\Carbon::parse($doc['date']);
    // dompdf reads images from disk (and needs GD for them); the browser uses normal URLs
    $img = function (string $path) use ($forPdf) {
        if (!file_exists(public_path($path))) return null;
        if ($forPdf) return extension_loaded('gd') ? public_path($path) : null;
        return asset($path);
    };
    $logoLeft  = $img('images/logo-left.png');
    $logoRight = $img('images/logo-right.png');
    $bd = $doc['breakdown'];
    $mainItem = $doc['items'][0];
@endphp
<style>
    .qd-sheet { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; color: #1a1a1a; font-size: 11px; line-height: 1.45; background: #fff; position: relative; }
    .qd-sheet table { border-collapse: collapse; width: 100%; }
    .qd-head td { vertical-align: middle; }
    .qd-logo { height: 58px; margin-right: 6px; }
    .qd-company { text-align: right; font-size: 9.5px; color: #444; }
    .qd-company-name { font-size: 13px; font-weight: bold; color: #1a1a1a; margin-bottom: 2px; }
    .qd-title { margin: 10px 0 8px; text-align: right; }
    .qd-title span { display: inline-block; background: #1a1a1a; color: #fff; font-size: 16px; font-weight: bold; letter-spacing: 3px; padding: 5px 18px; }
    .qd-draft { margin: 0 0 8px; padding: 6px 10px; border: 1px dashed #b45309; color: #92400e; background: #fffbeb; font-weight: bold; text-align: center; font-size: 10.5px; }
    .qd-info td { border: 1px solid #555; padding: 5px 7px; vertical-align: top; }
    .qd-info .qd-k { width: 19%; font-weight: bold; font-size: 9.5px; text-transform: uppercase; background: #f2f2f2; }
    .qd-info .qd-k2 { width: 11%; font-weight: bold; font-size: 9.5px; text-transform: uppercase; background: #f2f2f2; }
    .qd-info .qd-v2 { width: 22%; font-weight: bold; white-space: nowrap; }
    .qd-items { margin-top: 10px; }
    .qd-items th { background: #1a1a1a; color: #fff; font-size: 9.5px; text-transform: uppercase; padding: 6px; border: 1px solid #1a1a1a; }
    .qd-items td { border: 1px solid #555; padding: 7px; vertical-align: top; }
    .qd-items .qd-c { text-align: center; }
    .qd-items .qd-r { text-align: right; white-space: nowrap; }
    .qd-item-title { font-weight: bold; margin-bottom: 6px; }
    .qd-specs td { border: none; padding: 1px 6px 1px 0; vertical-align: top; font-size: 10.5px; }
    .qd-specs .qd-spec-k { width: 34%; font-weight: bold; color: #333; }
    .qd-total td { font-weight: bold; font-size: 12px; background: #f2f2f2; }
    .qd-section-label { margin-top: 12px; font-weight: bold; font-size: 11px; }
    .qd-terms { margin-top: 6px; }
    .qd-notes { margin: 4px 0 0; padding-left: 18px; }
    .qd-notes li { margin-bottom: 4px; }
    .qd-sign { margin-top: 18px; }
    .qd-sign-name { margin-top: 26px; font-weight: bold; text-decoration: underline; }
    .qd-annex { margin-top: 22px; border-top: 2px solid #1a1a1a; padding-top: 10px; }
    .qd-annex h3 { font-size: 12px; margin: 0 0 2px; letter-spacing: .5px; }
    .qd-annex p { margin: 0 0 8px; color: #555; font-size: 10px; }
    .qd-bd th { background: #f2f2f2; border: 1px solid #999; padding: 4px 6px; font-size: 9.5px; text-transform: uppercase; text-align: left; }
    .qd-bd td { border: 1px solid #bbb; padding: 4px 6px; font-size: 10.5px; }
    .qd-bd .qd-r { text-align: right; white-space: nowrap; }
    .qd-bd .qd-sub td { font-weight: bold; background: #fafafa; }
    .qd-bd-gap { height: 8px; }
    .qd-foot { margin-top: 20px; padding-top: 8px; border-top: 1px solid #999; text-align: center; font-size: 9px; color: #555; }
    .qd-foot strong { color: #1a1a1a; }
    .qd-tagline { font-style: italic; font-weight: bold; color: #1a1a1a; margin-top: 2px; }
    @if($forPdf)
    .qd-annex { page-break-before: always; border-top: none; padding-top: 0; margin-top: 0; }
    @endif
</style>

<div class="qd-sheet">
    <table class="qd-head">
        <tr>
            <td style="width:45%;">
                @if($logoLeft)<img src="{{ $logoLeft }}" alt="GMD South Phils" class="qd-logo">@endif
                @if($logoRight)<img src="{{ $logoRight }}" alt="" class="qd-logo">@endif
                @if(!$logoLeft && !$logoRight)<div class="qd-company-name">GMD SOUTH PHILS</div>@endif
            </td>
            <td class="qd-company">
                <div class="qd-company-name">{{ $doc['company']['name'] }}</div>
                <div>{{ $doc['company']['address'] }}</div>
                <div>DTI REG. NO. {{ $doc['company']['dti'] }} &nbsp;|&nbsp; TIN # {{ $doc['company']['tin'] }}</div>
                @if(!empty($doc['company']['email']) || !empty($doc['company']['phone']))
                <div>{{ $doc['company']['email'] ?? '' }}{{ !empty($doc['company']['email']) && !empty($doc['company']['phone']) ? ' / ' : '' }}{{ $doc['company']['phone'] ?? '' }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="qd-title"><span>QUOTATION</span></div>

    @if($doc['is_draft'])
    <div class="qd-draft">DRAFT PREVIEW — not yet sent to the client. The quotation number and date are assigned when it is sent.</div>
    @endif

    <table class="qd-info">
        <tr>
            <td class="qd-k">Company Name / Address:</td>
            <td><strong>{{ $doc['client']['company_name'] }}</strong>@if($doc['client']['address'])<br>{{ $doc['client']['address'] }}@endif</td>
            <td class="qd-k2">Quotation No.</td>
            <td class="qd-v2">{{ $doc['quotation_number'] }}</td>
        </tr>
        <tr>
            <td class="qd-k">Contact Person:</td>
            <td>
                {{ $doc['client']['contact_person'] }}
                @if($doc['client']['contact']) — {{ $doc['client']['contact'] }} @endif
                @if($doc['client']['email'])<br>{{ $doc['client']['email'] }} @endif
            </td>
            <td class="qd-k2">Date</td>
            <td class="qd-v2">{{ $date->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td class="qd-k">Project Title / Location:</td>
            {{-- data-live: the admin's live preview updates these as they are typed (no effect elsewhere) --}}
            <td><span data-live="project_title">{{ $doc['project']['title'] }}</span>@if($doc['project']['location'])<br><span data-live="project_location">{{ $doc['project']['location'] }}</span>@endif</td>
            <td class="qd-k2">RFQ Ref No.</td>
            <td class="qd-v2"><span data-live="rfq_ref" data-live-empty="—">{{ $doc['project']['rfq_ref'] ?: '—' }}</span></td>
        </tr>
    </table>

    <table class="qd-items">
        <thead>
            <tr>
                <th style="width:7%;">Item</th>
                <th>Description</th>
                <th style="width:19%;">Qty / Unit</th>
                <th style="width:16%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doc['items'] as $item)
            <tr>
                <td class="qd-c">{{ $item['no'] }}</td>
                <td>
                    <div class="qd-item-title">{{ $item['title'] }}</div>
                    @if(!empty($item['specs']))
                    <table class="qd-specs" @if($loop->first) data-live-specs @endif>
                        @foreach($item['specs'] as $line)
                        <tr>
                            <td class="qd-spec-k">{{ $line['label'] }}{{ $line['label'] !== '' ? ':' : '' }}</td>
                            <td>{{ $line['value'] }}</td>
                        </tr>
                        @endforeach
                    </table>
                    @endif
                </td>
                <td class="qd-c">
                    @if($item['quantity'])
                        {{ $peso($item['unit_price']) }}<br>× {{ $item['quantity'] }} {{ $item['unit'] }}
                    @endif
                </td>
                <td class="qd-r">{{ $item['amount'] !== null ? $peso($item['amount']) : '' }}</td>
            </tr>
            @endforeach
            <tr class="qd-total">
                <td colspan="3" class="qd-r">Total Amount (VAT Exc.) &nbsp; PHP</td>
                <td class="qd-r">{{ $peso($doc['totals']['total']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="qd-terms"><strong>Payment terms:</strong> {{ $doc['payment_terms'] }}</div>

    <div class="qd-section-label">General notes:</div>
    <ol class="qd-notes">
        <li>{{ $doc['validity'] }}</li>
        @if(!empty($doc['warranty']))<li>Warranty: <span data-live="warranty">{{ $doc['warranty'] }}</span></li>@endif
        @foreach($doc['notes'] as $note)
        <li data-live-note>{{ $note }}</li>
        @endforeach
    </ol>

    <div class="qd-sign">
        <div>{{ $doc['closing'] }}</div>
        <div style="margin-top:10px;">Sincerely,</div>
        <div class="qd-sign-name" data-live="signatory_name">{{ $doc['signatory']['name'] }}</div>
        <div data-live="signatory_title">{{ $doc['signatory']['title'] }}</div>
    </div>

    @if($bd['show'] && (count($bd['materials']) || count($bd['labor'])))
    <div class="qd-annex">
        <h3>ANNEX A — ITEMIZED COST BREAKDOWN</h3>
        <p>Quotation {{ $doc['quotation_number'] }} · costs for one {{ $doc['tank']['unit'] }}; the total multiplies them by {{ $bd['quantity'] }}. Material prices include allowance for cutting and wastage.</p>

        <table class="qd-bd">
            <thead>
                <tr><th>Materials</th><th class="qd-r" style="width:11%;">Qty</th><th style="width:10%;">Unit</th><th class="qd-r" style="width:16%;">Unit Price</th><th class="qd-r" style="width:16%;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($bd['materials'] as $m)
                <tr>
                    <td>{{ $m['name'] }}</td>
                    <td class="qd-r">{{ $num($m['quantity']) }}</td>
                    <td>{{ $m['unit'] }}</td>
                    <td class="qd-r">{{ $peso($m['unit_price']) }}</td>
                    <td class="qd-r">{{ $peso($m['amount']) }}</td>
                </tr>
                @empty
                <tr><td colspan="5">—</td></tr>
                @endforelse
                <tr class="qd-sub"><td colspan="4" class="qd-r">Materials subtotal</td><td class="qd-r">{{ $peso($bd['materials_subtotal']) }}</td></tr>
            </tbody>
        </table>
        <div class="qd-bd-gap"></div>
        <table class="qd-bd">
            <thead>
                <tr><th>Labor</th><th class="qd-r" style="width:11%;">Days</th><th style="width:10%;"></th><th class="qd-r" style="width:16%;">Daily Rate</th><th class="qd-r" style="width:16%;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($bd['labor'] as $l)
                <tr>
                    <td>{{ $l['description'] }}</td>
                    <td class="qd-r">{{ $num($l['days']) }}</td>
                    <td></td>
                    <td class="qd-r">{{ $peso($l['rate']) }}</td>
                    <td class="qd-r">{{ $peso($l['amount']) }}</td>
                </tr>
                @empty
                <tr><td colspan="5">—</td></tr>
                @endforelse
                <tr class="qd-sub"><td colspan="4" class="qd-r">Labor subtotal</td><td class="qd-r">{{ $peso($bd['labor_subtotal']) }}</td></tr>
            </tbody>
        </table>
        <div class="qd-bd-gap"></div>
        <table class="qd-bd">
            <tbody>
                <tr><td>Materials</td><td class="qd-r" style="width:16%;">{{ $peso($bd['materials_subtotal']) }}</td></tr>
                <tr><td>Labor</td><td class="qd-r">{{ $peso($bd['labor_subtotal']) }}</td></tr>
                <tr><td>Overhead, supervision &amp; profit ({{ $num($bd['overhead_percent']) }}%)</td><td class="qd-r">{{ $peso($bd['overhead']) }}</td></tr>
                <tr class="qd-sub"><td class="qd-r">Price per {{ $doc['tank']['unit'] }}</td><td class="qd-r">{{ $peso($bd['unit_price']) }}</td></tr>
                <tr class="qd-sub"><td class="qd-r">× {{ $bd['quantity'] }} {{ $bd['quantity'] === 1 ? $doc['tank']['unit'] : $doc['tank']['unit'] . 's' }} — Total Amount (VAT Exc.)</td><td class="qd-r">PHP {{ $peso($bd['total']) }}</td></tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="qd-foot">
        <strong>{{ $doc['company']['name'] }}</strong><br>
        {{ $doc['company']['address'] }}@if(!empty($doc['company']['email'])) · {{ $doc['company']['email'] }}@endif @if(!empty($doc['company']['phone'])) · {{ $doc['company']['phone'] }}@endif
        @if(!empty($doc['company']['tagline']))<div class="qd-tagline">"{{ $doc['company']['tagline'] }}"</div>@endif
    </div>
</div>
