<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <p style="color:var(--text-secondary); font-size:13.5px;">Download contracts, drawings, permits, and project reports.</p>
                <div style="display:flex; gap:10px;">
                    <select style="padding:8px 12px; border-radius:var(--radius-sm); border:1px solid var(--border-2); font-size:13px; background:var(--surface); color:var(--text-primary);">
                        <option>All Projects</option>
                        <option>Storage Tank Fabrication</option>
                        <option>Pipeline Installation</option>
                        <option>Structural Steel Works</option>
                    </select>
                    <select style="padding:8px 12px; border-radius:var(--radius-sm); border:1px solid var(--border-2); font-size:13px; background:var(--surface); color:var(--text-primary);">
                        <option>All Types</option>
                        <option>Contract</option>
                        <option>Drawing</option>
                        <option>Permit</option>
                        <option>Report</option>
                    </select>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">All Documents</span>
                    <span style="font-size:12.5px; color:var(--text-secondary);">12 files</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:32px;height:32px;background:#fee2e2;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#dc2626;flex-shrink:0;">
                                            <i data-lucide="file-text" style="width:15px;height:15px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13.5px;">Contract Agreement – Unit A</div>
                                            <div style="font-size:11.5px;color:var(--text-secondary);">Signed copy</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Storage Tank Fabrication</td>
                                <td><span class="badge badge-teal">Contract</span></td>
                                <td>2.4 MB</td>
                                <td>Jan 15, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:32px;height:32px;background:#dbeafe;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#1d4ed8;flex-shrink:0;">
                                            <i data-lucide="file-text" style="width:15px;height:15px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13.5px;">Technical Drawings – Rev.2</div>
                                            <div style="font-size:11.5px;color:var(--text-secondary);">Latest revision</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Pipeline Installation</td>
                                <td><span class="badge badge-info">Drawing</span></td>
                                <td>8.1 MB</td>
                                <td>Feb 20, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:32px;height:32px;background:#dcfce7;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#16a34a;flex-shrink:0;">
                                            <i data-lucide="file-check" style="width:15px;height:15px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13.5px;">Building Permit – Block C</div>
                                            <div style="font-size:11.5px;color:var(--text-secondary);">Approved by LGU</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Structural Steel Works</td>
                                <td><span class="badge badge-success">Permit</span></td>
                                <td>1.2 MB</td>
                                <td>Mar 8, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:9px;">
                                        <div style="width:32px;height:32px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                            <i data-lucide="file-bar-chart" style="width:15px;height:15px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13.5px;">Q1 Inspection Report 2025</div>
                                            <div style="font-size:11.5px;color:var(--text-secondary);">Third-party inspection</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Structural Steel Works</td>
                                <td><span class="badge badge-gray">Report</span></td>
                                <td>3.7 MB</td>
                                <td>Mar 31, 2025</td>
                                <td><a href="#" class="btn btn-outline btn-sm"><i data-lucide="download"></i> Download</a></td>
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