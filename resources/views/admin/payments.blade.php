<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Payments</h1>
                    <p>Track down payments, payment phases, and billing per project.</p>
                </div>
                <button class="add-btn" type="button" id="openAddPaymentModal">
                    <i data-lucide="plus"></i>
                    Record Payment
                </button>
            </div>

            <!-- Summary -->
            <div class="page-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="check-circle"></i></div>
                    <h3>Fully Paid</h3>
                    <div class="value">1</div>
                    <div class="info-card-sub">Water Tank Installation</div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="circle-half"></i></div>
                    <h3>Partial Payment</h3>
                    <div class="value">1</div>
                    <div class="info-card-sub">50% down paid</div>
                </div>
                <div class="info-card">
                    <div class="info-card-icon"><i data-lucide="clock"></i></div>
                    <h3>Pending Payment</h3>
                    <div class="value">1</div>
                    <div class="info-card-sub">No down payment yet</div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="paymentSearch" placeholder="Search project or client...">
                    </div>
                    <div class="filter-group">
                        <select id="paymentStatusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Client Type</th>
                                <th>Contract Amount</th>
                                <th>Down Payment (50%)</th>
                                <th>Balance</th>
                                <th>Payment Terms</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Fuel Storage Tank Fabrication</td>
                                <td>ABC Corporation</td>
                                <td><span class="client-badge corporate">Corporate</span></td>
                                <td>₱ 850,000</td>
                                <td>₱ 425,000 ✔</td>
                                <td>₱ 425,000</td>
                                <td>2 phases</td>
                                <td><span class="status-badge partial">Partial</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="View Details"
                                        onclick="openPaymentView('Fuel Storage Tank Fabrication')">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Water Tank Installation</td>
                                <td>South Plant Inc.</td>
                                <td><span class="client-badge owner">Owner</span></td>
                                <td>₱ 280,000</td>
                                <td>₱ 140,000 ✔</td>
                                <td>₱ 0</td>
                                <td>2 phases</td>
                                <td><span class="status-badge completed">Paid</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="View Details"
                                        onclick="openPaymentView('Water Tank Installation')">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Chemical Tank Repair</td>
                                <td>Prime Chemicals</td>
                                <td><span class="client-badge corporate">Corporate</span></td>
                                <td>₱ 420,000</td>
                                <td>—</td>
                                <td>₱ 420,000</td>
                                <td>2 phases</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="View Details"
                                        onclick="openPaymentView('Chemical Tank Repair')">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD PAYMENT MODAL -->
    <div class="modal-overlay" id="addPaymentModal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2>Record Payment</h2>
                    <p>Log a payment entry for a project.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddPaymentModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="addPaymentForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Project</label>
                        <select id="payProject" required>
                            <option value="">Select project</option>
                            <option value="Fuel Storage Tank Fabrication">Fuel Storage Tank Fabrication</option>
                            <option value="Water Tank Installation">Water Tank Installation</option>
                            <option value="Chemical Tank Repair">Chemical Tank Repair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Client</label>
                        <input type="text" id="payClient" required placeholder="Client name">
                    </div>
                    <div class="form-group">
                        <label>Client Type</label>
                        <select id="payClientType" required>
                            <option value="">Select type</option>
                            <option value="Owner">Owner (Direct)</option>
                            <option value="Corporate">Corporate Account</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contract Amount (₱)</label>
                        <input type="number" id="payContract" required placeholder="e.g. 850000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Down Payment Received (₱)</label>
                        <input type="number" id="payDown" placeholder="e.g. 425000" min="0">
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select id="payStatus" required>
                            <option value="">Select status</option>
                            <option value="Pending">Pending</option>
                            <option value="Partial">Partial (50% Down)</option>
                            <option value="Paid">Fully Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Terms</label>
                        <select id="payTerms" required>
                            <option value="2 phases">2 Phases (50% Down + 50% Completion)</option>
                            <option value="3 phases">3 Phases</option>
                            <option value="Full payment">Full Payment Upfront</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" id="payDate" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddPayment">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save"></i>
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>