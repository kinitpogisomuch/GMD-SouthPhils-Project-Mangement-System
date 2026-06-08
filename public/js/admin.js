/* ================= DATE TIME ================= */

function updateDateTime() {
    const dateEl = document.getElementById("headerDate");
    const timeEl = document.getElementById("headerTime");
    if (!dateEl || !timeEl) return;

    const now = new Date();
    dateEl.textContent = now.toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    timeEl.textContent = now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit", hour12: true });
}

/* ================= ADMIN PROFILE DROPDOWN ================= */

function initializeAdminDropdown() {
    const dropdown = document.querySelector(".admin-dropdown");
    const button = document.getElementById("adminDropdownBtn");
    if (!dropdown || !button) return;

    button.addEventListener("click", function (event) {
        event.stopPropagation();
        const notificationDropdown = document.querySelector(".notification-dropdown");
        if (notificationDropdown) notificationDropdown.classList.remove("open");
        dropdown.classList.toggle("open");
    });

    dropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });
}

/* ================= NOTIFICATION DROPDOWN ================= */

function initializeNotificationDropdown() {
    const dropdown = document.querySelector(".notification-dropdown");
    const button = document.getElementById("notificationDropdownBtn");
    if (!dropdown || !button) return;

    button.addEventListener("click", function (event) {
        event.stopPropagation();
        const adminDropdown = document.querySelector(".admin-dropdown");
        if (adminDropdown) adminDropdown.classList.remove("open");
        dropdown.classList.toggle("open");
    });

    dropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });
}

function closeDropdownsOnOutsideClick() {
    document.addEventListener("click", function () {
        const adminDropdown = document.querySelector(".admin-dropdown");
        const notificationDropdown = document.querySelector(".notification-dropdown");
        if (adminDropdown) adminDropdown.classList.remove("open");
        if (notificationDropdown) notificationDropdown.classList.remove("open");
    });
}

/* ================= HELPERS ================= */

function getStatusClass(status) {
    const s = status.toLowerCase();
    if (s === "ongoing" || s === "active") return "ongoing";
    if (s === "completed" || s === "paid") return "completed";
    if (s === "partial") return "partial";
    if (s === "shortage") return "shortage";
    return "pending";
}

function getTaskClass(task) {
    const t = task.toLowerCase();
    if (t === "welding") return "welding";
    if (t === "cutting") return "cutting";
    if (t === "grinding") return "grinding";
    if (t === "assembly") return "assembly";
    return "safety";
}

function formatDate(dateValue) {
    if (!dateValue) return "";
    const date = new Date(dateValue);
    return date.toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" });
}

function initializeGenericSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!searchInput || !table) return;

    searchInput.addEventListener("keyup", function () {
        const searchValue = searchInput.value.toLowerCase();
        table.querySelectorAll("tbody tr").forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(searchValue) ? "" : "none";
        });
    });
}

function initializeStatusFilter(filterId, tableId, colIndex) {
    const filter = document.getElementById(filterId);
    const table = document.getElementById(tableId);
    if (!filter || !table) return;

    filter.addEventListener("change", function () {
        const val = filter.value.toLowerCase();
        table.querySelectorAll("tbody tr").forEach(function (row) {
            const cell = row.cells[colIndex];
            if (!cell) return;
            row.style.display = val === "" || cell.textContent.toLowerCase().includes(val) ? "" : "none";
        });
    });
}

function initializeModal(openBtnId, closeBtnId, cancelBtnId, modalId, formId) {
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);
    const closeBtn = document.getElementById(closeBtnId);
    const cancelBtn = document.getElementById(cancelBtnId);
    const form = formId ? document.getElementById(formId) : null;

    if (!modal || !openBtn) return;

    function open() { modal.classList.add("show"); }
    function close() {
        modal.classList.remove("show");
        if (form) form.reset();
    }

    openBtn.addEventListener("click", open);
    if (closeBtn) closeBtn.addEventListener("click", close);
    if (cancelBtn) cancelBtn.addEventListener("click", close);
    modal.addEventListener("click", function (e) { if (e.target === modal) close(); });
}

/* ================= EMPLOYEES ================= */

var employeeStore = {};

function fmt(n) { return "\u20B1 " + Number(n).toLocaleString("en-PH"); }

function formatContact(contact) {
    if (!contact) return "";
    const clean = contact.replace(/\D/g, '');
    if (clean.length === 11) {
        return clean.slice(0, 4) + " " + clean.slice(4, 7) + " " + clean.slice(7);
    }
    return contact;
}

