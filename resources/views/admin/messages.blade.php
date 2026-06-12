<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Messages</h1>
                    <p>Chat with employees and clients.</p>
                </div>
            </div>

            <div class="message-page-card">
                <div class="message-page-header">
                    <span class="message-page-title">Conversations</span>
                </div>

                <div class="message-list-container">
                    <div class="message-sidebar">
                        <div class="message-sidebar-search">
                            <input type="text" id="contactSearch" placeholder="Search contacts...">
                        </div>

                        <div id="contactList">
                            @forelse($contacts as $c)
                            <div class="message-thread {{ $c['unread'] > 0 ? 'unread' : '' }}"
                                 data-type="{{ $c['type'] }}"
                                 data-id="{{ $c['id'] }}"
                                 data-name="{{ $c['name'] }}"
                                 data-role="{{ $c['role'] }}">
                                <div class="message-thread-avatar"></div>
                                <div class="message-thread-body">
                                    <div class="message-thread-header">
                                        <span class="message-thread-name">{{ $c['name'] }} <span class="message-thread-role">{{ $c['role'] }}</span></span>
                                        <span class="message-thread-time">{{ $c['last_time'] }}</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                        <span class="message-thread-preview">{{ $c['last_message'] ?? 'No messages yet' }}</span>
                                        @if($c['unread'] > 0)
                                        <span class="unread-badge">{{ $c['unread'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="message-empty-state">
                                <i data-lucide="users"></i>
                                <p>No contacts available</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="message-chat-window" id="chatWindow">
                        <div class="message-empty-state" id="chatEmptyState">
                            <i data-lucide="message-square"></i>
                            <p>Select a conversation to start chatting</p>
                        </div>

                        <div id="chatActive" style="display:none; flex-direction:column; height:100%;">
                            <div class="message-chat-header">
                                <div class="message-chat-avatar" id="chatAvatar"></div>
                                <div class="message-chat-info">
                                    <div class="message-chat-name" id="chatName"></div>
                                    <div class="message-chat-role" id="chatRole"></div>
                                </div>
                            </div>

                            <div class="message-thread-content" id="chatMessages"></div>

                            <div class="message-input-area">
                                <input type="text" class="message-input-field" id="chatInput" placeholder="Type a message...">
                                <button class="message-send-btn" id="chatSendBtn" type="button">
                                    <i data-lucide="send"></i> Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        const CSRF = '{{ csrf_token() }}';
        const THREAD_URL_TEMPLATE = "{{ route('admin.messages.thread', ['type' => '__TYPE__', 'id' => '__ID__']) }}";
        const SEND_URL = "{{ route('admin.messages.send') }}";

        let activeContact = null;
        let pollTimer = null;

        function threadUrl(type, id) {
            return THREAD_URL_TEMPLATE.replace('__TYPE__', type).replace('__ID__', id);
        }

        function getInitials(name) {
            const parts = name.trim().split(/\s+/).filter(Boolean);
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.querySelectorAll('.message-thread-avatar').forEach(el => {
            const thread = el.closest('.message-thread');
            el.textContent = getInitials(thread.dataset.name);
        });

        document.querySelectorAll('.message-thread').forEach(el => {
            el.addEventListener('click', () => openThread(el));
        });

        function openThread(el) {
            document.querySelectorAll('.message-thread').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            el.classList.remove('unread');
            const badge = el.querySelector('.unread-badge');
            if (badge) badge.remove();

            activeContact = {
                type: el.dataset.type,
                id: el.dataset.id,
                name: el.dataset.name,
                role: el.dataset.role,
            };

            document.getElementById('chatEmptyState').style.display = 'none';
            document.getElementById('chatActive').style.display = 'flex';

            document.getElementById('chatAvatar').textContent = getInitials(activeContact.name);
            document.getElementById('chatName').textContent = activeContact.name;
            document.getElementById('chatRole').textContent = activeContact.role;

            loadThread();

            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(loadThread, 4000);
        }

        function loadThread() {
            if (!activeContact) return;
            fetch(threadUrl(activeContact.type, activeContact.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => renderMessages(data.messages));
        }

        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');

            if (!messages.length) {
                container.innerHTML = '<div class="message-empty-state"><i data-lucide="message-circle"></i><p>No messages yet. Say hello!</p></div>';
                lucide.createIcons();
                return;
            }

            container.innerHTML = '';
            messages.forEach(m => {
                const bubble = document.createElement('div');
                bubble.className = 'message-bubble ' + (m.is_mine ? 'sent' : 'received');

                if (!m.is_mine) {
                    const avatar = document.createElement('div');
                    avatar.className = 'message-bubble-avatar';
                    avatar.textContent = getInitials(activeContact.name);
                    bubble.appendChild(avatar);
                }

                const content = document.createElement('div');
                content.className = 'message-bubble-content';
                content.innerHTML = `<div class="message-text">${escapeHtml(m.body)}</div><div class="message-time">${m.time}</div>`;
                bubble.appendChild(content);

                container.appendChild(bubble);
            });

            container.scrollTop = container.scrollHeight;
        }

        function sendMessage() {
            if (!activeContact) return;
            const input = document.getElementById('chatInput');
            const body = input.value.trim();
            if (!body) return;

            input.value = '';

            fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    recipient_type: activeContact.type,
                    recipient_id: activeContact.id,
                    body: body
                })
            })
            .then(r => r.json())
            .then(data => {
                loadThread();
                updateSidebarPreview(activeContact, data.message);
            });
        }

        function updateSidebarPreview(contact, message) {
            const el = document.querySelector(`.message-thread[data-type="${contact.type}"][data-id="${contact.id}"]`);
            if (!el) return;
            const preview = el.querySelector('.message-thread-preview');
            if (preview) preview.textContent = message.body;
            const time = el.querySelector('.message-thread-time');
            if (time) time.textContent = message.time;
            el.parentNode.prepend(el);
        }

        document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keydown', e => {
            if (e.key === 'Enter') sendMessage();
        });

        document.getElementById('contactSearch').addEventListener('input', e => {
            const term = e.target.value.trim().toLowerCase();
            document.querySelectorAll('#contactList .message-thread').forEach(el => {
                const name = el.dataset.name.toLowerCase();
                el.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });
    </script>
</body>
</html>
