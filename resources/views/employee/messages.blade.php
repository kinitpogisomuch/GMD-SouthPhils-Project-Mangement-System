<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <div class="admin-layout">
        @include('partials.employee.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Messages</h1>
                    <p>Chat with admins.</p>
                </div>
            </div>

            <div class="message-page-card">
                <div class="message-page-header">
                    <div class="message-page-heading">
                        <div class="message-page-icon"><i data-lucide="message-square"></i></div>
                        <div>
                            <span class="message-page-title">Conversations</span>
                            <div class="message-page-subtitle">{{ count($contacts) }} {{ count($contacts) === 1 ? 'contact' : 'contacts' }}</div>
                        </div>
                    </div>
                </div>

                <div class="message-list-container">
                    <div class="message-sidebar">
                        <div class="message-sidebar-search">
                            <div class="message-search-wrap">
                                <i data-lucide="search"></i>
                                <input type="text" id="contactSearch" placeholder="Search contacts...">
                            </div>
                        </div>

                        <div id="contactList">
                            @forelse($contacts as $c)
                            <div class="message-thread {{ $c['unread'] > 0 ? 'unread' : '' }}"
                                 data-type="{{ $c['type'] }}"
                                 data-id="{{ $c['id'] }}"
                                 data-name="{{ $c['name'] }}"
                                 data-role="{{ $c['role'] }}"
                                 data-photo="{{ $c['profile_photo'] }}">
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
                                <button type="button" class="message-attach-btn message-chat-back-btn" id="chatBackBtn" title="Back to conversations">
                                    <i data-lucide="arrow-left"></i>
                                </button>
                                <div class="message-chat-avatar" id="chatAvatar"></div>
                                <div class="message-chat-info">
                                    <div class="message-chat-name" id="chatName"></div>
                                    <div class="message-chat-role" id="chatRole"></div>
                                </div>
                                <button type="button" class="message-attach-btn" id="chatInfoBtn" title="View contact info">
                                    <i data-lucide="info"></i>
                                </button>
                            </div>

                            <div class="message-thread-content" id="chatMessages"></div>

                            <div class="message-attachment-preview" id="attachmentPreview"></div>

                            <div class="message-input-area">
                                <div class="message-attach-group">
                                    <button type="button" class="message-attach-btn" id="attachCameraBtn" title="Take a photo">
                                        <i data-lucide="camera"></i>
                                    </button>
                                    <button type="button" class="message-attach-btn" id="attachImageBtn" title="Send a picture">
                                        <i data-lucide="image"></i>
                                    </button>
                                    <button type="button" class="message-attach-btn" id="attachFileBtn" title="Attach a file">
                                        <i data-lucide="paperclip"></i>
                                    </button>
                                </div>

                                <input type="file" id="cameraInput" accept="image/*" capture="environment" hidden>
                                <input type="file" id="imageInput" accept="image/*" multiple hidden>
                                <input type="file" id="fileInput" multiple hidden>

                                <input type="text" class="message-input-field" id="chatInput" placeholder="Type a message...">
                                <button class="message-send-btn" id="chatSendBtn" type="button">
                                    <i data-lucide="send"></i> <span>Send</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div class="modal-overlay" id="cameraModal">
        <div class="modal-card" style="max-width:480px;">
            <div class="modal-header">
                <div>
                    <h2>Take a Photo</h2>
                    <p>Position the camera and capture a photo to attach.</p>
                </div>
                <button class="modal-close" type="button" id="closeCameraModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="camera-preview-wrap">
                <video id="cameraVideo" autoplay playsinline muted></video>
            </div>
            <canvas id="cameraCanvas" style="display:none;"></canvas>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cameraCancelBtn">Cancel</button>
                <button type="button" class="save-btn" id="cameraCaptureBtn">
                    <i data-lucide="camera"></i> Capture
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="userInfoModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Contact Info</h2>
                    <p>Details for this conversation.</p>
                </div>
                <button class="modal-close" type="button" id="closeUserInfoModal">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="user-info-card">
                <div class="user-info-avatar" id="infoAvatar"></div>
                <div class="user-info-name" id="infoName"></div>
                <div class="user-info-role" id="infoRole"></div>
            </div>

            <div class="user-info-details">
                <div class="user-info-row">
                    <i data-lucide="mail"></i>
                    <span id="infoEmail">—</span>
                </div>
                <div class="user-info-row">
                    <i data-lucide="phone"></i>
                    <span id="infoContact">—</span>
                </div>
                <div class="user-info-row">
                    <i data-lucide="map-pin"></i>
                    <span id="infoAddress">—</span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="closeUserInfoBtn">Close</button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        const CSRF = '{{ csrf_token() }}';
        const MY_NAME = @json($myName);
        const MY_PHOTO = @json($myPhoto);
        const THREAD_URL_TEMPLATE = "{{ route('employee.messages.thread', ['type' => '__TYPE__', 'id' => '__ID__']) }}";
        const SEND_URL = "{{ route('employee.messages.send') }}";

        let activeContact = null;
        let activeContactInfo = null;
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

        function setAvatar(el, name, photo) {
            if (photo) {
                el.innerHTML = `<img src="${photo}" alt="${escapeHtml(name)}">`;
            } else {
                el.textContent = getInitials(name);
            }
        }

        document.querySelectorAll('.message-thread-avatar').forEach(el => {
            const thread = el.closest('.message-thread');
            setAvatar(el, thread.dataset.name, thread.dataset.photo);
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
                photo: el.dataset.photo,
            };
            activeContactInfo = null;

            document.getElementById('chatEmptyState').style.display = 'none';
            document.getElementById('chatActive').style.display = 'flex';
            document.querySelector('.message-list-container').classList.add('chat-open');

            setAvatar(document.getElementById('chatAvatar'), activeContact.name, activeContact.photo);
            document.getElementById('chatName').textContent = activeContact.name;
            document.getElementById('chatRole').textContent = activeContact.role;

            loadThread(true);

            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(loadThread, 4000);
        }

        document.getElementById('chatBackBtn').addEventListener('click', () => {
            document.querySelector('.message-list-container').classList.remove('chat-open');
        });

        function loadThread(forceScroll = false) {
            if (!activeContact) return;
            fetch(threadUrl(activeContact.type, activeContact.id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                activeContactInfo = data.contact || null;
                renderMessages(data.messages, forceScroll);
            });
        }

        function renderMessages(messages, forceScroll = false) {
            const container = document.getElementById('chatMessages');
            const wasNearBottom = forceScroll
                || (container.scrollHeight - container.scrollTop - container.clientHeight < 80);

            if (!messages.length) {
                container.innerHTML = '<div class="message-empty-state"><i data-lucide="message-circle"></i><p>No messages yet. Say hello!</p></div>';
                lucide.createIcons();
                return;
            }

            container.innerHTML = '';
            messages.forEach(m => {
                const bubble = document.createElement('div');
                bubble.className = 'message-bubble ' + (m.is_mine ? 'sent' : 'received');

                const avatar = document.createElement('div');
                avatar.className = 'message-bubble-avatar';
                setAvatar(avatar, m.is_mine ? MY_NAME : activeContact.name, m.is_mine ? MY_PHOTO : activeContact.photo);
                bubble.appendChild(avatar);

                const content = document.createElement('div');
                content.className = 'message-bubble-content';

                let html = '';
                if (m.body) {
                    html += `<div class="message-text">${escapeHtml(m.body)}</div>`;
                }
                if (m.attachments && m.attachments.length) {
                    html += '<div class="message-attachments">';
                    m.attachments.forEach(att => {
                        if (att.mime && att.mime.startsWith('image/')) {
                            html += `<a href="${att.url}" target="_blank" rel="noopener"><img src="${att.url}" class="message-attachment-img" alt="${escapeHtml(att.name)}"></a>`;
                        } else {
                            html += `<a href="${att.url}" target="_blank" rel="noopener" class="message-attachment-file"><i data-lucide="file-text"></i><span>${escapeHtml(att.name)}</span></a>`;
                        }
                    });
                    html += '</div>';
                }
                html += `<div class="message-time">${m.time}</div>`;
                content.innerHTML = html;
                bubble.appendChild(content);

                container.appendChild(bubble);
            });

            lucide.createIcons();

            if (wasNearBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function sendMessage() {
            if (!activeContact) return;
            const input = document.getElementById('chatInput');
            const body = input.value.trim();
            if (!body && !pendingAttachments.length) return;

            const formData = new FormData();
            formData.append('recipient_type', activeContact.type);
            formData.append('recipient_id', activeContact.id);
            formData.append('body', body);
            pendingAttachments.forEach(a => formData.append('attachments[]', a.file));

            input.value = '';
            clearAttachmentPreviews();

            fetch(SEND_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    alert(data.message || 'Failed to send message.');
                    return;
                }
                loadThread(true);
                updateSidebarPreview(activeContact, data.message);
            });
        }

        function updateSidebarPreview(contact, message) {
            const el = document.querySelector(`.message-thread[data-type="${contact.type}"][data-id="${contact.id}"]`);
            if (!el) return;
            const preview = el.querySelector('.message-thread-preview');
            if (preview) preview.textContent = message.body || (message.attachments && message.attachments.length ? 'Sent an attachment' : '');
            const time = el.querySelector('.message-thread-time');
            if (time) time.textContent = message.time;
            el.parentNode.prepend(el);
        }

        document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keydown', e => {
            if (e.key === 'Enter') sendMessage();
        });

        /* ── Contact info modal ──────────────────────────────────────── */
        document.getElementById('chatInfoBtn').addEventListener('click', () => {
            if (!activeContact) return;
            const info = activeContactInfo || {};

            const avatar = document.getElementById('infoAvatar');
            if (info.profile_photo) {
                avatar.innerHTML = `<img src="${info.profile_photo}" alt="${escapeHtml(activeContact.name)}">`;
            } else {
                avatar.textContent = getInitials(activeContact.name);
            }

            document.getElementById('infoName').textContent = activeContact.name;
            document.getElementById('infoRole').textContent = activeContact.role;
            document.getElementById('infoEmail').textContent = info.email || 'Not provided';
            document.getElementById('infoContact').textContent = info.contact || 'Not provided';
            document.getElementById('infoAddress').textContent = info.address || 'Not provided';

            openModal('userInfoModal');
        });

        document.getElementById('closeUserInfoModal').addEventListener('click', () => closeModal('userInfoModal'));
        document.getElementById('closeUserInfoBtn').addEventListener('click', () => closeModal('userInfoModal'));

        document.getElementById('contactSearch').addEventListener('input', e => {
            const term = e.target.value.trim().toLowerCase();
            document.querySelectorAll('#contactList .message-thread').forEach(el => {
                const name = el.dataset.name.toLowerCase();
                el.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });

        /* ── Attachment pickers (UI preview only, not yet sent) ──────────── */
        let pendingAttachments = [];

        function setupAttachmentPicker(buttonId, inputId) {
            const button = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            button.addEventListener('click', () => input.click());
            input.addEventListener('change', () => {
                Array.from(input.files).forEach(file => addAttachmentPreview(file));
                input.value = '';
            });
        }

        function addAttachmentPreview(file) {
            const id = 'att-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            pendingAttachments.push({ id, file });

            const preview = document.getElementById('attachmentPreview');
            const chip = document.createElement('div');
            chip.className = 'attachment-chip';
            chip.dataset.id = id;

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'attachment-chip-thumb';
                chip.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'attachment-chip-icon';
                icon.innerHTML = '<i data-lucide="file-text"></i>';
                chip.appendChild(icon);
            }

            const name = document.createElement('span');
            name.className = 'attachment-chip-name';
            name.textContent = file.name;
            chip.appendChild(name);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'attachment-chip-remove';
            remove.innerHTML = '<i data-lucide="x"></i>';
            remove.addEventListener('click', () => removeAttachmentPreview(id));
            chip.appendChild(remove);

            preview.appendChild(chip);
            preview.classList.add('show');
            lucide.createIcons();
        }

        function removeAttachmentPreview(id) {
            pendingAttachments = pendingAttachments.filter(a => a.id !== id);
            const preview = document.getElementById('attachmentPreview');
            const chip = preview.querySelector(`.attachment-chip[data-id="${id}"]`);
            if (chip) chip.remove();
            if (!preview.children.length) preview.classList.remove('show');
        }

        function clearAttachmentPreviews() {
            pendingAttachments = [];
            const preview = document.getElementById('attachmentPreview');
            preview.innerHTML = '';
            preview.classList.remove('show');
        }

        /* ── Camera capture ───────────────────────────────────────────── */
        let cameraStream = null;

        function openModal(id) {
            const m = document.getElementById(id);
            if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
        }

        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
        }

        function openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                document.getElementById('cameraInput').click();
                return;
            }

            openModal('cameraModal');
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(stream => {
                    cameraStream = stream;
                    document.getElementById('cameraVideo').srcObject = stream;
                })
                .catch(() => {
                    closeCamera();
                    document.getElementById('cameraInput').click();
                });
        }

        function closeCamera() {
            closeModal('cameraModal');
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');

            const w = video.videoWidth || video.offsetWidth;
            const h = video.videoHeight || video.offsetHeight;
            if (!w || !h) return;

            canvas.width = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(video, 0, 0, w, h);

            canvas.toBlob(blob => {
                closeCamera();
                if (blob) {
                    addAttachmentPreview(new File([blob], `photo-${Date.now()}.jpg`, { type: 'image/jpeg' }));
                }
            }, 'image/jpeg', 0.9);
        }

        document.getElementById('attachCameraBtn').addEventListener('click', openCamera);
        document.getElementById('cameraCaptureBtn').addEventListener('click', capturePhoto);
        document.getElementById('cameraCancelBtn').addEventListener('click', closeCamera);
        document.getElementById('closeCameraModal').addEventListener('click', closeCamera);

        document.getElementById('cameraInput').addEventListener('change', e => {
            if (!e.target.files.length) return;
            Array.from(e.target.files).forEach(file => addAttachmentPreview(file));
            e.target.value = '';
        });

        setupAttachmentPicker('attachImageBtn', 'imageInput');
        setupAttachmentPicker('attachFileBtn', 'fileInput');
    </script>
</body>
</html>