function calcSalary(rate, days, otHours, sss, philhealth, pagibig, otherDed) {
    rate       = parseFloat(rate)       || 0;
    days       = parseFloat(days)       || 0;
    otHours    = parseFloat(otHours)    || 0;
    sss        = parseFloat(sss)        || 0;
    philhealth = parseFloat(philhealth) || 0;
    pagibig    = parseFloat(pagibig)    || 0;
    otherDed   = parseFloat(otherDed)   || 0;

    var hourlyRate = rate / 8;
    var otPay      = otHours * hourlyRate * 1.25;
    var gross      = (rate * days) + otPay;
    var totalDed   = sss + philhealth + pagibig + otherDed;
    var net        = gross - totalDed;
    return { gross: gross, totalDed: totalDed, net: net, otPay: otPay };
}

function buildSalaryTable() {
    var tbody = document.getElementById("salaryTableBody");
    if (!tbody) return;
    tbody.innerHTML = "";

    var totalGross = 0, totalDed = 0, totalNet = 0;

    var rows = document.querySelectorAll("#employeesTable tbody tr");
    rows.forEach(function (row) {
        var d = row.dataset;
        if (!d.name) return;

        var rate       = parseFloat(d.rate) || 0;
        var days       = parseFloat(d.days) || 26;
        var sss        = 500, philhealth = 200, pagibig = 100, otherDed = 0, otHours = 0;

        var stored = employeeStore[d.name];
        if (stored) {
            sss        = stored.sss        || sss;
            philhealth = stored.philhealth || philhealth;
            pagibig    = stored.pagibig    || pagibig;
            otherDed   = stored.otherDed   || otherDed;
            otHours    = stored.otHours    || otHours;
            days       = stored.days       || days;
        }

        var calc = calcSalary(rate, days, otHours, sss, philhealth, pagibig, otherDed);
        totalGross += calc.gross;
        totalDed   += calc.totalDed;
        totalNet   += calc.net;

        var contactFormatted = d.contact ? formatContact(d.contact) : "";

        var tr = document.createElement("tr");
        tr.innerHTML =
            "<td>" + d.name + "</td>" +
            "<td>" + contactFormatted + "</td>" +
            "<td>" + d.role + "</td>" +
            "<td>" + fmt(rate) + "</td>" +
            "<td>" + days + " days</td>" +
            "<td>" + otHours + " hrs</td>" +
            "<td>" + fmt(calc.gross) + "</td>" +
            "<td>" + fmt(calc.totalDed) + "</td>" +
            "<td><strong>" + fmt(calc.net) + "</strong></td>" +
            "<td><span class=\"status-badge completed\">Paid</span></td>" +
            "<td><button class=\"action-btn view salary-slip-btn\" type=\"button\" title=\"View Slip\"" +
                " data-name=\"" + d.name + "\" data-role=\"" + d.role + "\"" +
                " data-contact=\"" + (d.contact || "") + "\"" +
                " data-rate=\"" + rate + "\" data-days=\"" + days + "\"" +
                " data-ot=\"" + otHours + "\" data-gross=\"" + calc.gross + "\"" +
                " data-ded=\"" + calc.totalDed + "\" data-net=\"" + calc.net + "\"" +
                " data-sss=\"" + sss + "\" data-philhealth=\"" + philhealth + "\"" +
                " data-pagibig=\"" + pagibig + "\" data-otherded=\"" + otherDed + "\">" +
                "<i data-lucide=\"file-text\"></i></button></td>";
        tbody.appendChild(tr);
    });

    var summaryGross = document.getElementById("summaryGross");
    var summaryDed   = document.getElementById("summaryDeductions");
    var summaryNet   = document.getElementById("summaryNet");
    var totalPayroll = document.getElementById("totalPayroll");
    if (summaryGross) summaryGross.textContent = fmt(totalGross);
    if (summaryDed)   summaryDed.textContent   = fmt(totalDed);
    if (summaryNet)   summaryNet.textContent    = fmt(totalNet);
    if (totalPayroll) totalPayroll.textContent  = fmt(totalNet);

    if (typeof lucide !== "undefined") lucide.createIcons();

    document.querySelectorAll(".salary-slip-btn").forEach(function (btn) {
        btn.addEventListener("click", function () { openSalarySlip(btn.dataset); });
    });
}

