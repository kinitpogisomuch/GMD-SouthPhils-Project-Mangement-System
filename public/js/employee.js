/* ============================================================
   Employee Project View — Phase Tracker + Conditional Log
   ============================================================ */

const EMP_PHASES = [
    { key: "planning",    label: "Planning & Design",    icon: "pencil-ruler",  desc: "Shop drawing approved by client",   days: 5,  shortLabel: "Planning" },
    { key: "procurement", label: "Material Procurement", icon: "shopping-cart", desc: "PO issued · 3 suppliers",           days: 4,  shortLabel: "Procurement" },
    { key: "matl_prep",   label: "Material Preparation", icon: "package",       desc: "Layout, marking, cutting complete", days: 3,  shortLabel: "Matl. Prep" },
    { key: "fabrication", label: "Fabrication",          icon: "hammer",        desc: "Welding, grinding, assembly",       days: 21, shortLabel: "Fabrication" },
    { key: "inspection",  label: "Inspection",           icon: "shield-check",  desc: "Pressure test and quality check",   days: 2,  shortLabel: "Inspection" },
    { key: "painting",    label: "Painting",             icon: "paintbrush",    desc: "Surface coating and finishing",     days: 2,  shortLabel: "Painting" },
    { key: "completion",  label: "Completion",           icon: "check-circle",  desc: "Final QA check and documentation",  days: 1,  shortLabel: "Completion" },
    { key: "delivery",    label: "Delivery",             icon: "truck",         desc: "Final delivery and turnover",       days: 1,  shortLabel: "Delivery" }
];

function empAdjustDays(duration) {
    if (duration && duration.includes("14")) return [2, 2, 2, 5, 1, 1, 1, 1];
    return [5, 4, 3, 21, 2, 2, 1, 1];
}

function empGetStatuses(progress) {
    const total = EMP_PHASES.reduce(function(a, b) { return a + b.days; }, 0);
    var acc = 0;
    return EMP_PHASES.map(function(phase) {
        var start = (acc / total) * 100;
        var end   = ((acc + phase.days) / total) * 100;
        acc += phase.days;
        if (progress >= end)   return "done";
        if (progress >= start) return "active";
        return "pending";
    });
}

function empGetActiveIndex(statuses) {
    var idx = statuses.indexOf("active");
    if (idx !== -1) return idx;
    if (statuses.every(function(s) { return s === "done"; })) return EMP_PHASES.length;
    return 0;
}

/* ── Phase Tracker (read-only) ────────────────────────── */

function buildEmpPhaseSteps(statuses) {
    var container = document.getElementById("empPhaseSteps");
    if (!container) return;
    container.innerHTML = "";

    EMP_PHASES.forEach(function(phase, i) {
        var status = statuses[i];
        var step = document.createElement("div");
        step.className = "phase-step " + status;

        var iconHTML;
        if (status === "done")   iconHTML = '<i data-lucide="check"></i>';
        else if (status === "active") iconHTML = '<i data-lucide="loader"></i>';
        else iconHTML = '<i data-lucide="' + phase.icon + '"></i>';

        step.innerHTML =
            '<div class="phase-step-box">' +
                '<div class="phase-step-icon">' + iconHTML + '</div>' +
                '<div class="phase-step-label">' + phase.shortLabel + '</div>' +
            '</div>';

        container.appendChild(step);
    });

    if (typeof lucide !== "undefined") lucide.createIcons();
}

/* ── Progress Badge ───────────────────────────────────── */

function updateEmpBadge(progress) {
    var badge = document.getElementById("empProgressBadge");
    if (!badge) return;
    if (progress >= 100) {
        badge.style.background = "#dcfce7";
        badge.style.color = "#16a34a";
        badge.textContent = "100% Complete";
    } else if (progress > 0) {
        badge.style.background = "#fef3c7";
        badge.style.color = "#b45309";
        badge.textContent = progress + "% Complete";
    } else {
        badge.style.background = "#f1f5f9";
        badge.style.color = "#64748b";
        badge.textContent = "Not Started";
    }
}

