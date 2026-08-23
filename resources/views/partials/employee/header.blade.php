<header class="employee-header">
    <div class="employee-header-left">
        <div>
            <div class="system-title">GMD South Phils</div>
            <div class="system-subtitle">EMPLOYEE PORTAL</div>
        </div>
    </div>

    <nav class="employee-header-nav">
        <a href="{{ route('employee.dashboard') }}"
           class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('employee.projects') }}"
           class="{{ request()->routeIs(['employee.projects']) ? 'active' : '' }}">
            <i data-lucide="folder-kanban"></i>
            <span>Projects</span>
        </a>
        <a href="{{ route('employee.project_materials') }}"
           class="{{ request()->routeIs(['employee.project_materials', 'employee.project_materials.detail', 'employee.material_usage.detail']) ? 'active' : '' }}">
            <i data-lucide="package"></i>
            <span>Project Materials</span>
        </a>
        <a href="{{ route('employee.salary') }}"
           class="{{ request()->routeIs('employee.salary') ? 'active' : '' }}">
            <i data-lucide="wallet"></i>
            <span>My Salary</span>
        </a>
    </nav>

    <div class="employee-header-right">

        <!-- Notification Dropdown -->
        <div class="notification-dropdown" id="notificationDropdown">
            <button class="notification-btn" id="notificationDropdownBtn">
                <i data-lucide="bell"></i>
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
                <a href="{{ route('employee.notifications') }}" class="view-all-notifications" id="viewAllLink" style="display:none;">
                    View all notifications
                </a>
            </div>
        </div>

        <!-- Employee Dropdown with Settings & Logout -->
        <div class="employee-dropdown">
            <button class="employee-profile-btn" id="employeeDropdownBtn">
                <div class="employee-avatar">
                    @if(session('profile_photo'))
                        <img src="{{ session('profile_photo') }}" alt="Profile"
                             style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <span style="font-size:16px;font-weight:900;color:var(--dark);">
                            {{ strtoupper(substr(session('name', 'E'), 0, 1)) }}
                        </span>
                    @endif
                </div>
                <i data-lucide="chevron-down" class="dropdown-arrow"></i>
            </button>
            <div class="employee-dropdown-menu">
                <div class="employee-dropdown-profile">
                    <div class="employee-dropdown-profile-avatar">
                        @if(session('profile_photo'))
                            <img src="{{ session('profile_photo') }}" alt="Profile">
                        @else
                            <span>{{ strtoupper(substr(session('name', 'E'), 0, 1)) }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="employee-dropdown-profile-name">{{ session('name', 'Employee') }}</div>
                        <div class="employee-dropdown-profile-role">Employee</div>
                    </div>
                </div>

                <div class="employee-dropdown-divider"></div>

                <a href="{{ route('employee.settings') }}" class="employee-dropdown-item">
                    <span class="employee-dropdown-icon"><i data-lucide="settings"></i></span>
                    <span class="employee-dropdown-label">Settings</span>
                    <i data-lucide="chevron-right" class="employee-dropdown-chevron"></i>
                </a>

                <div class="employee-dropdown-divider"></div>

                <a href="{{ route('logout') }}" class="employee-dropdown-item danger"
                   onclick="event.preventDefault(); document.getElementById('empLogoutModal').classList.add('show');">
                    <span class="employee-dropdown-icon"><i data-lucide="log-out"></i></span>
                    <span class="employee-dropdown-label">Log out</span>
                </a>
                <form id="employee-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                    @csrf
                </form>
            </div>
        </div>

    </div>
</header>

{{-- Floating chat launcher --}}
<div style="position:fixed;bottom:24px;right:24px;z-index:9997;" id="chatPopupWrap">
    <button type="button" id="chatPopupBtn" title="Messages"
        style="position:relative;width:52px;height:52px;border-radius:50%;background:var(--dark);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 26px rgba(0,0,0,.28);transition:transform .18s ease;"
        onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform='scale(1)'">
        <i data-lucide="message-square" style="width:22px;height:22px;"></i>
        <span id="headerMessagesBadge" class="notification-count-badge" style="display:none;"></span>
    </button>

    {{-- Chat list dropdown --}}
    <div id="chatPopupDropdown" style="display:none;position:absolute;bottom:64px;right:0;width:360px;max-width:calc(100vw - 48px);background:linear-gradient(180deg, var(--white) 0%, #fafafa 100%);border:1px solid var(--border);border-radius:20px;box-shadow:0 18px 40px rgba(0,0,0,0.14);z-index:600;overflow:hidden;">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 16px 12px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:32px;height:32px;background:var(--dark);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="message-square" style="width:16px;height:16px;color:#fff;"></i>
                </div>
                <span style="font-size:15px;font-weight:800;color:var(--dark);">Messages</span>
            </div>
            <a href="{{ route('employee.messages') }}" style="font-size:12px;font-weight:700;color:var(--dark);text-decoration:none;background:var(--cream-soft);padding:5px 12px;border-radius:999px;border:1px solid var(--border);">See all</a>
        </div>

        {{-- Search --}}
        <div style="padding:12px 14px;">
            <div style="display:flex;align-items:center;gap:8px;background:var(--cream-soft);border-radius:12px;padding:8px 14px;border:1px solid var(--border);">
                <i data-lucide="search" style="width:14px;height:14px;color:var(--muted);flex-shrink:0;"></i>
                <input id="chatContactSearch" type="text" placeholder="Search contact..."
                    style="flex:1;border:none;background:transparent;font-size:13px;outline:none;color:var(--dark);"
                    oninput="filterChatContacts(this.value)">
            </div>
        </div>

        {{-- Contact list --}}
        <div id="chatContactList" style="max-height:320px;overflow-y:auto;">
            <div style="text-align:center;padding:32px;color:var(--muted);font-size:13px;">Loading...</div>
        </div>

        {{-- Footer --}}
        <div style="padding:10px 14px;border-top:1px solid var(--border);background:var(--cream-soft);">
            <a href="{{ route('employee.messages') }}" style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:700;color:var(--dark);text-decoration:none;">
                <i data-lucide="external-link" style="width:13px;height:13px;"></i>
                Open full messages
            </a>
        </div>
    </div>
</div>

{{-- Floating chat window — matches full message thread UI --}}
<div id="chatFloatWindow" style="display:none;position:fixed;bottom:0;right:24px;width:360px;height:480px;border-radius:14px 14px 0 0;box-shadow:0 -2px 30px rgba(0,0,0,.18),0 0 0 1px rgba(0,0,0,.08);z-index:9999;flex-direction:column;overflow:hidden;background:#fff;">

    {{-- Header --}}
    <div style="background:#fff;border-bottom:1px solid #eee;padding:10px 14px;display:flex;align-items:center;gap:10px;">
        <div id="chatWinAvatarWrap" style="width:40px;height:40px;border-radius:50%;background:var(--dark);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;flex-shrink:0;overflow:hidden;">
            <span id="chatWinAvatar">?</span>
        </div>
        <div style="flex:1;min-width:0;">
            <div id="chatWinName" style="font-size:14px;font-weight:800;color:#050505;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2;">—</div>
            <div id="chatWinRole" style="font-size:11px;color:#888;">—</div>
        </div>
        <button onclick="closeChatWindow()" title="Close" style="width:32px;height:32px;border-radius:50%;background:#f0f2f5;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#050505;">
            <i data-lucide="x" style="width:16px;height:16px;"></i>
        </button>
    </div>

    {{-- Messages --}}
    <div id="chatWinMessages" style="flex:1;overflow-y:auto;padding:14px 12px;display:flex;flex-direction:column;gap:2px;background:#f7f8fa;"></div>

    {{-- Hidden file inputs --}}
    <input type="file" id="chatCameraInput"     accept="image/*" capture="environment" style="display:none;" onchange="sendChatFile(this)">
    <input type="file" id="chatImageInput"      accept="image/*" multiple style="display:none;" onchange="sendChatFile(this)">
    <input type="file" id="chatAttachInput"     multiple style="display:none;" onchange="sendChatFile(this)">

    {{-- Input bar --}}
    <div style="background:#fff;border-top:1px solid #eee;padding:6px 10px;display:flex;align-items:center;gap:4px;flex-shrink:0;">
        <button type="button" onclick="document.getElementById('chatCameraInput').click()"
            title="Camera"
            style="width:34px;height:34px;border-radius:50%;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#65676b;flex-shrink:0;transition:background .15s;"
            onmouseover="this.style.background='#f0f2f5'" onmouseout="this.style.background='none'">
            <i data-lucide="camera" style="width:17px;height:17px;"></i>
        </button>
        <button type="button" onclick="document.getElementById('chatImageInput').click()"
            title="Send image"
            style="width:34px;height:34px;border-radius:50%;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#65676b;flex-shrink:0;transition:background .15s;"
            onmouseover="this.style.background='#f0f2f5'" onmouseout="this.style.background='none'">
            <i data-lucide="image" style="width:17px;height:17px;"></i>
        </button>
        <button type="button" onclick="document.getElementById('chatAttachInput').click()"
            title="Attach file"
            style="width:34px;height:34px;border-radius:50%;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#65676b;flex-shrink:0;transition:background .15s;"
            onmouseover="this.style.background='#f0f2f5'" onmouseout="this.style.background='none'">
            <i data-lucide="paperclip" style="width:17px;height:17px;"></i>
        </button>
        <div style="flex:1;background:#f0f2f5;border-radius:20px;padding:7px 14px;display:flex;align-items:center;">
            <textarea id="chatWinInput" placeholder="Type a message..." rows="1"
                style="flex:1;border:none;background:transparent;resize:none;font-size:13px;font-family:inherit;outline:none;max-height:72px;overflow-y:auto;line-height:1.4;color:#050505;display:block;width:100%;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChatMsg();}"></textarea>
        </div>
        <button type="button" onclick="sendChatMsg()" title="Send"
            style="width:36px;height:36px;border-radius:50%;background:var(--dark);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;"
            onmouseover="this.style.background='var(--dark-deep)'" onmouseout="this.style.background='var(--dark)'">
            <i data-lucide="send" style="width:15px;height:15px;"></i>
        </button>
    </div>
</div>

<script>
(function () {
    const RECENT_URL   = '{{ route("employee.notifications.recent") }}';
    const READ_ALL_URL = '{{ route("employee.notifications.read-all") }}';
    const CSRF         = '{{ csrf_token() }}';

    // Kept in sync with Notification::ICON_MAP (app/Models/Notification.php)
    const iconMap = {
        project_created:                 'folder-kanban',
        progress_requested:              'bell',
        progress_submitted:              'send',
        revision_requested:              'alert-triangle',
        revision_submitted:              'refresh-cw',
        progress_approved:               'check-circle',
        phase_advanced:                  'layers',
        project_completed:               'award',
        pending_review:                  'clock',
        material_added:                  'package-plus',
        material_updated:                'package',
        material_removed:                'package-minus',
        material_requested:              'package-x',
        material_usage_logged:           'clipboard-check',
        labor_updated:                   'users',
        shop_drawing_submitted:          'file-text',
        shop_drawing_approved:           'check-circle',
        shop_drawing_revision_requested: 'alert-triangle',
        quotation_sent:                  'receipt',
        fund_released:                   'credit-card',
        fund_replenished:                'refresh-cw',
    };

    const priorityClass = {
        info:    'notif-icon-info',
        warning: 'notif-icon-warning',
        success: 'notif-icon-success',
    };

    function loadNotifications() {
        fetch(RECENT_URL)
            .then(r => r.json())
            .then(data => renderNotifications(data.notifications, data.unread_count))
            .catch(() => {});
    }

    function renderNotifications(list, unreadCount) {
        const badge     = document.getElementById('notificationCountBadge');
        const label     = document.getElementById('notificationUnreadLabel');
        const container = document.getElementById('notificationList');
        const viewAll   = document.getElementById('viewAllLink');

        if (unreadCount > 0) {
            badge.style.display = 'flex';
            badge.textContent   = unreadCount > 99 ? '99+' : unreadCount;
            label.style.display = 'inline';
            label.textContent   = unreadCount + ' unread';
        } else {
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
        fetch(`/employee/notifications/${id}/read`, {
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

    loadNotifications();
    setInterval(loadNotifications, 30000);
    document.getElementById('notificationDropdownBtn').addEventListener('click', loadNotifications);
})();

(function () {
    const employeeDropdown     = document.querySelector('.employee-dropdown');
    const employeeBtn          = document.getElementById('employeeDropdownBtn');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const notificationBtn      = document.getElementById('notificationDropdownBtn');

    if (employeeBtn && employeeDropdown) {
        employeeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (notificationDropdown) notificationDropdown.classList.remove('open');
            employeeDropdown.classList.toggle('open');
        });
    }

    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (employeeDropdown) employeeDropdown.classList.remove('open');
            notificationDropdown.classList.toggle('open');
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('a') || e.target.closest('button')) return;
        if (employeeDropdown) employeeDropdown.classList.remove('open');
        if (notificationDropdown) notificationDropdown.classList.remove('open');
    });
})();

