<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

            @php
                $status    = $payment->computeStatus();
                $totalPaid = $payment->totalPaid();
                $balance   = $payment->currentBalance();
                $pct       = $payment->contract_amount > 0
                    ? round(($totalPaid / $payment->contract_amount) * 100, 1)
                    : 0;
            @endphp

            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;color:var(--muted);">
                <a href="{{ route('client.payments') }}" style="color:var(--muted);text-decoration:none;font-weight:600;">
                    Payments
                </a>
                <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                <span style="color:var(--dark);font-weight:700;">{{ $payment->project->name ?? 'Payment Detail' }}</span>
            </div>

            <div class="page-header" style="margin-bottom:24px;align-items:flex-start;">
                <div>
                    <h1 class="page-title">{{ $payment->project->name ?? 'Payment Detail' }}</h1>
                    <p class="page-subtitle">{{ $payment->payment_terms }} &nbsp;·&nbsp; Contract signed {{ $payment->date ? \Carbon\Carbon::parse($payment->date)->format('M d, Y') : '—' }}</p>
                </div>
                <span class="status-badge {{ \App\Models\Payment::statusBadgeClass($status) }}" style="font-size:13px;padding:8px 16px;">
                    {{ $status }}
                </span>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card teal">
                    <div class="stat-icon teal"><i data-lucide="file-text"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($payment->contract_amount, 0) }}</div>
                        <div class="stat-label">Contract Amount</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($totalPaid, 0) }}</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i data-lucide="wallet"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱{{ number_format($balance, 0) }}</div>
                        <div class="stat-label">Remaining Balance</div>
                    </div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i data-lucide="badge-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $pct }}%</div>
                        <div class="stat-label">Paid of Contract</div>
                    </div>
                </div>
            </div>

            <!-- Stage Breakdown -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Payment Breakdown by Stage</div>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                    @foreach($payment->stages() as $stage)
                        @php
                            $expected   = $stageAmounts[$stage] ?? 0;
                            $stagePaid  = isset($stageTransactions[$stage]) ? $stageTransactions[$stage]->sum('amount_paid') : 0;
                            $stageLeft  = max(0, $expected - $stagePaid);
                            $isPaid     = in_array($stage, $paidStages);
                            $stageLabel = \App\Models\PaymentTransaction::stageLabel($stage);
                            $stagePct   = $expected > 0 ? min(100, round(($stagePaid / $expected) * 100, 1)) : 0;
                        @endphp
                        @php $stageProofs = $payment->proofs->where('payment_stage', $stage); @endphp
                        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:stretch;">
                            <div style="flex:2;min-width:280px;border:1px solid var(--border);border-radius:16px;padding:18px 20px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap;">
                                    <div style="font-weight:900;font-size:15px;color:var(--dark);">{{ $stageLabel }}</div>
                                    @if($isPaid)
                                        <span class="status-badge completed">Paid</span>
                                    @elseif($stagePaid > 0)
                                        <span class="status-badge ongoing">Partial</span>
                                    @else
                                        <span class="status-badge pending">Unpaid</span>
                                    @endif
                                </div>
                                <div class="progress-bar" style="height:8px;margin-bottom:14px;">
                                    <div class="progress-fill"
                                         style="width:{{ $stagePct }}%;
                                         background:{{ $isPaid ? 'var(--success)' : 'var(--accent)' }};"></div>
                                </div>
                                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:10px;">
                                    <div class="info-mini">
                                        <div class="info-mini-label">Expected</div>
                                        <div class="info-mini-value">₱{{ number_format($expected, 2) }}</div>
                                    </div>
                                    <div class="info-mini">
                                        <div class="info-mini-label">Paid</div>
                                        <div class="info-mini-value" style="color:var(--success);">₱{{ number_format($stagePaid, 2) }}</div>
                                    </div>
                                    <div class="info-mini">
                                        <div class="info-mini-label">Remaining</div>
                                        <div class="info-mini-value" style="color:var(--danger);">₱{{ number_format($stageLeft, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div style="flex:1;min-width:250px;border:1px solid var(--border);border-radius:16px;padding:18px 20px;background:var(--surface-2);">
                                <div style="font-weight:800;font-size:12.5px;color:var(--dark);text-transform:uppercase;letter-spacing:.03em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                                    <i data-lucide="upload-cloud" style="width:14px;height:14px;"></i>
                                    Upload Proof of Payment
                                </div>

                                @if(!$isPaid)
                                <form method="POST" action="{{ route('client.payments.proof.store', $payment->id) }}" enctype="multipart/form-data" class="proof-upload-form" data-stage="{{ $stage }}">
                                    @csrf
                                    <input type="hidden" name="payment_stage" value="{{ $stage }}">
                                    <label for="proofFilesInput{{ $stage }}" class="qr-upload-dropzone" id="proofDropzone{{ $stage }}" style="margin-bottom:8px;">
                                        <i data-lucide="file-plus" style="width:18px;height:18px;color:var(--accent);"></i>
                                        <span style="font-size:12px;font-weight:700;color:var(--dark);">Click to upload receipt/screenshot</span>
                                        <span style="font-size:10.5px;color:var(--muted);">PDF or image, up to 5 files, max 10MB each</span>
                                    </label>
                                    <input type="file" name="proof_files[]" id="proofFilesInput{{ $stage }}" accept=".pdf,image/*" multiple required style="display:none;">
                                    <div id="proofFilesList{{ $stage }}" class="qr-file-list" style="display:none;"></div>
                                    <textarea name="notes" rows="2" placeholder="Optional note (e.g. reference number)"
                                              style="width:100%;border:1px solid var(--border);border-radius:10px;padding:8px 10px;font-size:12px;font-family:inherit;box-sizing:border-box;resize:vertical;margin-bottom:8px;"></textarea>
                                    <button type="submit" class="save-btn" style="width:100%;justify-content:center;padding:9px;font-size:12.5px;">
                                        <i data-lucide="send" style="width:13px;height:13px;"></i>
                                        Submit Proof
                                    </button>
                                </form>
                                @else
                                <div style="font-size:12px;color:var(--muted);">This stage has already been confirmed as paid.</div>
                                @endif

                                @if($stageProofs->isNotEmpty())
                                <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--border);display:flex;flex-direction:column;gap:6px;">
                                    <div style="font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Submitted</div>
                                    @foreach($stageProofs as $proof)
                                    <a href="{{ $proof->file_url }}" target="_blank" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--accent);text-decoration:none;">
                                        <i data-lucide="file-text" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        {{ $proof->created_at->format('M d, Y g:i A') }}
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($payment->billingStatements->isNotEmpty())
            <!-- Billing Statements -->
            <div class="card" style="overflow:hidden;margin-bottom:24px;">
                <div class="card-header">
                    <div class="card-title">Billing Statements</div>
                </div>
                <div style="display:flex;flex-direction:column;">
                    @foreach($payment->billingStatements as $statement)
                    <a href="{{ route('client.payments.billing_statements.show', [$payment->id, $statement->id]) }}"
                       style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i data-lucide="file-text" style="width:16px;height:16px;color:var(--accent);"></i>
                            <span style="font-weight:700;">Statement dated {{ $statement->statement_date->format('M d, Y') }}</span>
                            @if($statement->reference_no)
                            <span style="font-size:12px;color:var(--muted);">Ref: {{ $statement->reference_no }}</span>
                            @endif
                        </div>
                        <i data-lucide="chevron-right" style="width:16px;height:16px;color:var(--muted);"></i>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Payment History -->
            <div class="card" style="overflow:hidden;">
                <div class="card-header">
                    <div class="card-title">Payment History</div>
                </div>
                @if($payment->transactions->isEmpty())
                    <div class="empty-state">
                        <i data-lucide="receipt" style="display:block;margin:0 auto;"></i>
                        <p>No payment transactions recorded yet.</p>
                    </div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Stage</th>
                                    <th>Amount Paid</th>
                                    <th>Reference #</th>
                                    <th>Notes</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->transactions->sortByDesc('payment_date') as $tx)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($tx->payment_date)->format('M d, Y') }}</td>
                                        <td>{{ \App\Models\PaymentTransaction::stageLabel($tx->payment_stage) }}</td>
                                        <td><strong style="color:var(--success);">₱{{ number_format($tx->amount_paid, 2) }}</strong></td>
                                        <td>{{ $tx->reference_number ?? '—' }}</td>
                                        <td>{{ $tx->notes ?? '—' }}</td>
                                        <td>
                                            @php $receiptUrls = !empty($tx->receipt_urls) ? $tx->receipt_urls : array_filter([$tx->receipt_url]); @endphp
                                            @if(!empty($receiptUrls))
                                            <div style="display:flex;flex-direction:column;gap:3px;">
                                                @foreach($receiptUrls as $i => $url)
                                                <a href="{{ $url }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;color:var(--accent);font-weight:700;text-decoration:none;">
                                                    <i data-lucide="receipt" style="width:14px;height:14px;"></i> View{{ count($receiptUrls) > 1 ? ' ' . ($i + 1) : '' }}
                                                </a>
                                                @endforeach
                                            </div>
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
    <script>
        lucide.createIcons();

        // ── Proof-of-payment upload — chip UI (mirrors the "Request a
        // Quotation" reference-files upload: dropzone until something's
        // picked, then compact chips with a "+" tile to add more) ──
        document.querySelectorAll('.proof-upload-form').forEach(function (form) {
            var stage    = form.dataset.stage;
            var input    = document.getElementById('proofFilesInput' + stage);
            var dropzone = document.getElementById('proofDropzone' + stage);
            var list     = document.getElementById('proofFilesList' + stage);
            if (!input) return;

            var MAX_FILES = 5;
            var selected  = [];

            function formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }
            function syncInput() {
                var dt = new DataTransfer();
                selected.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
            }
            function viewFile(index) {
                var f = selected[index];
                if (!f) return;
                window.open(URL.createObjectURL(f), '_blank');
            }
            function removeFile(index) {
                selected.splice(index, 1);
                syncInput();
                render();
            }
            function render() {
                dropzone.style.display = selected.length ? 'none' : '';
                list.style.display     = selected.length ? 'flex' : 'none';

                list.innerHTML = selected.map(function (f, i) {
                    var isImage = f.type.indexOf('image/') === 0;
                    var thumb   = isImage
                        ? '<img class="qr-file-thumb" src="' + URL.createObjectURL(f) + '" alt="">'
                        : '<span class="qr-file-thumb"><i data-lucide="file-text"></i></span>';
                    return '<div class="qr-file-chip" data-index="' + i + '" title="Click to view">'
                        + thumb
                        + '<div class="qr-file-meta">'
                            + '<div class="qr-file-name" title="' + f.name.replace(/"/g, '&quot;') + '">' + f.name + '</div>'
                            + '<div class="qr-file-size">' + formatSize(f.size) + '</div>'
                        + '</div>'
                        + '<button type="button" class="qr-file-remove" data-index="' + i + '" title="Remove"><i data-lucide="x"></i></button>'
                    + '</div>';
                }).join('');

                if (selected.length && selected.length < MAX_FILES) {
                    list.innerHTML += '<button type="button" class="qr-file-add" data-stage="' + stage + '" title="Add more"><i data-lucide="plus"></i></button>';
                }

                list.querySelectorAll('.qr-file-chip').forEach(function (chip) {
                    chip.addEventListener('click', function (e) {
                        if (e.target.closest('.qr-file-remove')) return;
                        viewFile(Number(chip.dataset.index));
                    });
                });
                list.querySelectorAll('.qr-file-remove').forEach(function (btn) {
                    btn.addEventListener('click', function () { removeFile(Number(btn.dataset.index)); });
                });
                var addBtn = list.querySelector('.qr-file-add');
                if (addBtn) addBtn.addEventListener('click', function () { input.value = ''; input.click(); });

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            input.addEventListener('change', function () {
                var rejected = [];
                Array.from(input.files || []).forEach(function (f) {
                    if (f.size > 10 * 1024 * 1024) { rejected.push(f.name); return; }
                    var isDuplicate = selected.some(function (sf) {
                        return sf.name === f.name && sf.size === f.size && sf.lastModified === f.lastModified;
                    });
                    if (!isDuplicate && selected.length < MAX_FILES) selected.push(f);
                });
                syncInput();
                render();
                if (rejected.length && typeof showFileTooLargeModal === 'function') showFileTooLargeModal(rejected.join(', '), 10);
            });

            form.addEventListener('submit', function (e) {
                if (!selected.length) {
                    e.preventDefault();
                    alert('Please attach at least one receipt/screenshot.');
                }
            });
        });
    </script>
</body>
</html>
