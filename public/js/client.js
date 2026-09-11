/* ================= NUMBER INPUT: DISABLE SCROLL-TO-CHANGE =================
   Mouse-wheel scrolling over a focused number input silently increments/decrements
   its value in Chrome/Firefox. Blur it the instant a wheel event fires so the page
   scrolls normally instead and the value stays untouched. */
document.addEventListener('wheel', function (e) {
    if (document.activeElement && document.activeElement.tagName === 'INPUT' && document.activeElement.type === 'number') {
        document.activeElement.blur();
    }
}, { passive: true });

/* ================= DATE TIME ================= */

function updateDateTime() {
    var dateEl = document.getElementById("headerDate");
    var timeEl = document.getElementById("headerTime");
    if (!dateEl || !timeEl) return;

    var now = new Date();
    dateEl.textContent = now.toLocaleDateString("en-US", { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    timeEl.textContent = now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", second: "2-digit", hour12: true });
}

/* ================= INIT ================= */

updateDateTime();
setInterval(updateDateTime, 1000);

if (typeof lucide !== "undefined") {
    lucide.createIcons();
}