/* ── Employee Phase Panel ─────────────────────────────── */
/*
   Phase rules:
   0–1  (Planning, Procurement)  → Owner only — show status message
   2–3  (Matl Prep, Fabrication) → General progress log form
   4    (Inspection)             → Pressure test log form
   5    (Painting)               → General progress log form
   6–7+ (Completion, Delivery)   → Employee work complete message
*/

function buildEmpPhasePanel(activeIndex, statuses) {
    var container = document.getElementById("empPhasePanel");
    if (!container) return;

    /* ── Owner phases (Planning / Procurement) ── */
    if (activeIndex === 0 || activeIndex === 1) {
        var phaseLabel = EMP_PHASES[activeIndex].label;
        container.innerHTML =
            '<div class="info-box" style="background:#fef9c3;border:1px solid #fde68a;">' +
                '<i data-lucide="info" style="color:#d97706;"></i>' +
                '<div>' +
                    '<div class="info-box-title" style="color:#92400e;">Project is in ' + phaseLabel + ' Phase</div>' +
                    '<div class="info-box-body" style="color:#78350f;">Your hands-on work begins at Material Preparation — when materials arrive at the job site. No log entry is needed at this time. The owner is managing this phase.</div>' +
                '</div>' +
            '</div>';
        if (typeof lucide !== "undefined") lucide.createIcons();
        return;
    }

    /* ── Employee work complete (Completion / Delivery / all done) ── */
    if (activeIndex >= 6) {
        container.innerHTML =
            '<div class="info-box" style="background:#dcfce7;border:1px solid #86efac;">' +
                '<i data-lucide="check-circle-2" style="color:#16a34a;"></i>' +
                '<div>' +
                    '<div class="info-box-title" style="color:#14532d;">Your Work is Complete</div>' +
                    '<div class="info-box-body" style="color:#166534;">Fabrication and painting are finished. The owner is coordinating delivery and final turnover with the client. Great work!</div>' +
                '</div>' +
            '</div>';
        if (typeof lucide !== "undefined") lucide.createIcons();
        return;
    }

    /* ── Inspection: Pressure Test Log ── */
    if (activeIndex === 4) {
        container.innerHTML = buildInspectionForm();
        if (typeof lucide !== "undefined") lucide.createIcons();
        return;
    }

    /* ── General Progress Log (Matl Prep, Fabrication, Painting) ── */
    var phaseLabel = EMP_PHASES[activeIndex].label;
    container.innerHTML = buildGeneralLogForm(phaseLabel);
    if (typeof lucide !== "undefined") lucide.createIcons();
}

/* ── General Log Form ─────────────────────────────────── */