function openSalarySlip(d) {
    var modal = document.getElementById("salaryDetailModal");
    if (!modal) return;
    var nameEl = document.getElementById("salaryDetailName");
    var bodyEl = document.getElementById("salaryDetailBody");
    if (nameEl) nameEl.innerHTML = d.name + " &middot; " + d.role + "<br><small style='font-size:12px;'>" + formatContact(d.contact) + "</small>";
    if (bodyEl) bodyEl.innerHTML =
        "<div class=\"salary-slip\">" +
            "<div class=\"salary-slip-row\"><span>Daily Rate</span><strong>" + fmt(d.rate) + "</strong></div>" +
            "<div class=\"salary-slip-row\"><span>Days Worked</span><strong>" + d.days + " days</strong></div>" +
            "<div class=\"salary-slip-row\"><span>Basic Pay</span><strong>" + fmt(parseFloat(d.rate)*parseFloat(d.days)) + "</strong></div>" +
            "<div class=\"salary-slip-row\"><span>Overtime (" + d.ot + " hrs)</span><strong>" + fmt((parseFloat(d.rate)/8)*parseFloat(d.ot)*1.25) + "</strong></div>" +
            "<div class=\"salary-slip-row\"><span>Gross Pay</span><strong>" + fmt(d.gross) + "</strong></div>" +
            "<div class=\"salary-slip-row deduction\"><span>SSS</span><strong>- " + fmt(d.sss) + "</strong></div>" +
            "<div class=\"salary-slip-row deduction\"><span>PhilHealth</span><strong>- " + fmt(d.philhealth) + "</strong></div>" +
            "<div class=\"salary-slip-row deduction\"><span>Pag-IBIG</span><strong>- " + fmt(d.pagibig) + "</strong></div>" +
            (parseFloat(d.otherded) > 0 ? "<div class=\"salary-slip-row deduction\"><span>Other Deductions</span><strong>- " + fmt(d.otherded) + "</strong></div>" : "") +
            "<div class=\"salary-slip-row total\"><span>NET PAY</span><strong>" + fmt(d.net) + "</strong></div>" +
        "</div>";
    modal.classList.add("show");
}

function initializeEmployeeModals() {
    initializeModal("openAddEmployeeModal", "closeAddEmployeeModal", "cancelAddEmployee", "addEmployeeModal", "addEmployeeForm");
    initializeGenericSearch("employeeSearch", "employeesTable");
    initializeGenericSearch("salarySearch", "salaryTable");

    var roleFilter = document.getElementById("roleFilter");
    var empTable   = document.getElementById("employeesTable");
    if (roleFilter && empTable) {
        roleFilter.addEventListener("change", function () {
            var val = this.value.toLowerCase();
            empTable.querySelectorAll("tbody tr").forEach(function (row) {
                var roleCell = row.cells[2];
                if (!roleCell) return;
                row.style.display = val === "" || roleCell.textContent.toLowerCase().includes(val) ? "" : "none";
            });
        });
    }

    var closeSlip  = document.getElementById("closeSalaryDetailModal");
    var cancelSlip = document.getElementById("cancelSalaryDetail");
    var slipModal  = document.getElementById("salaryDetailModal");
    if (slipModal) {
        if (closeSlip)  closeSlip.onclick  = function () { slipModal.classList.remove("show"); };
        if (cancelSlip) cancelSlip.onclick = function () { slipModal.classList.remove("show"); };
        slipModal.addEventListener("click", function (e) { if (e.target === slipModal) slipModal.classList.remove("show"); });
    }

    initializeModal("openRecordPaymentModal", "closeRecordPaymentModal", "cancelRecordPayment", "recordPaymentModal", "recordPaymentForm");
    var rpForm = document.getElementById("recordPaymentForm");
    if (rpForm) {
        rpForm.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("recordPaymentModal").classList.remove("show");
            rpForm.reset();
        });
    }

    function syncEmpHeaderBtns(activeTabName) {
        var addBtn    = document.getElementById("openAddEmployeeModal");
        var salaryBtn = document.getElementById("openRecordPaymentModal");
        if (addBtn)    addBtn.style.display    = (activeTabName === "salary")    ? "none" : "";
        if (salaryBtn) salaryBtn.style.display = (activeTabName === "employees") ? "none" : "";
    }

    var tabs = document.querySelectorAll(".emp-tab");
    tabs.forEach(function (tab) {
        tab.addEventListener("click", function () {
            tabs.forEach(function (t) { t.classList.remove("active"); });
            document.querySelectorAll(".emp-tab-content").forEach(function (c) { c.classList.remove("active"); });
            tab.classList.add("active");
            var target = document.getElementById("tab-" + tab.dataset.tab);
            if (target) {
                target.classList.add("active");
                if (tab.dataset.tab === "salary") buildSalaryTable();
            }
            syncEmpHeaderBtns(tab.dataset.tab);
        });
    });

    var empTableBody = document.getElementById("employeesTable");
    if (empTableBody) {
        empTableBody.addEventListener("click", function (e) {
            var salaryBtn = e.target.closest(".salary-btn");
            var row = e.target.closest("tr");
            if (!row) return;
            var d = row.dataset;
            if (salaryBtn) {
                var rate = parseFloat(d.rate) || 0;
                var days = parseFloat(d.days) || 26;
                var calc = calcSalary(rate, days, 0, 500, 200, 100, 0);
                openSalarySlip({
                    name: d.name, role: d.role, contact: d.contact || "",
                    rate: rate, days: days, ot: 0,
                    gross: calc.gross, ded: calc.totalDed, net: calc.net,
                    sss: 500, philhealth: 200, pagibig: 100, otherded: 0
                });
            }
        });
    }

    var addForm = document.getElementById("addEmployeeForm");
    if (addForm) {
        addForm.addEventListener("submit", function (e) {
            e.preventDefault();
            var name     = document.getElementById("empName").value;
            var contact  = document.getElementById("empContact").value;
            var role     = document.getElementById("empRole").value;
            var rate     = document.getElementById("empDailyRate").value || "0";
            var sss      = document.getElementById("empSSS").value || "500";
            var ph       = document.getElementById("empPhilHealth").value || "200";
            var pi       = document.getElementById("empPagIbig").value || "100";
            var otherDed = document.getElementById("empOtherDed").value || "0";
            var monthly  = parseFloat(rate) * 26;
            var formattedContact = formatContact(contact);

            var tbody = document.querySelector("#employeesTable tbody");
            if (tbody) {
                var row = document.createElement("tr");
                row.dataset.name    = name;
                row.dataset.contact = contact;
                row.dataset.role    = role;
                row.dataset.rate    = rate;
                row.dataset.monthly = monthly;
                row.dataset.days    = "26";
                row.innerHTML =
                    "<td>" + name + "</td>" +
                    "<td>" + formattedContact + "</td>" +
                    "<td>" + role + "</td>" +
                    "<td>\u20B1 " + parseFloat(rate).toLocaleString() + "</td>" +
                    "<td class=\"action-cell\">" +
                        "<button class=\"action-btn view salary-btn\" type=\"button\" title=\"Salary Details\"><i data-lucide=\"banknote\"></i></button>" +
                    "</td>";
                tbody.appendChild(row);
                employeeStore[name] = {
                    contact: contact,
                    sss: parseFloat(sss),
                    philhealth: parseFloat(ph),
                    pagibig: parseFloat(pi),
                    otherDed: parseFloat(otherDed)
                };
                updateEmpCounts();
                if (typeof lucide !== "undefined") lucide.createIcons();
            }

            var rpSel = document.getElementById("rpEmployee");
            if (rpSel) {
                var opt = document.createElement("option");
                opt.value = name;
                opt.textContent = name;
                rpSel.appendChild(opt);
            }

            document.getElementById("addEmployeeModal").classList.remove("show");
            addForm.reset();
        });
    }

    updateEmpCounts();
}

