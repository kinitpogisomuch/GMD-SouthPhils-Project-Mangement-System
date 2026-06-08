<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon teal"><i data-lucide="receipt"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱5.25M</div>
                        <div class="stat-label">Total Contract Value</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱3.40M</div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i data-lucide="clock"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱430K</div>
                        <div class="stat-label">Pending Payment</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i data-lucide="calendar"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">₱1.42M</div>
                        <div class="stat-label">Remaining Balance</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Payment Schedule & History</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice No.</th>
                                <th>Project</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Paid Date</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span style="font-family:monospace;font-size:12px;">INV-2025-001</span></td>
                                <td>Storage Tank Fabrication</td>
                                <td>Mobilization / Downpayment</td>
                                <td><strong>₱500,000</strong></td>
                                <td>Jan 15, 2025</td>
                                <td>Jan 14, 2025</td>
                                <td><span class="badge badge-success">Paid</span></td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i></a></td>
                            </tr>
                            <tr>
                                <td><span style="font-family:monospace;font-size:12px;">INV-2025-002</span></td>
                                <td>Pipeline Installation</td>
                                <td>Mobilization / Downpayment</td>
                                <td><strong>₱360,000</strong></td>
                                <td>Feb 5, 2025</td>
                                <td>Feb 4, 2025</td>
                                <td><span class="badge badge-success">Paid</span></td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i></a></td>
                            </tr>
                            <tr>
                                <td><span style="font-family:monospace;font-size:12px;">INV-2025-003</span></td>
                                <td>Storage Tank Fabrication</td>
                                <td>Progress Billing – 50% completion</td>
                                <td><strong>₱750,000</strong></td>
                                <td>Mar 1, 2025</td>
                                <td>Feb 28, 2025</td>
                                <td><span class="badge badge-success">Paid</span></td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i></a></td>
                            </tr>
                            <tr>
                                <td><span style="font-family:monospace;font-size:12px;">INV-2025-004</span></td>
                                <td>Structural Steel Works</td>
                                <td>Progress Billing – 75% completion</td>
                                <td><strong>₱430,000</strong></td>
                                <td>Apr 1, 2025</td>
                                <td>—</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                                <td><span style="color:var(--text-muted);font-size:12px;">—</span></td>
                            </tr>
                            <tr>
                                <td><span style="font-family:monospace;font-size:12px;">INV-2025-005</span></td>
                                <td>Storage Tank Fabrication</td>
                                <td>Final Billing – Completion</td>
                                <td><strong>₱750,000</strong></td>
                                <td>Jun 30, 2025</td>
                                <td>—</td>
                                <td><span class="badge badge-gray">Upcoming</span></td>
                                <td><span style="color:var(--text-muted);font-size:12px;">—</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>