function buildGeneralLogForm(phaseLabel) {
    return (
        '<div class="emp-pv-card" style="margin-bottom:20px;">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">' +
                '<i data-lucide="clipboard-list" style="width:18px;height:18px;color:var(--accent);"></i>' +
                '<h3 class="emp-pv-card-title" style="margin-bottom:0;">Submit Progress Log — ' + phaseLabel + '</h3>' +
            '</div>' +
            '<form id="empLogForm" onsubmit="submitEmpLog(event)">' +

                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:0;" class="grid-2-log">' +
                    '<div class="log-field">' +
                        '<label class="log-label">DATE OF WORK</label>' +
                        '<input type="date" name="work_date" class="log-input" required>' +
                    '</div>' +
                    '<div class="log-field">' +
                        '<label class="log-label">HOURS WORKED</label>' +
                        '<input type="number" name="hours_worked" class="log-input" min="0.5" max="24" step="0.5" placeholder="e.g. 8" required>' +
                    '</div>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label class="log-label">WORK DONE <span style="color:var(--accent);font-weight:400;">*</span></label>' +
                    '<textarea name="work_done" class="log-textarea" rows="3" placeholder="Describe what was accomplished today..." required></textarea>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label class="log-label">ISSUES / OBSERVATIONS <span style="color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0;">(optional)</span></label>' +
                    '<textarea name="issues" class="log-textarea" rows="2" placeholder="Any problems, delays, or observations to flag..."></textarea>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label class="log-label">SITE PHOTOS <span style="color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0;">(optional)</span></label>' +
                    '<label class="log-upload-label">' +
                        '<i data-lucide="upload-cloud"></i>' +
                        'Click to upload photos' +
                        '<input type="file" name="photos" multiple accept="image/*" style="display:none;" onchange="previewEmpPhotos(this)">' +
                    '</label>' +
                    '<div id="photoPreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>' +
                '</div>' +

                '<div style="display:flex;justify-content:flex-end;">' +
                    '<button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:13.5px;">' +
                        '<i data-lucide="send" style="width:14px;height:14px;"></i> Submit Log' +
                    '</button>' +
                '</div>' +
            '</form>' +
        '</div>'
    );
}

/* ── Inspection / Pressure Test Form ─────────────────── */

function buildInspectionForm() {
    return (
        '<div class="emp-pv-card" style="margin-bottom:20px;">' +
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">' +
                '<i data-lucide="shield-check" style="width:18px;height:18px;color:var(--accent);"></i>' +
                '<h3 class="emp-pv-card-title" style="margin-bottom:0;">Pressure Test Log — Inspection</h3>' +
            '</div>' +
            '<p style="font-size:12.5px;color:var(--text-secondary);margin-bottom:20px;">Log the pressure test result. The owner will review and make the final call to advance to Painting.</p>' +

            '<form id="empInspectionForm" onsubmit="submitInspectionLog(event)">' +

                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="grid-2-log">' +
                    '<div class="log-field">' +
                        '<label class="log-label">DATE OF TEST</label>' +
                        '<input type="date" name="test_date" class="log-input" required>' +
                    '</div>' +
                    '<div class="log-field">' +
                        '<label class="log-label">SOAK DURATION (hours)</label>' +
                        '<input type="number" name="soak_hours" class="log-input" min="0.5" step="0.5" placeholder="e.g. 2" required>' +
                    '</div>' +
                '</div>' +

                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="grid-2-log">' +
                    '<div class="log-field">' +
                        '<label class="log-label">TEST PRESSURE (PSI)</label>' +
                        '<input type="number" name="pressure_psi" class="log-input" min="0" step="1" placeholder="e.g. 150" required>' +
                    '</div>' +
                    '<div class="log-field">' +
                        '<label class="log-label">TEST RESULT</label>' +
                        '<div style="display:flex;gap:20px;padding-top:8px;">' +
                            '<label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13.5px;font-weight:700;">' +
                                '<input type="radio" name="test_result" value="pass" style="accent-color:#16a34a;width:15px;height:15px;" required>' +
                                '<span style="color:#16a34a;">PASSED</span>' +
                            '</label>' +
                            '<label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13.5px;font-weight:700;">' +
                                '<input type="radio" name="test_result" value="fail" style="accent-color:#dc2626;width:15px;height:15px;">' +
                                '<span style="color:#dc2626;">FAILED</span>' +
                            '</label>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:600;color:var(--text-primary);margin-bottom:10px;">' +
                        '<input type="checkbox" id="leaksFound" onchange="toggleLeakDetail()" style="width:15px;height:15px;accent-color:#dc2626;">' +
                        'Leaks were found during the test' +
                    '</label>' +
                    '<div id="leakDetail" style="display:none;">' +
                        '<label class="log-label">LEAK DESCRIPTION</label>' +
                        '<textarea name="leak_desc" class="log-textarea" rows="2" style="border-color:#fca5a5;" placeholder="Describe where leaks were found and their severity..."></textarea>' +
                    '</div>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label class="log-label">ADDITIONAL NOTES <span style="color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0;">(optional)</span></label>' +
                    '<textarea name="notes" class="log-textarea" rows="2" placeholder="Any other observations from the inspection..."></textarea>' +
                '</div>' +

                '<div class="log-field">' +
                    '<label class="log-label">TEST PHOTOS <span style="color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0;">(optional)</span></label>' +
                    '<label class="log-upload-label">' +
                        '<i data-lucide="upload-cloud"></i>' +
                        'Upload test photos' +
                        '<input type="file" name="photos" multiple accept="image/*" style="display:none;" onchange="previewEmpPhotos(this)">' +
                    '</label>' +
                    '<div id="photoPreview" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;"></div>' +
                '</div>' +

                '<div style="display:flex;justify-content:flex-end;">' +
                    '<button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:13.5px;">' +
                        '<i data-lucide="send" style="width:14px;height:14px;"></i> Submit Inspection Log' +
                    '</button>' +
                '</div>' +

            '</form>' +
        '</div>'
    );
}