function updateEmpCounts() {
    var rows    = document.querySelectorAll("#employeesTable tbody tr");
    var totalEl = document.getElementById("totalEmpCount");
    if (totalEl) totalEl.textContent = rows.length;
}

/* ================= INITIALIZE ================= */

updateDateTime();
setInterval(updateDateTime, 1000);

initializeAdminDropdown();
initializeNotificationDropdown();
closeDropdownsOnOutsideClick();
initializeEmployeeModals();

if (typeof lucide !== "undefined") lucide.createIcons();

/* =============== PROJECT VIEW SCRIPTS =============== */

const PHASES = [
    { key: "planning",    label: "Planning & Design",      icon: "pencil-ruler",  desc: "Shop drawing approved by client",        days: 5,  shortLabel: "Planning"    },
    { key: "procurement", label: "Material Procurement",   icon: "shopping-cart", desc: "PO issued · 3 suppliers",                days: 4,  shortLabel: "Procurement" },
    { key: "matl_prep",   label: "Material Preparation",   icon: "package",       desc: "Layout, marking, cutting complete",      days: 3,  shortLabel: "Matl. Prep"  },
    { key: "fabrication", label: "Fabrication",            icon: "hammer",        desc: "Welding, grinding, assembly",            days: 21, shortLabel: "Fabrication" },
    { key: "inspection",  label: "Inspection",             icon: "search",        desc: "Safety and quality inspection",          days: 2,  shortLabel: "Inspection"  },
    { key: "painting",    label: "Painting",               icon: "paintbrush",    desc: "Surface coating and finishing",          days: 2,  shortLabel: "Painting"    },
    { key: "completion",  label: "Completion",             icon: "check-circle",  desc: "Final QA check and documentation",       days: 1,  shortLabel: "Completion"  },
    { key: "delivery",    label: "Delivery",               icon: "truck",         desc: "Final delivery and turnover",            days: 1,  shortLabel: "Delivery"    }
];

function adjustPhaseDays(duration) {
    if (duration && duration.includes("14")) return [2, 2, 2, 5, 1, 1, 1, 1];
    return [5, 4, 3, 21, 2, 2, 1, 1];
}

