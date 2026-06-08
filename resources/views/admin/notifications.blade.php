<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Notifications</h1>
                    <p class="page-subtitle">Your recent system notifications.</p>
                </div>
                @if($notifications->total() > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">
                        <i data-lucide="check-check"></i> Mark All as Read
                    </button>
                </form>
                @endif
            </div>

            <div class="card" style="padding:0;overflow:hidden;">
                @forelse($notifications as $n)
                    @php
                        $iconMap = [
                            'project_created'    => 'folder-kanban',
                            'progress_requested' => 'bell',
                            'progress_submitted' => 'send',
                            'revision_requested' => 'alert-triangle',
                            'revision_submitted' => 'refresh-cw',
                            'progress_approved'  => 'check-circle',
                            'phase_advanced'     => 'layers',
                            'project_completed'  => 'award',
                            'pending_review'     => 'clock',
                        ];
                        $icon = $iconMap[$n->notification_type] ?? 'bell';
                        $priorityClass = match($n->priority) {
                            'warning' => 'notif-icon-warning',
                            'success' => 'notif-icon-success',
                            default   => 'notif-icon-info',
                        };
                    @endphp
                    <a href="{{ $n->action_url ?? '#' }}"
                       class="notif-page-item {{ !$n->is_read ? 'unread' : '' }}"
                       onclick="markRead(event, {{ $n->id }}, '{{ $n->action_url ?? '' }}')">
                        <div class="notification-icon {{ $priorityClass }}">
                            <i data-lucide="{{ $icon }}"></i>
                        </div>
                        <div class="notif-page-body">
                            <div class="notif-page-title">{{ $n->title }}</div>
                            <div class="notif-page-msg">{{ $n->message }}</div>
                            <span class="notif-time">{{ $n->created_at->format('M d, Y g:i A') }} &middot; {{ $n->created_at->diffForHumans() }}</span>
                        </div>
                        @if(!$n->is_read)
                            <span class="notif-unread-dot"></span>
                        @endif
                    </a>
                @empty
                    <div class="notification-empty" style="padding:48px;">
                        <i data-lucide="bell-off" style="width:32px;height:32px;color:var(--muted);"></i>
                        <span>No notifications yet.</span>
                    </div>
                @endforelse
            </div>

            <div style="margin-top:20px;">
                {{ $notifications->links() }}
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        const CSRF = '{{ csrf_token() }}';

        function markRead(e, id, url) {
            e.preventDefault();
            fetch(`/admin/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
            }).finally(() => {
                if (url && url !== '#' && url !== '') window.location.href = url;
                else window.location.reload();
            });
        }

        function initAdminDropdowns() {
            const adminDropdown = document.querySelector('.admin-dropdown');
            const adminBtn      = document.getElementById('adminDropdownBtn');
            if (!adminDropdown || !adminBtn) return;
            adminBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                adminDropdown.classList.toggle('open');
            });
            document.addEventListener('click', function() {
                adminDropdown.classList.remove('open');
            });
        }
        initAdminDropdowns();
    </script>
</body>
</html>
