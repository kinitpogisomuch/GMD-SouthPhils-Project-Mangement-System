<header class="admin-header">
    <div class="admin-header-left">
        <button class="sidebar-toggle-btn" type="button" id="sidebarToggleBtn" title="Toggle menu">
            <i data-lucide="menu"></i>
        </button>
        <div>
            <div class="system-title">GMD South Phils</div>
            <div class="system-subtitle">Project Management</div>
        </div>
    </div>

    <div class="admin-header-center">
        <div class="header-clock">
            <i data-lucide="clock-3" class="header-clock-icon"></i>
            <div class="header-clock-text">
                <span class="header-clock-date" id="headerDate">—</span>
                <span class="header-clock-time" id="headerTime">—</span>
            </div>
        </div>
    </div>

    <div class="admin-header-right">

        <div class="notification-dropdown" id="notificationDropdown">
            <button class="notification-btn" type="button" title="Notifications" id="notificationDropdownBtn">
                <i data-lucide="bell"></i>
                <span class="notification-dot" id="notificationDot" style="display:none;"></span>
                <span class="notification-count-badge" id="notificationCountBadge" style="display:none;"></span>
            </button>

            <div class="notification-dropdown-menu" id="notificationDropdownMenu">
                <div class="notification-header">
                    <h4>Notifications</h4>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span id="notificationUnreadLabel" style="display:none;"></span>
                        <button type="button" id="markAllReadBtn"
                            style="font-size:11px;font-weight:700;color:var(--muted);background:none;border:none;cursor:pointer;padding:0;"
                            title="Mark all as read">Mark all read</button>
                    </div>
                </div>

                <div id="notificationList">
                    <div class="notification-empty" id="notificationLoading">
                        <i data-lucide="loader" style="width:20px;height:20px;color:var(--muted);"></i>
                    </div>
                </div>

                <a href="{{ route('admin.notifications') }}" class="view-all-notifications" id="viewAllLink" style="display:none;">
                    View all notifications
                </a>
            </div>
        </div>

        <div class="admin-dropdown">
            <button class="admin-profile-btn" type="button" id="adminDropdownBtn">
                <div class="admin-avatar">
                    @if(session('profile_photo'))
                        <img src="{{ session('profile_photo') }}" alt="Profile"
                             style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    @else
                        <span style="font-size:16px;font-weight:900;color:var(--dark);">
                            {{ strtoupper(substr(session('full_name', 'A'), 0, 1)) }}
                        </span>
                    @endif
                </div>

                <div class="admin-details">
                    <div class="admin-name">
                        {{ session('full_name', 'Admin') }}
                    </div>
                    <div class="admin-role">Administrator</div>
                </div>

                <i data-lucide="chevron-down" class="dropdown-arrow"></i>
            </button>

            <div class="admin-dropdown-menu" id="adminDropdownMenu">
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var sidebar   = document.querySelector('.admin-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });
    }
});

(function () {
    function updateClock() {
        var d = document.getElementById('headerDate');
        var t = document.getElementById('headerTime');
        if (!d || !t) return;
        var now = new Date();
        d.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        t.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
    }
    updateClock();
    setInterval(updateClock, 1000);
})();

(function () {
    const RECENT_URL   = '{{ route("admin.notifications.recent") }}';
    const READ_ALL_URL = '{{ route("admin.notifications.read-all") }}';
    const CSRF         = '{{ csrf_token() }}';

    const iconMap = {
        project_created:    'folder-kanban',
        progress_requested: 'bell',
        progress_submitted: 'send',
        revision_requested: 'alert-triangle',
        revision_submitted: 'refresh-cw',
        progress_approved:  'check-circle',
        phase_advanced:     'layers',
        project_completed:  'award',
        pending_review:     'clock',
    };

    const priorityClass = {
        info:    'notif-icon-info',
        warning: 'notif-icon-warning',
        success: 'notif-icon-success',
    };

    function loadNotifications() {
        fetch(RECENT_URL)
            .then(r => r.json())
            .then(data => {
                renderNotifications(data.notifications, data.unread_count);
            })
            .catch(() => {});
    }

    function renderNotifications(list, unreadCount) {
        const dot       = document.getElementById('notificationDot');
        const badge     = document.getElementById('notificationCountBadge');
        const label     = document.getElementById('notificationUnreadLabel');
        const container = document.getElementById('notificationList');
        const viewAll   = document.getElementById('viewAllLink');

        // Update bell badge
        if (unreadCount > 0) {
            dot.style.display   = 'block';
            badge.style.display = 'flex';
            badge.textContent   = unreadCount > 99 ? '99+' : unreadCount;
            label.style.display = 'inline';
            label.textContent   = unreadCount + ' unread';
        } else {
            dot.style.display   = 'none';
            badge.style.display = 'none';
            label.style.display = 'none';
        }

        if (!list || list.length === 0) {
            container.innerHTML = '<div class="notification-empty"><i data-lucide="bell-off" style="width:22px;height:22px;color:var(--muted);display:block;margin:0 auto 6px;"></i><span>No notifications</span></div>';
            if (window.lucide) lucide.createIcons();
            viewAll.style.display = 'none';
            return;
        }

        container.innerHTML = list.map(n => {
            const icon  = iconMap[n.notification_type] || 'bell';
            const cls   = priorityClass[n.priority] || 'notif-icon-info';
            const unread = !n.is_read ? ' unread' : '';
            const url   = n.action_url || '#';
            return `<div class="notification-item${unread}" data-id="${n.id}" data-url="${url}" onclick="handleNotifClick(this)">
                <div class="notification-icon ${cls}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="notification-text">
                    <strong>${escapeHtml(n.title)}</strong>
                    <p>${escapeHtml(n.message.split('\n')[0])}</p>
                    <span class="notif-time">${escapeHtml(n.created_at)}</span>
                </div>
                ${!n.is_read ? '<span class="notif-unread-dot"></span>' : ''}
            </div>`;
        }).join('');

        if (window.lucide) lucide.createIcons();
        viewAll.style.display = 'block';
    }

    window.handleNotifClick = function(el) {
        const id  = el.dataset.id;
        const url = el.dataset.url;

        fetch(`/admin/notifications/${id}/read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        }).finally(() => {
            if (url && url !== '#') window.location.href = url;
            else loadNotifications();
        });
    };

    document.getElementById('markAllReadBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        fetch(READ_ALL_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        }).then(() => loadNotifications());
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Initial load + polling every 30s
    loadNotifications();
    setInterval(loadNotifications, 30000);

    // Reload when dropdown opens
    document.getElementById('notificationDropdownBtn').addEventListener('click', function() {
        loadNotifications();
    });
})();
</script>
