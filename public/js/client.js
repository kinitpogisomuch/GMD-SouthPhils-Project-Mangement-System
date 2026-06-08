/* ================= DATE TIME ================= */

function updateDateTime() {
    var dateEl = document.getElementById("headerDate");
    var timeEl = document.getElementById("headerTime");
    if (!dateEl || !timeEl) return;

    var now = new Date();
    dateEl.textContent = now.toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    timeEl.textContent = now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit", hour12: true });
}

/* ================= CLIENT PROFILE DROPDOWN ================= */

function initializeClientDropdown() {
    var dropdown = document.querySelector(".client-dropdown");
    var button   = document.getElementById("clientDropdownBtn");
    if (!dropdown || !button) return;

    button.addEventListener("click", function (e) {
        e.stopPropagation();
        var notifDropdown = document.querySelector(".notification-dropdown");
        if (notifDropdown) notifDropdown.classList.remove("open");
        dropdown.classList.toggle("open");
    });

    dropdown.addEventListener("click", function (e) { e.stopPropagation(); });
}

/* ================= NOTIFICATION DROPDOWN ================= */

function initializeNotificationDropdown() {
    var dropdown = document.querySelector(".notification-dropdown");
    var button   = document.getElementById("notificationDropdownBtn");
    if (!dropdown || !button) return;

    button.addEventListener("click", function (e) {
        e.stopPropagation();
        var clientDropdown = document.querySelector(".client-dropdown");
        if (clientDropdown) clientDropdown.classList.remove("open");
        dropdown.classList.toggle("open");
    });

    dropdown.addEventListener("click", function (e) { e.stopPropagation(); });
}

/* ================= OUTSIDE CLICK CLOSE ================= */

document.addEventListener("click", function () {
    var clientDropdown = document.querySelector(".client-dropdown");
    var notifDropdown  = document.querySelector(".notification-dropdown");
    if (clientDropdown) clientDropdown.classList.remove("open");
    if (notifDropdown)  notifDropdown.classList.remove("open");
});

/* ================= INIT ================= */

updateDateTime();
setInterval(updateDateTime, 1000);
initializeClientDropdown();
initializeNotificationDropdown();

if (typeof lucide !== "undefined") {
    lucide.createIcons();
}
