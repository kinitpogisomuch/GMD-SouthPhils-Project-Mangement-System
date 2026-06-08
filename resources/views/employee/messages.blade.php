<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div class="card" style="height: calc(100vh - 160px); display: flex; flex-direction: column; margin-bottom: 0;">
                <div class="card-header">
                    <span class="card-title">Messages</span>
                    <span class="status-badge pending">2 Unread</span>
                </div>
                <div class="message-list-container">
                    <div class="message-sidebar">

                        @php
                        $threads = [
                            ['name' => 'Engr. Santos (Supervisor)', 'preview' => 'Weld inspection report needed by EOD.', 'time' => '9:45 AM', 'unread' => 1, 'active' => true],
                            ['name' => 'Project Manager', 'preview' => 'Updated schedule for Phase 3 attached.', 'time' => 'Yesterday', 'unread' => 1, 'active' => false],
                            ['name' => 'Admin – GMD', 'preview' => 'Please submit your timesheet for this week.', 'time' => 'Apr 8', 'unread' => 0, 'active' => false],
                        ];
                        @endphp

                        @foreach($threads as $t)
                        <div class="message-thread"
                             style="background: {{ $t['active'] ? 'var(--accent-soft)' : 'transparent' }};"
                             onclick="openMessage(this, '{{ addslashes($t['name']) }}')">
                            <div class="message-thread-header">
                                <div class="message-thread-name"
                                     style="font-weight: {{ $t['unread'] > 0 ? '900' : '700' }}; color:var(--dark);">
                                    {{ $t['name'] }}
                                </div>
                                <div class="flex-center gap-8">
                                    <span class="message-thread-time">{{ $t['time'] }}</span>
                                    @if($t['unread'] > 0)
                                    <span class="unread-badge">{{ $t['unread'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="message-thread-preview">{{ $t['preview'] }}</div>
                        </div>
                        @endforeach

                    </div>

                    <div id="chatWindow" class="message-chat-window">
                        <div class="message-chat-header">
                            <div class="message-chat-avatar">
                                <i data-lucide="user-check"></i>
                            </div>
                            <div class="message-chat-info">
                                <div class="message-chat-name">Engr. Santos (Supervisor)</div>
                                <div class="message-chat-status">
                                    <span class="message-chat-status-dot"></span> Online
                                </div>
                            </div>
                        </div>

                        <div id="messageThread" class="message-thread-content">
                            <div class="message-bubble received">
                                <div class="message-avatar">
                                    <i data-lucide="user" style="width:15px;height:15px;color:var(--muted);"></i>
                                </div>
                                <div class="message-bubble-content">
                                    <div class="message-text">Good morning! Make sure the weld inspection on Section 3B is completed before 5 PM today.</div>
                                    <div class="message-time">9:30 AM</div>
                                </div>
                            </div>

                            <div class="message-bubble sent">
                                <div class="message-bubble-content">
                                    <div class="message-text">Understood, Sir. I'll have the report and photos submitted before 5 PM.</div>
                                    <div class="message-time">9:38 AM ✓✓</div>
                                </div>
                            </div>

                            <div class="message-bubble received">
                                <div class="message-avatar">
                                    <i data-lucide="user" style="width:15px;height:15px;color:var(--muted);"></i>
                                </div>
                                <div class="message-bubble-content">
                                    <div class="message-text">Weld inspection report needed by EOD. Please include photos of all seam lines.</div>
                                    <div class="message-time">9:45 AM</div>
                                </div>
                            </div>
                        </div>

                        <div class="message-input-area">
                            <input type="text" id="replyInput" placeholder="Type a message..."
                                   class="log-input message-input-field">
                            <button class="btn btn-primary message-send-btn" onclick="sendReply()">
                                <i data-lucide="send"></i> Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function openMessage(element, sender) {
            document.querySelectorAll('.message-thread').forEach(el => {
                el.style.background = 'transparent';
            });
            element.style.background = 'var(--accent-soft)';
            const chatHeader = document.querySelector('#chatWindow > div:first-child');
            if (chatHeader) {
                chatHeader.querySelector('div:last-child div:first-child').textContent = sender;
            }
            const unreadBadge = element.querySelector('.unread-badge');
            if (unreadBadge) unreadBadge.remove();
        }

        function sendReply() {
            const replyInput = document.getElementById('replyInput');
            const message = replyInput.value.trim();
            if (!message) return;
            const messageThread = document.getElementById('messageThread');
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const sentMessage = document.createElement('div');
            sentMessage.style.cssText = 'display:flex;justify-content:flex-end;';
            sentMessage.innerHTML = `
                <div style="max-width:75%;">
                    <div style="background:linear-gradient(135deg,var(--dark) 0%,var(--dark-soft) 100%);color:var(--white);padding:12px 16px;border-radius:14px 0 14px 14px;font-size:13.5px;line-height:1.6;">
                        ${escapeHtml(message)}
                    </div>
                    <div style="font-size:11px;color:var(--muted);font-weight:700;margin-top:5px;text-align:right;">${timeStr} ✓✓</div>
                </div>
            `;
            messageThread.appendChild(sentMessage);
            replyInput.value = '';
            messageThread.scrollTop = messageThread.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function toggleSidebar() {
            document.querySelector('.employee-sidebar').classList.toggle('open');
        }

        function initializeEmployeeDropdown() {
            const dropdown = document.querySelector(".employee-dropdown");
            const button = document.getElementById("employeeDropdownBtn");
            if (!dropdown || !button) return;
            button.addEventListener("click", function(event) {
                event.stopPropagation();
                const notificationDropdown = document.querySelector(".notification-dropdown");
                if (notificationDropdown) notificationDropdown.classList.remove("open");
                dropdown.classList.toggle("open");
            });
        }

        function initializeNotificationDropdown() {
            const dropdown = document.querySelector(".notification-dropdown");
            const button = document.getElementById("notificationDropdownBtn");
            if (!dropdown || !button) return;
            button.addEventListener("click", function(event) {
                event.stopPropagation();
                const employeeDropdown = document.querySelector(".employee-dropdown");
                if (employeeDropdown) employeeDropdown.classList.remove("open");
                dropdown.classList.toggle("open");
            });
        }

        function closeDropdownsOnOutsideClick() {
            document.addEventListener("click", function(event) {
                if (event.target.closest('a') || event.target.closest('button')) return;
                const employeeDropdown = document.querySelector(".employee-dropdown");
                const notificationDropdown = document.querySelector(".notification-dropdown");
                if (employeeDropdown) employeeDropdown.classList.remove("open");
                if (notificationDropdown) notificationDropdown.classList.remove("open");
            });
        }

        initializeEmployeeDropdown();
        initializeNotificationDropdown();
        closeDropdownsOnOutsideClick();
    </script>
</body>
</html>