function getPhaseStatusFromCurrentPhase(currentPhase) {
    const statuses = [];
    let foundActive = false;
    PHASES.forEach(function (phase) {
        if (foundActive) {
            statuses.push("pending");
        } else if (phase.key === currentPhase) {
            statuses.push("active");
            foundActive = true;
        } else {
            statuses.push("done");
        }
    });
    return statuses;
}

function getPhaseStatusFromProgress(progress) {
    const totalDays = PHASES.reduce((a, b) => a + b.days, 0);
    let accumulated = 0;
    const statuses  = [];
    PHASES.forEach(function (phase) {
        const phaseStart = (accumulated / totalDays) * 100;
        const phaseEnd   = ((accumulated + phase.days) / totalDays) * 100;
        if (progress >= phaseEnd)        statuses.push("done");
        else if (progress >= phaseStart) statuses.push("active");
        else                             statuses.push("pending");
        accumulated += phase.days;
    });
    return statuses;
}

function buildPhaseSteps(statuses) {
    const container = document.getElementById("phaseSteps");
    if (!container) return;
    container.innerHTML = "";
    PHASES.forEach(function (phase, index) {
        const status = statuses[index];
        const step   = document.createElement("div");
        step.className = "phase-step " + status;
        let iconHTML = "";
        if (status === "done")        iconHTML = '<i data-lucide="check"></i>';
        else if (status === "active") iconHTML = '<i data-lucide="loader"></i>';
        else                          iconHTML = '<i data-lucide="' + phase.icon + '"></i>';
        step.innerHTML = `
            <div class="phase-step-box">
                <div class="phase-step-icon">${iconHTML}</div>
                <div class="phase-step-label">${phase.shortLabel}</div>
            </div>`;
        container.appendChild(step);
    });
    if (typeof lucide !== "undefined") lucide.createIcons();
}

function buildPhaseDetails(statuses, adjustedDays) {
    const container = document.getElementById("phaseDetailsList");
    if (!container) return;
    container.innerHTML = "";
    PHASES.forEach(function (phase, index) {
        const status      = statuses[index];
        const days        = adjustedDays[index];
        const statusLabel = status === "done" ? "Done" : status === "active" ? "In Progress" : "Upcoming";
        const item        = document.createElement("div");
        item.className    = "phase-detail-item";
        item.innerHTML = `
            <div class="phase-detail-left">
                <div class="phase-dot ${status}"></div>
                <div>
                    <div class="phase-detail-name">${phase.label}</div>
                    <div class="phase-detail-desc">${phase.desc}</div>
                </div>
            </div>
            <div class="phase-detail-status ${status}">${statusLabel}</div>
            <div class="phase-detail-dur">${days} day${days !== 1 ? "s" : ""}</div>`;
        container.appendChild(item);
    });
}

function buildTimeline(statuses, adjustedDays) {
    const tbody   = document.getElementById("timelineBody");
    const totalEl = document.getElementById("timelineTotal");
    if (!tbody) return;
    tbody.innerHTML = "";

    let currentDate = new Date();
    if (typeof START_DATE_STR !== 'undefined' && START_DATE_STR && START_DATE_STR !== "N/A") {
        const parsed = new Date(START_DATE_STR);
        if (!isNaN(parsed)) currentDate = parsed;
    }

    const totalDays = adjustedDays.reduce((a, b) => a + b, 0);
    let dayOffset   = 0;

    PHASES.forEach(function (phase, index) {
        const status = statuses[index];
        const days   = adjustedDays[index];
        const startD = new Date(currentDate);
        startD.setDate(startD.getDate() + dayOffset);
        const endD = new Date(startD);
        endD.setDate(endD.getDate() + days);
        const startStr = startD.toLocaleDateString("en-US", { month: "short", day: "numeric" });
        const endStr   = status === "active"
            ? "~" + endD.toLocaleDateString("en-US", { month: "short", day: "numeric" })
            : endD.toLocaleDateString("en-US", { month: "short", day: "numeric" });
        const tr = document.createElement("tr");
        tr.className = status === "done" ? "done-row" : status === "active" ? "active-row" : "";
        tr.innerHTML = `<td>${phase.shortLabel}</td><td>${startStr}</td><td>${endStr}</td><td>${days}d</td>`;
        tbody.appendChild(tr);
        dayOffset += days;
    });

    if (totalEl) {
        const endDate = new Date(currentDate);
        endDate.setDate(endDate.getDate() + totalDays);
        const endStr = endDate.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
        totalEl.innerHTML = `<span>Estimated Total Duration</span><span>${totalDays} days &nbsp;·&nbsp; Target: ${endStr}</span>`;
    }
}

