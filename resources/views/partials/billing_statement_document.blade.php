@php
    $totalPaid = $payment->totalPaid();
    $particulars = $statement->project_title ?: ($payment->project->name ?? '—');

    // This statement is an invoice for $statement->billing_stage — the balance shown
    // here anticipates that bill being settled (contract minus what's already paid
    // minus what's being billed now), not just what's been recorded so far.
    $billedStageAmount = 0;
    if ($statement->billing_stage) {
        $billingStagePaid = $payment->transactions->where('payment_stage', $statement->billing_stage)->sum('amount_paid');
        if ($billingStagePaid <= 0) {
            $billedStageAmount = $payment->stageAmounts()[$statement->billing_stage] ?? 0;
        }
    }
    $balance = max(0, $payment->currentBalance() - $billedStageAmount);
@endphp

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

    <div class="bs-title">BILLING STATEMENT</div>

    <!-- Fields grid -->
    <table class="bs-fields">
        <tr>
            <td class="bs-field-label">Attention:</td>
            <td class="bs-field-value">{{ $statement->attention ?: '—' }}</td>
            <td class="bs-field-label">Statement Date</td>
            <td class="bs-field-value">{{ $statement->statement_date->format('F d, Y') }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">Bill to:</td>
            <td class="bs-field-value">{{ $statement->bill_to ?: $payment->client }}</td>
            <td class="bs-field-label">Reference No.</td>
            <td class="bs-field-value">{{ $statement->reference_no ?: '—' }}</td>
        </tr>
        <tr><td colspan="4" style="height:14px;"></td></tr>
        <tr>
            <td class="bs-field-label">TIN#</td>
            <td class="bs-field-value" colspan="3">{{ $statement->tin_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">Project title:</td>
            <td class="bs-field-value" colspan="3">{{ $statement->project_title ?: ($payment->project->name ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">Project location:</td>
            <td class="bs-field-value" colspan="3">{{ $statement->project_location ?: ($payment->project->address ?? '—') }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">P.O. Number:</td>
            <td class="bs-field-value" colspan="3">{{ $statement->po_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">P.R. Number:</td>
            <td class="bs-field-value" colspan="3">{{ $statement->pr_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="bs-field-label">Subject:</td>
            <td class="bs-field-value" colspan="3">{{ $statement->subject ?: '—' }}</td>
        </tr>
    </table>

    <!-- Particulars table -->
    @php
        $stageAmounts = $payment->stageAmounts();
    @endphp
    <table class="bs-particulars">
        <thead>
            <tr>
                <th style="width:110px;">DATE</th>
                <th>PARTICULARS</th>
                <th style="width:140px;text-align:center;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $statement->statement_date->format('F d, Y') }}</td>
                <td>
                    <strong>{{ $particulars }}</strong>
                </td>
                <td style="text-align:center;">
                    <div style="font-size:9.5px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px;">Total Contract Amount</div>
                    <span style="font-weight:800;">{{ number_format($payment->contract_amount, 2) }}</span>
                </td>
            </tr>
            @foreach($payment->stages() as $stage)
            @php
                $stageLabel   = \App\Models\PaymentTransaction::stageLabel($stage);
                $stagePct     = $payment->contract_amount > 0
                    ? round((($stageAmounts[$stage] ?? 0) / $payment->contract_amount) * 100)
                    : 0;
                $isBilled     = $statement->billing_stage === $stage;
                $stagePaidAmt = $payment->transactions->where('payment_stage', $stage)->sum('amount_paid');
                // Amounts only appear once a term is actually billed or paid — future
                // terms show ₱0.00 until their own statement bills them.
                $displayAmt   = ($isBilled || $stagePaidAmt > 0) ? ($stageAmounts[$stage] ?? 0) : 0;
                $stageStatus  = $stagePaidAmt <= 0
                    ? 'Unpaid'
                    : ($stagePaidAmt >= ($stageAmounts[$stage] ?? 0) ? 'Paid' : 'Partially Paid');
            @endphp
            <tr @if($isBilled) style="background:#fff3d6;" @endif>
                <td></td>
                <td class="bs-stage-row">
                    {{ $stageLabel }} ({{ $stagePct }}%)
                    @if($stageStatus !== 'Unpaid')
                    <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.03em;margin-left:6px;color:{{ $stageStatus === 'Paid' ? '#16a34a' : '#b45309' }};">{{ $stageStatus }}</span>
                    @endif
                    @if($isBilled)
                    <strong>&nbsp;— BILLED THIS STATEMENT</strong>
                    @endif
                </td>
                <td style="text-align:center;font-weight:700;">{{ $displayAmt > 0 ? number_format($displayAmt, 2) : '–' }}</td>
            </tr>
            @endforeach
            <tr class="bs-total-row">
                <td></td>
                <td style="text-align:right;">Total amount balance &nbsp;&nbsp; PHP</td>
                <td style="text-align:center;">{{ number_format($balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($statement->billing_stage)
    <div class="bs-final-balance" style="background:#fff3d6;border:1px solid #f0c674;border-radius:6px;padding:10px 14px;">
        This statement bills you for: <strong>{{ \App\Models\PaymentTransaction::stageLabel($statement->billing_stage) }}</strong>
        &nbsp;—&nbsp; Amount Due: <strong>PHP {{ number_format($stageAmounts[$statement->billing_stage] ?? 0, 2) }}</strong>
    </div>
    @endif

    <div class="bs-final-balance">
        Final Amount balance: &nbsp; <strong>PHP {{ number_format($balance, 2) }}</strong>
    </div>

    <div class="bs-deposit">
        <div>For payment, please name check or deposit to:</div>
        <div class="bs-deposit-text">{{ $statement->deposit_instructions ?: '—' }}</div>
    </div>

    <div class="bs-questions">If you have questions, please let us know.</div>

    <div class="bs-signatures">
        <div class="bs-sig-block">
            <div class="bs-sig-label">Prepared by:</div>
            <div class="bs-sig-name">{{ $statement->prepared_by_name ?: '—' }}</div>
            <div class="bs-sig-line"></div>
            <div class="bs-sig-role">{{ $statement->prepared_by_role ?: '—' }}</div>
        </div>
        <div class="bs-sig-block">
            <div class="bs-sig-label">Approved by:</div>
            <div class="bs-sig-name">{{ $statement->approved_by_name ?: '—' }}</div>
            <div class="bs-sig-line"></div>
            <div class="bs-sig-role">{{ $statement->approved_by_role ?: '—' }}</div>
        </div>
    </div>

    <div class="bs-auto-note">This is an auto-generated billing statement.</div>
</div>
