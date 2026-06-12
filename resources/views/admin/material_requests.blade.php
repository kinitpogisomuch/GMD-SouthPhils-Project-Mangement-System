<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Requests | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Material Requests</h1>
                    <p>Track material requests sent to suppliers and monitor shortages.</p>
                </div>
                <button class="add-btn" type="button" id="openAddMaterialModal">
                    <i data-lucide="plus"></i>
                    New Request
                </button>
            </div>

            <!-- Summary row -->
            <div class="page-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                <div class="info-card orange">
                    <div class="info-card-icon orange"><i data-lucide="clock"></i></div>
                    <h3>Pending Requests</h3>
                    <div class="value">2</div>
                    <div class="info-card-sub">Awaiting supplier response</div>
                </div>
                <div class="info-card green">
                    <div class="info-card-icon green"><i data-lucide="check-circle"></i></div>
                    <h3>Fulfilled</h3>
                    <div class="value">2</div>
                    <div class="info-card-sub">Materials delivered</div>
                </div>
                <div class="info-card red">
                    <div class="info-card-icon red"><i data-lucide="alert-triangle"></i></div>
                    <h3>Shortages Flagged</h3>
                    <div class="value">2</div>
                    <div class="info-card-sub">Needs immediate restocking</div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="materialSearch" placeholder="Search material...">
                    </div>
                    <div class="filter-group">
                        <select id="materialStatusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="fulfilled">Fulfilled</option>
                            <option value="shortage">Shortage</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table" id="materialsTable">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Project</th>
                                <th>Supplier</th>
                                <th>Requested Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Welding Electrodes (6013)</td>
                                <td>50</td>
                                <td>kg</td>
                                <td>Fuel Storage Tank Fabrication</td>
                                <td>Steel Supply Co.</td>
                                <td>May 12, 2026</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="Mark as Fulfilled"
                                        onclick="markFulfilled(this)">
                                        <i data-lucide="check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Steel Plates (6mm)</td>
                                <td>20</td>
                                <td>sheets</td>
                                <td>Fuel Storage Tank Fabrication</td>
                                <td>MetalWorks PH</td>
                                <td>May 10, 2026</td>
                                <td><span class="status-badge completed">Fulfilled</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="View">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Grinding Discs</td>
                                <td>30</td>
                                <td>pcs</td>
                                <td>Chemical Tank Repair</td>
                                <td>Steel Supply Co.</td>
                                <td>May 18, 2026</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="Mark as Fulfilled"
                                        onclick="markFulfilled(this)">
                                        <i data-lucide="check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Welding Rods (7018)</td>
                                <td>25</td>
                                <td>kg</td>
                                <td>Fuel Storage Tank Fabrication</td>
                                <td>MetalWorks PH</td>
                                <td>May 8, 2026</td>
                                <td><span class="status-badge shortage">Shortage</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="Re-request"
                                        onclick="rerequestMaterial(this)">
                                        <i data-lucide="refresh-ccw"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Angle Bars (2x2)</td>
                                <td>40</td>
                                <td>pcs</td>
                                <td>Chemical Tank Repair</td>
                                <td>Steel Supply Co.</td>
                                <td>May 5, 2026</td>
                                <td><span class="status-badge completed">Fulfilled</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="View">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Safety Gloves</td>
                                <td>10</td>
                                <td>pairs</td>
                                <td>All Projects</td>
                                <td>Safety Depot PH</td>
                                <td>May 15, 2026</td>
                                <td><span class="status-badge shortage">Shortage</span></td>
                                <td>
                                    <button class="action-btn view" type="button" title="Re-request"
                                        onclick="rerequestMaterial(this)">
                                        <i data-lucide="refresh-ccw"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD MATERIAL REQUEST MODAL -->
    <div class="modal-overlay" id="addMaterialModal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h2>New Material Request</h2>
                    <p>Send a material request to your supplier.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddMaterialModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form id="addMaterialForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Material Name</label>
                        <input type="text" id="matName" required placeholder="e.g. Welding Electrodes">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" id="matQty" required placeholder="e.g. 50" min="1">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select id="matUnit" required>
                            <option value="">Select unit</option>
                            <option value="kg">kg</option>
                            <option value="pcs">pcs</option>
                            <option value="sheets">sheets</option>
                            <option value="pairs">pairs</option>
                            <option value="rolls">rolls</option>
                            <option value="liters">liters</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Project</label>
                        <select id="matProject" required>
                            <option value="">Select project</option>
                            <option value="Fuel Storage Tank Fabrication">Fuel Storage Tank Fabrication</option>
                            <option value="Chemical Tank Repair">Chemical Tank Repair</option>
                            <option value="All Projects">All Projects</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select id="matSupplier" required>
                            <option value="">Select supplier</option>
                            <option value="Steel Supply Co.">Steel Supply Co.</option>
                            <option value="MetalWorks PH">MetalWorks PH</option>
                            <option value="Safety Depot PH">Safety Depot PH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Requested Date</label>
                        <input type="date" id="matDate" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddMaterial">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="send"></i>
                        Send Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>