function updateProgressBadge(progress) {
    const badge = document.getElementById("progressBadge");
    if (!badge) return;
    if (progress >= 100) {
        badge.style.backgroundColor = "#E7F6EC";
        badge.style.color           = "#207A3A";
        badge.textContent           = "100%";
    } else if (progress > 0) {
        badge.style.backgroundColor = "#EAF0FF";
        badge.style.color           = "#2A4EAA";
        badge.textContent           = progress + "%";
    } else {
        badge.style.backgroundColor = "#FFF3D6";
        badge.style.color           = "#8A6100";
        badge.textContent           = "0%";
    }
}

/* ── INIT — only runs on project view pages ── */
(function () {
    if (typeof PROJECT_PROGRESS === 'undefined') return;

    const adjustedDays = adjustPhaseDays(
        typeof PROJECT_DURATION !== 'undefined' ? PROJECT_DURATION : ''
    );
    PHASES.forEach(function (phase, i) { phase.days = adjustedDays[i]; });

    let statuses;
    if (typeof PROJECT_CURRENT_PHASE !== 'undefined' && PROJECT_CURRENT_PHASE) {
        statuses = getPhaseStatusFromCurrentPhase(PROJECT_CURRENT_PHASE);
    } else {
        statuses = getPhaseStatusFromProgress(PROJECT_PROGRESS);
    }

    updateProgressBadge(PROJECT_PROGRESS);
    buildPhaseSteps(statuses);
    buildPhaseDetails(statuses, adjustedDays);
    buildTimeline(statuses, adjustedDays);

    if (typeof lucide !== "undefined") lucide.createIcons();
})();

/* =============== SETTINGS SCRIPTS =============== */

/* ================= MANAGE CLIENTS MODULE ================= */
// ✅ FIX: Removed all localStorage client logic and form submit interception.
// All client CRUD is handled by Laravel form submissions (add/edit/delete).
// This function only handles the search — nothing else.

function initializeClientsModule() {
    var clientsTable = document.getElementById("clientsTable");
    if (!clientsTable) return;

    // Search only — all CRUD is handled by Laravel form submissions
    initializeGenericSearch("clientSearch", "clientsTable");
}

/* ================= TOAST ================= */

function showToast(msg) {
    var toast = document.getElementById("successToast");
    var label = document.getElementById("toastMsg");
    if (!toast) return;
    if (label) label.textContent = msg || "Saved successfully.";
    toast.classList.add("show");
    setTimeout(function () { toast.classList.remove("show"); }, 3000);
}

/* ================= AVATAR ================= */

var changeAvatarBtn = document.getElementById("changeAvatarBtn");
var avatarInput     = document.getElementById("avatarInput");

if (changeAvatarBtn) {
    changeAvatarBtn.addEventListener("click", function () {
        if (avatarInput) avatarInput.click();
    });
}

if (avatarInput) {
    avatarInput.addEventListener("change", function (e) {
        var file = e.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (ev) {
            var avatarEl = document.getElementById("avatarDisplay");
            if (avatarEl) avatarEl.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        };
        reader.readAsDataURL(file);
    });
}

/* ================= PROFILE FORM ================= */

var profileOriginal = {};

(function captureOriginal() {
    ["profileFirstName","profileLastName","profileUsername","profileEmail","profilePhone","profilePosition","profileAddress"].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginal[id] = el.value;
    });
})();

var profileForm = document.getElementById("profileForm");
if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var firstName = document.getElementById("profileFirstName").value.trim();
        var lastName  = document.getElementById("profileLastName").value.trim();
        var fullName  = (firstName + " " + lastName).trim();
        var avatarName = document.getElementById("avatarName");
        if (avatarName) avatarName.textContent = fullName || "Admin";
        var avatarDisplay = document.getElementById("avatarDisplay");
        if (avatarDisplay && !avatarDisplay.querySelector("img")) {
            var initials = (firstName[0] || "") + (lastName[0] || "");
            avatarDisplay.innerHTML = initials
                ? '<span style="font-size:28px;font-weight:900;color:var(--white);">' + initials.toUpperCase() + '</span>'
                : '<i data-lucide="user"></i>';
            if (typeof lucide !== "undefined") lucide.createIcons();
        }
        showToast("Profile updated successfully.");
    });
}

window.resetProfileForm = function () {
    Object.keys(profileOriginal).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.value = profileOriginal[id];
    });
};

/* ================= PASSWORD FORM ================= */

document.querySelectorAll(".toggle-pw").forEach(function (btn) {
    btn.addEventListener("click", function () {
        var target = document.getElementById(btn.dataset.target);
        if (!target) return;
        if (target.type === "password") {
            target.type = "text";
            btn.innerHTML = '<i data-lucide="eye-off"></i>';
        } else {
            target.type = "password";
            btn.innerHTML = '<i data-lucide="eye"></i>';
        }
        if (typeof lucide !== "undefined") lucide.createIcons();
    });
});