(function () {
    const UNREAD_MESSAGES_URL = '{{ route("employee.messages.unread_count") }}';

    function loadUnreadMessages() {
        fetch(UNREAD_MESSAGES_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const headerBadge = document.getElementById('headerMessagesBadge');
                if (!headerBadge) return;
                if (data.count > 0) {
                    headerBadge.style.display = 'flex';
                    headerBadge.textContent = data.count > 99 ? '99+' : data.count;
                } else {
                    headerBadge.style.display = 'none';
                }
            })
            .catch(() => {});
    }

    loadUnreadMessages();
    setInterval(loadUnreadMessages, 15000);
    window.__setMsgBadgeRefresh = function(fn) { window.__employeeLoadUnreadMessages = fn; };
})();

// ─── Chat Popup ────────────────────────────────────────────────────────────
(function () {
    var CONTACTS_URL = '{{ route("employee.messages.contacts") }}';
    var THREAD_URL   = '{{ url("employee/messages/thread") }}';
    var SEND_URL     = '{{ route("employee.messages.send") }}';
    var CSRF         = '{{ csrf_token() }}';

    var chatContact = null; // { type, id, name }
    var popupOpen   = false;

    var wrap     = document.getElementById('chatPopupWrap');
    var btn      = document.getElementById('chatPopupBtn');
    var dropdown = document.getElementById('chatPopupDropdown');
    var window_  = document.getElementById('chatFloatWindow');
    var msgList  = document.getElementById('chatWinMessages');

    window.closeChatPopup = function () {
        popupOpen = false;
        dropdown.style.display = 'none';
    };

    // Toggle dropdown
    btn && btn.addEventListener('click', function (e) {
        e.stopPropagation();
        popupOpen = !popupOpen;
        dropdown.style.display = popupOpen ? 'block' : 'none';
        if (popupOpen) {
            var notificationDropdown = document.querySelector('.notification-dropdown');
            var employeeDropdown     = document.querySelector('.employee-dropdown');
            if (notificationDropdown) notificationDropdown.classList.remove('open');
            if (employeeDropdown) employeeDropdown.classList.remove('open');
            loadContacts();
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#chatPopupWrap') && !e.target.closest('#chatFloatWindow')) {
            popupOpen = false;
            dropdown.style.display = 'none';
            var s = document.getElementById('chatContactSearch');
            if (s) { s.value = ''; filterChatContacts(''); }
        }
    });

    function loadContacts() {
        var list = document.getElementById('chatContactList');
        list.innerHTML = '<div style="text-align:center;padding:28px;color:var(--muted);font-size:13px;">Loading...</div>';
        fetch(CONTACTS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(contacts) {
                if (!contacts.length) {
                    list.innerHTML = '<div style="text-align:center;padding:28px;color:var(--muted);font-size:13px;">No conversations yet.</div>';
                    return;
                }
                list.innerHTML = contacts.map(function(c, idx) {
                    var parts = (c.name || '?').trim().split(/\s+/);
                    var init  = parts.length >= 2
                        ? parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase()
                        : parts[0].substring(0, 2).toUpperCase();
                    var hasMsg  = !!c.last_message;
                    var unread  = c.unread > 0
                        ? '<span style="min-width:18px;height:18px;background:#ef4444;color:#fff;border-radius:999px;font-size:10px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;flex-shrink:0;">' + c.unread + '</span>'
                        : '';
                    var preview = hasMsg
                        ? c.last_message.substring(0,42) + (c.last_message.length>42?'…':'')
                        : 'No messages yet';
                    var photo   = c.profile_photo
                        ? '<img src="'+c.profile_photo+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
                        : '<span style="font-size:12px;font-weight:900;color:#fff;letter-spacing:.5px;">'+init+'</span>';
                    var roleLabel = (c.role || c.type || '').toUpperCase();

                    return '<div onclick="openChatWith(\''+c.type+'\','+c.id+',\''+escHtml(c.name)+'\')" data-name="'+c.name.toLowerCase()+'" '
                        + 'style="display:flex;align-items:center;gap:12px;padding:11px 16px;cursor:pointer;transition:background .12s;border-bottom:1px solid var(--border);" '
                        + 'onmouseover="this.style.background=\'var(--cream-soft)\'" onmouseout="this.style.background=\'\'">'

                        // Avatar
                        + '<div style="width:44px;height:44px;min-width:44px;border-radius:50%;background:linear-gradient(135deg,var(--dark) 0%,var(--dark-soft) 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">'
                        +   photo
                        + '</div>'

                        // Info
                        + '<div style="flex:1;min-width:0;">'
                        +   '<div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">'
                        +     '<span style="font-size:13.5px;font-weight:700;color:var(--dark);">'+escHtml(c.name)+'</span>'
                        +     '<span style="font-size:10.5px;font-weight:600;color:var(--muted);letter-spacing:.04em;">'+roleLabel+'</span>'
                        +     (unread ? '<span style="margin-left:auto;">'+unread+'</span>' : '')
                        +   '</div>'
                        +   '<div style="font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+preview+'</div>'
                        + '</div>'
                        + '</div>';
                }).join('');
            });
    }

    window.filterChatContacts = function(q) {
        q = (q || '').toLowerCase();
        document.querySelectorAll('#chatContactList [data-name]').forEach(function(el) {
            el.style.display = (!q || el.dataset.name.includes(q)) ? '' : 'none';
        });
    };

    window.openChatWith = function(type, id, name) {
        chatContact = { type: type, id: id, name: name };
        dropdown.style.display = 'none';
        popupOpen = false;

        try { localStorage.setItem('gmd_employee_open_chat', JSON.stringify(chatContact)); } catch (e) {}

        document.getElementById('chatWinName').textContent = name;
        document.getElementById('chatWinRole').textContent = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('chatWinAvatar').textContent = name.charAt(0).toUpperCase();
        window_.style.display = 'flex';
        if (wrap) wrap.style.display = 'none';
        if (window.lucide) lucide.createIcons();
        msgList.innerHTML = '<div style="text-align:center;padding:20px;color:var(--muted);font-size:12px;">Loading...</div>';

        fetch(THREAD_URL + '/' + type + '/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                renderMessages(data.messages || []);
                if (window.__employeeLoadUnreadMessages) window.__employeeLoadUnreadMessages();
            });
    };

    function renderMessages(messages) {
        if (!messages.length) {
            msgList.innerHTML = '<div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#888;font-size:13px;"><img src="{{ asset("images/wave-hand.png") }}" alt="wave" style="width:48px;height:48px;object-fit:contain;opacity:.7;margin-bottom:10px;"><div>No messages yet.</div><div>Say hello!</div></div>';
            return;
        }
        var myInit = '{{ strtoupper(substr(session("name", "E"), 0, 1)) }}';
        var html = '';
        messages.forEach(function(m, i) {
            var isMe     = !!m.is_mine;
            var next     = i < messages.length - 1 ? messages[i + 1] : null;
            var sameNext = next && (!!next.is_mine) === isMe;
            var mb       = sameNext ? '2px' : '8px';
            var timeId   = 'chatMsgTime-' + (m.id || i) + '-' + i;
            var timeLabel = [m.date_label, m.time].filter(Boolean).join(' · ');

            // Images / attachments
            var imgHtml = '', fileHtml = '';
            if (m.attachments && m.attachments.length) {
                m.attachments.forEach(function(a) {
                    if (a.url && /\.(jpg|jpeg|png|gif|webp)/i.test(a.url)) {
                        imgHtml += '<img src="' + a.url + '" style="max-width:180px;width:100%;border-radius:10px;display:block;margin-top:4px;cursor:pointer;" onclick="window.open(\'' + a.url + '\',\'_blank\')">';
                    } else {
                        fileHtml += '<div style="font-size:11px;margin-top:4px;opacity:.7;">📎 ' + escHtml(a.name || 'File') + '</div>';
                    }
                });
            }

            var avatarStyle = 'width:26px;height:26px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;';

            if (isMe) {
                // SENT — right side
                html += '<div style="display:flex;justify-content:flex-end;align-items:flex-end;gap:5px;margin-bottom:' + mb + ';">';
                html += '<div style="display:flex;flex-direction:column;align-items:flex-end;max-width:75%;">';
                if (m.body) {
                    html += '<div onclick="toggleChatMsgTime(\''+timeId+'\')" style="cursor:pointer;background:var(--dark);color:#fff;border-radius:16px 16px 3px 16px;padding:8px 12px;font-size:13px;line-height:1.45;word-break:break-word;">' + escHtml(m.body) + '</div>';
                }
                if (imgHtml) html += imgHtml;
                if (fileHtml) html += fileHtml;
                if (timeLabel) html += '<div id="'+timeId+'" style="display:none;font-size:10px;color:#aaa;margin-top:3px;text-align:right;">' + escHtml(timeLabel) + '</div>';
                html += '</div>';
                html += '<div style="' + avatarStyle + 'background:#444;">' + myInit + '</div>';
                html += '</div>';

            } else {
                // RECEIVED — left side
                var cInit = (chatContact && chatContact.name || '?').charAt(0).toUpperCase();
                html += '<div style="display:flex;justify-content:flex-start;align-items:flex-end;gap:5px;margin-bottom:' + mb + ';">';
                html += '<div style="' + avatarStyle + 'background:var(--dark);">' + cInit + '</div>';
                html += '<div style="display:flex;flex-direction:column;align-items:flex-start;max-width:75%;">';
                if (m.body) {
                    html += '<div onclick="toggleChatMsgTime(\''+timeId+'\')" style="cursor:pointer;background:#fff;color:#050505;border:1px solid #e8e8e8;border-radius:16px 16px 16px 3px;padding:8px 12px;font-size:13px;line-height:1.45;word-break:break-word;box-shadow:0 1px 2px rgba(0,0,0,.05);">' + escHtml(m.body) + '</div>';
                }
                if (imgHtml) html += imgHtml;
                if (fileHtml) html += fileHtml;
                if (timeLabel) html += '<div id="'+timeId+'" style="display:none;font-size:10px;color:#aaa;margin-top:3px;">' + escHtml(timeLabel) + '</div>';
                html += '</div>';
                html += '</div>';
            }
        });
        msgList.innerHTML = html;
        msgList.scrollTop = msgList.scrollHeight;
        if (window.lucide) lucide.createIcons();
    }

    window.sendChatMsg = function() {
        var input = document.getElementById('chatWinInput');
        var body  = input.value.trim();
        if (!body || !chatContact) return;
        input.value = '';

        var fd = new FormData();
        fd.append('recipient_type', chatContact.type);
        fd.append('recipient_id',   chatContact.id);
        fd.append('body',           body);
        fd.append('_token',         CSRF);

        // Optimistic render
        var myInit   = '{{ strtoupper(substr(session("name", "E"), 0, 1)) }}';
        var statusId = 'chatMsgStatus-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;margin-bottom:10px;';
        div.innerHTML = '<div style="display:flex;align-items:flex-end;gap:6px;">'
            + '<div style="max-width:72%;background:var(--dark);color:#fff;border-radius:18px 18px 4px 18px;padding:9px 14px;font-size:13px;line-height:1.5;word-break:break-word;">'+escHtml(body)+'</div>'
            + '<div style="width:30px;height:30px;border-radius:50%;background:#555;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;">'+myInit+'</div>'
            + '</div>'
            + '<div id="'+statusId+'" style="font-size:10px;color:#aaa;margin-top:3px;margin-right:36px;">Sending…</div>';
        msgList.appendChild(div);
        msgList.scrollTop = msgList.scrollHeight;

        fetch(SEND_URL, { method:'POST', body: fd, headers:{ 'X-Requested-With':'XMLHttpRequest' } })
            .then(function(r){
                var statusEl = document.getElementById(statusId);
                if (!statusEl) return;
                if (r.ok) {
                    statusEl.textContent = 'Sent';
                } else {
                    statusEl.textContent = 'Failed to send';
                    statusEl.style.color = '#e53e3e';
                }
            })
            .catch(function(){
                var statusEl = document.getElementById(statusId);
                if (statusEl) {
                    statusEl.textContent = 'Failed to send';
                    statusEl.style.color = '#e53e3e';
                }
            });
    };

    window.sendChatFile = function(input) {
        if (!input.files.length || !chatContact) return;
        var fd = new FormData();
        fd.append('recipient_type', chatContact.type);
        fd.append('recipient_id',   chatContact.id);
        fd.append('body',           '');
        fd.append('_token',         CSRF);
        Array.from(input.files).forEach(function(f, i){
            fd.append('attachments[]', f);
        });
        input.value = ''; // reset so same file can be re-sent

        fetch(SEND_URL, { method:'POST', body: fd, headers:{ 'X-Requested-With':'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (chatContact) {
                    fetch(THREAD_URL + '/' + chatContact.type + '/' + chatContact.id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(r){ return r.json(); })
                        .then(function(d){ renderMessages(d.messages || []); });
                }
            })
            .catch(function(){});
    };

    window.closeChatWindow = function() {
        window_.style.display = 'none';
        if (wrap) wrap.style.display = '';
        chatContact = null;
        try { localStorage.removeItem('gmd_employee_open_chat'); } catch (e) {}
        if (window.lucide) lucide.createIcons();
    };

    function escHtml(s) {
        return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    window.toggleChatMsgTime = function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = (el.style.display === 'none') ? 'block' : 'none';
    };

    // Restore the chat window if it was left open on a previous page
    try {
        var savedChat = JSON.parse(localStorage.getItem('gmd_employee_open_chat') || 'null');
        if (savedChat && savedChat.type && savedChat.id) {
            window.openChatWith(savedChat.type, savedChat.id, savedChat.name);
        }
    } catch (e) {}
})();
</script>

<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="empLogoutModal">
    <div class="modal-card" style="max-width:420px;">
        <div class="modal-header">
            <div>
                <h2>Log out</h2>
                <p>You are about to end your session.</p>
            </div>
            <button class="modal-close" type="button"
                    onclick="document.getElementById('empLogoutModal').classList.remove('show');">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="delete-confirm-body">
            <div class="delete-confirm-icon"><i data-lucide="log-out"></i></div>
            <p>Are you sure you want to log out?</p>
            <p style="font-size:13px;color:var(--text-secondary);margin-top:8px;line-height:1.5;">
                You will need to sign in again to access your account.
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" class="cancel-btn"
                    onclick="document.getElementById('empLogoutModal').classList.remove('show');">
                Cancel
            </button>
            <button type="button" class="save-btn"
                    onclick="document.getElementById('employee-logout-form').submit();">
                <i data-lucide="log-out"></i> Log out
            </button>
        </div>
    </div>
</div>
