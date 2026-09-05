@php
    $totalPaid = $payment->totalPaid();
    $balance   = $payment->currentBalance();
    $particulars = $statement->project_title ?: ($payment->project->name ?? '—');
@endphp

<div class="bs-sheet">
    <!-- Letterhead -->
    <div class="bs-letterhead">
        <div class="bs-brand">
            <div class="bs-brand-gmd">GMD</div>
            <div class="bs-brand-sub">SOUTHPHILS</div>
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
                <th style="width:140px;text-align:right;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $statement->statement_date->format('F d, Y') }}</td>
                <td><strong>{{ $particulars }}</strong></td>
                <td></td>
            </tr>
            @foreach($payment->stages() as $stage)
            @php
                $stageLabel  = \App\Models\PaymentTransaction::stageLabel($stage);
                $stagePct    = $payment->contract_amount > 0
                    ? round((($stageAmounts[$stage] ?? 0) / $payment->contract_amount) * 100)
                    : 0;
            @endphp
            <tr>
                <td></td>
                <td class="bs-stage-row">{{ $stageLabel }} ({{ $stagePct }}%)</td>
                <td style="text-align:right;font-weight:700;">{{ number_format($stageAmounts[$stage] ?? 0, 2) }}</td>
            </tr>
            @endforeach
            <tr class="bs-subtotal-row">
                <td></td>
                <td style="text-align:right;">Total Contract Amount</td>
                <td style="text-align:right;">{{ number_format($payment->contract_amount, 2) }}</td>
            </tr>
            @foreach($payment->transactions->sortBy('payment_date') as $tx)
            <tr>
                <td></td>
                <td class="bs-less-row">Less: &nbsp; {{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }} (paid {{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }})</td>
                <td style="text-align:right;color:#b91c1c;">{{ number_format($tx->amount_paid, 2) }}</td>
            </tr>
            @endforeach
            <tr class="bs-total-row">
                <td></td>
                <td style="text-align:right;">Total amount balance &nbsp;&nbsp; PHP</td>
                <td style="text-align:right;">{{ number_format($balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

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
            <div class="bs-sig-role">{{ $statement->prepared_by_role ?: '—' }}</div>
        </div>
        <div class="bs-sig-block">
            <div class="bs-sig-label">Approved by:</div>
            <div class="bs-sig-name">{{ $statement->approved_by_name ?: '—' }}</div>
            <div class="bs-sig-role">{{ $statement->approved_by_role ?: '—' }}</div>
        </div>
    </div>
</div>