var newPwInput = document.getElementById("newPassword");
if (newPwInput) {
    newPwInput.addEventListener("input", function () {
        var val   = newPwInput.value;
        var score = 0;
        if (val.length >= 8)           score++;
        if (/[A-Z]/.test(val))         score++;
        if (/[0-9]/.test(val))         score++;
        if (/[^A-Za-z0-9]/.test(val))  score++;
        var fill   = document.getElementById("pwStrengthFill");
        var label  = document.getElementById("pwStrengthLabel");
        if (!fill || !label) return;
        var labels = ["", "Weak", "Fair", "Good", "Strong"];
        var colors = ["", "#B42318", "#E8900A", "#2A7A3A", "#1A5C2A"];
        var widths = ["0%", "25%", "50%", "75%", "100%"];
        fill.style.width      = widths[score];
        fill.style.background = colors[score];
        label.textContent     = score > 0 ? labels[score] : "";
        label.style.color     = colors[score];
    });
}

var confirmPwInput = document.getElementById("confirmPassword");
if (confirmPwInput) {
    confirmPwInput.addEventListener("input", function () {
        var msg   = document.getElementById("pwMatchMsg");
        var newPw = document.getElementById("newPassword");
        if (!msg || !newPw) return;
        if (confirmPwInput.value === "") { msg.textContent = ""; return; }
        if (confirmPwInput.value === newPw.value) {
            msg.textContent = "✓ Passwords match";
            msg.style.color = "#207A3A";
        } else {
            msg.textContent = "✗ Passwords do not match";
            msg.style.color = "#B42318";
        }
    });
}

var pwForm = document.getElementById("passwordForm");
if (pwForm) {
    pwForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var current = document.getElementById("currentPassword").value;
        var newPw   = document.getElementById("newPassword").value;
        var confirm = document.getElementById("confirmPassword").value;
        if (!current) { alert("Please enter your current password."); return; }
        if (newPw.length < 8) { alert("New password must be at least 8 characters."); return; }
        if (newPw !== confirm) { alert("New passwords do not match."); return; }
        pwForm.reset();
        var fill  = document.getElementById("pwStrengthFill");
        var label = document.getElementById("pwStrengthLabel");
        var match = document.getElementById("pwMatchMsg");
        if (fill)  fill.style.width    = "0%";
        if (label) label.textContent   = "";
        if (match) match.textContent   = "";
        showToast("Password updated successfully.");
    });
}

/* ================= USERS TABLE ================= */

var usersTable = document.getElementById("usersTable");
if (usersTable) initializeGenericSearch("userSearch", "usersTable");

var rowBeingEdited  = null;
var rowBeingDeleted = null;

initializeModal("openAddUserModal", "closeAddUserModal", "cancelAddUser", "addUserModal", "addUserForm");

var addUserForm = document.getElementById("addUserForm");
if (addUserForm) {
    addUserForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var name      = document.getElementById("newUserName").value;
        var username  = document.getElementById("newUserUsername").value;
        var email     = document.getElementById("newUserEmail").value;
        var role      = document.getElementById("newUserRole").value;
        var status    = document.getElementById("newUserStatus").value;
        var initial   = name.charAt(0).toUpperCase();
        var avClass   = role === "admin" ? "admin-av" : "supplier-av";
        var roleBadge = role === "admin" ? "admin-role" : "supplier-role";
        var roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
        var today     = new Date().toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
        var statusClass = status === "Active" ? "completed" : "pending";
        var tbody = document.querySelector("#usersTable tbody");
        if (!tbody) return;
        var row = document.createElement("tr");
        row.dataset.name = name; row.dataset.username = username;
        row.dataset.email = email; row.dataset.role = role;
        row.dataset.status = status; row.dataset.date = today;
        row.innerHTML =
            "<td><div class='user-cell'><div class='user-avatar-sm " + avClass + "'>" + initial + "</div>" + name + "</div></td>" +
            "<td>" + username + "</td><td>" + email + "</td>" +
            "<td><span class='role-badge " + roleBadge + "'>" + roleLabel + "</span></td>" +
            "<td><span class='status-badge " + statusClass + "'>" + status + "</span></td>" +
            "<td>" + today + "</td>" +
            "<td class='action-cell'>" +
                "<button class='action-btn view edit-user-btn' type='button'><i data-lucide='pencil'></i></button>" +
                "<button class='action-btn view delete-user-btn' type='button'><i data-lucide='trash-2'></i></button>" +
            "</td>";
        tbody.appendChild(row);
        if (typeof lucide !== "undefined") lucide.createIcons();
        document.getElementById("addUserModal").classList.remove("show");
        addUserForm.reset();
        showToast("User \"" + name + "\" created.");
    });
}