/* ── Form Helpers ─────────────────────────────────────── */

function toggleLeakDetail() {
    var cb     = document.getElementById("leaksFound");
    var detail = document.getElementById("leakDetail");
    if (cb && detail) detail.style.display = cb.checked ? "block" : "none";
}

function previewEmpPhotos(input) {
    var preview = document.getElementById("photoPreview");
    if (!preview) return;
    preview.innerHTML = "";
    Array.prototype.slice.call(input.files, 0, 6).forEach(function(file) {
        var url = URL.createObjectURL(file);
        var div = document.createElement("div");
        div.style.cssText = "width:80px;height:60px;border-radius:6px;overflow:hidden;border:1px solid var(--border);";
        div.innerHTML = '<img src="' + url + '" style="width:100%;height:100%;object-fit:cover;">';
        preview.appendChild(div);
    });
}

function submitEmpLog(e) {
    e.preventDefault();
    var btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" style="width:14px;height:14px;"></i> Submitting...';
    if (typeof lucide !== "undefined") lucide.createIcons();
    setTimeout(function() {
        showLogSuccess("Progress log submitted. The owner has been notified.");
    }, 1200);
}

function submitInspectionLog(e) {
    e.preventDefault();
    var btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" style="width:14px;height:14px;"></i> Submitting...';
    if (typeof lucide !== "undefined") lucide.createIcons();
    setTimeout(function() {
        showLogSuccess("Inspection log submitted. The owner will review the pressure test result and advance the project if approved.");
    }, 1200);
}

function showLogSuccess(message) {
    var panel = document.getElementById("empPhasePanel");
    var form  = panel ? panel.querySelector("form") : null;
    if (!form) return;

    var successDiv = document.createElement("div");
    successDiv.style.cssText = "background:#dcfce7;border:1px solid #86efac;border-radius:var(--radius-sm);padding:14px 16px;display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;color:#14532d;margin-top:16px;";
    successDiv.innerHTML = '<i data-lucide="check-circle-2" style="width:18px;height:18px;flex-shrink:0;"></i> ' + message;
    form.parentNode.appendChild(successDiv);
    form.remove();
    if (typeof lucide !== "undefined") lucide.createIcons();
}

/* ── Init ─────────────────────────────────────────────── */

(function() {
    var adjustedDays = empAdjustDays(PROJECT_DURATION);
    EMP_PHASES.forEach(function(p, i) { p.days = adjustedDays[i]; });

    var statuses    = empGetStatuses(PROJECT_PROGRESS);
    var activeIndex = empGetActiveIndex(statuses);

    updateEmpBadge(PROJECT_PROGRESS);
    buildEmpPhaseSteps(statuses);
    buildEmpPhasePanel(activeIndex, statuses);

    if (typeof lucide !== "undefined") lucide.createIcons();
})();