initializeModal("", "closeEditUserModal", "cancelEditUser", "editUserModal", "editUserForm");

if (usersTable) {
    usersTable.addEventListener("click", function (e) {
        var editBtn   = e.target.closest(".edit-user-btn");
        var deleteBtn = e.target.closest(".delete-user-btn");
        var row       = e.target.closest("tr");
        if (!row) return;
        if (editBtn) {
            rowBeingEdited = row;
            var d = row.dataset;
            document.getElementById("editUserSubtitle").textContent = "Editing: " + d.name;
            document.getElementById("editUserName").value     = d.name;
            document.getElementById("editUserUsername").value = d.username;
            document.getElementById("editUserEmail").value    = d.email;
            document.getElementById("editUserRole").value     = d.role;
            document.getElementById("editUserStatus").value   = d.status;
            document.getElementById("editUserPassword").value = "";
            document.getElementById("editUserModal").classList.add("show");
        }
        if (deleteBtn) {
            rowBeingDeleted = row;
            var name = row.dataset.name || "this user";
            document.getElementById("deleteUserMsg").textContent = "Are you sure you want to delete \"" + name + "\"?";
            document.getElementById("deleteUserModal").classList.add("show");
        }
    });
}

var editUserForm = document.getElementById("editUserForm");
if (editUserForm) {
    editUserForm.addEventListener("submit", function (e) {
        e.preventDefault();
        if (!rowBeingEdited) return;
        var name      = document.getElementById("editUserName").value;
        var username  = document.getElementById("editUserUsername").value;
        var email     = document.getElementById("editUserEmail").value;
        var role      = document.getElementById("editUserRole").value;
        var status    = document.getElementById("editUserStatus").value;
        var initial   = name.charAt(0).toUpperCase();
        var avClass   = role === "admin" ? "admin-av" : "supplier-av";
        var roleBadge = role === "admin" ? "admin-role" : "supplier-role";
        var roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
        var statusClass = status === "Active" ? "completed" : "pending";
        rowBeingEdited.dataset.name = name; rowBeingEdited.dataset.username = username;
        rowBeingEdited.dataset.email = email; rowBeingEdited.dataset.role = role;
        rowBeingEdited.dataset.status = status;
        var cells = rowBeingEdited.cells;
        cells[0].innerHTML = "<div class='user-cell'><div class='user-avatar-sm " + avClass + "'>" + initial + "</div>" + name + "</div>";
        cells[1].textContent = username; cells[2].textContent = email;
        cells[3].innerHTML = "<span class='role-badge " + roleBadge + "'>" + roleLabel + "</span>";
        cells[4].innerHTML = "<span class='status-badge " + statusClass + "'>" + status + "</span>";
        if (typeof lucide !== "undefined") lucide.createIcons();
        document.getElementById("editUserModal").classList.remove("show");
        editUserForm.reset();
        rowBeingEdited = null;
        showToast("User updated successfully.");
    });
}

["closeEditUserModal","cancelEditUser"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener("click", function () {
        document.getElementById("editUserModal").classList.remove("show");
    });
});

var editUserModal = document.getElementById("editUserModal");
if (editUserModal) editUserModal.addEventListener("click", function (e) { if (e.target === this) this.classList.remove("show"); });

var confirmDeleteUser = document.getElementById("confirmDeleteUser");
if (confirmDeleteUser) {
    confirmDeleteUser.addEventListener("click", function () {
        if (rowBeingDeleted) {
            var name = rowBeingDeleted.dataset.name;
            rowBeingDeleted.remove();
            rowBeingDeleted = null;
            document.getElementById("deleteUserModal").classList.remove("show");
            showToast("User \"" + name + "\" deleted.");
        }
    });
}

["closeDeleteUserModal","cancelDeleteUser"].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener("click", function () {
        document.getElementById("deleteUserModal").classList.remove("show");
        rowBeingDeleted = null;
    });
});

var deleteUserModal = document.getElementById("deleteUserModal");
if (deleteUserModal) deleteUserModal.addEventListener("click", function (e) {
    if (e.target === this) { this.classList.remove("show"); rowBeingDeleted = null; }
});

/* ================= DOMContentLoaded ================= */

document.addEventListener("DOMContentLoaded", function () {
    // Initialize clients module (search only — CRUD handled by Laravel)
    initializeClientsModule();

    // Tab activation from session (used on settings page)
    if (typeof ACTIVE_TAB !== 'undefined') {
        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === ACTIVE_TAB);
        });
        document.querySelectorAll('.emp-tab-content').forEach(function (pane) {
            pane.classList.toggle('active', pane.id === 'tab-' + ACTIVE_TAB);
        });
    }
});