<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.client.header')

    <main class="admin-content">

            <div class="page-header" style="margin-bottom:24px;">
                <div>
                    <h1 class="page-title">
                        Notifications
                        @if($unreadCount > 0)
                            <span class="notif-unread-badge">{{ $unreadCount }}</span>
                        @endif
                    </h1>
                    <p class="page-subtitle">
                        @if($unreadCount > 0)
                            {{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }} waiting for you.
                        @else
                            You're all caught up — no unread notifications.
                        @endif
                    </p>
                </div>
                @if($unreadCount > 0)
                <form method="POST" action="{{ route('client.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">
                        <i data-lucide="check-check"></i> Mark All as Read
                    </button>
                </form>
                @endif
            </div>

            <div class="filter-tabs" style="margin-bottom:20px;margin-left:auto;width:fit-content;">
                <a href="{{ route('client.notifications') }}" class="filter-tab {{ $filter === 'all' ? 'active' : '' }}">All</a>
                <a href="{{ route('client.notifications', ['filter' => 'unread']) }}" class="filter-tab {{ $filter === 'unread' ? 'active' : '' }}">Unread</a>
            </div>

            <div class="card" style="padding:0;overflow:hidden;">
                @php
                    $grouped = $notifications->getCollection()->groupBy(function ($n) {
                        if ($n->created_at->isToday()) return 'Today';
                        if ($n->created_at->isYesterday()) return 'Yesterday';
                        if ($n->created_at->greaterThanOrEqualTo(now()->subDays(7))) return 'This Week';
                        return 'Earlier';
                    });
                @endphp
                @forelse($grouped as $label => $group)
                    <div class="notif-date-heading">{{ $label }}</div>
                    @foreach($group as $n)
                        <a href="{{ $n->action_url ?? '#' }}"
                           class="notif-page-item {{ !$n->is_read ? 'unread' : '' }}"
                           data-id="{{ $n->id }}"
                           data-url="{{ $n->action_url ?? '' }}">
                            <div class="notification-icon {{ $n->icon_class }}">
                                <i data-lucide="{{ $n->icon }}"></i>
                            </div>
                            <div class="notif-page-body">
                                <div class="notif-page-title">{{ $n->title }}</div>
                                <div class="notif-page-msg">{{ $n->message }}</div>
                                <span class="notif-time" title="{{ $n->created_at->format('M d, Y g:i A') }}">{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="notif-page-right">
                                @if(!$n->is_read)
                                    <span class="notif-unread-dot"></span>
                                @endif
                                <i data-lucide="chevron-right" class="notif-page-chevron"></i>
                            </div>
                        </a>
                    @endforeach
                @empty
                    <div class="notification-empty" style="padding:48px;">
                        <div class="notification-empty-icon">
                            <i data-lucide="bell-off" style="width:28px;height:28px;color:var(--muted);"></i>
                        </div>
                        <span>{{ $filter === 'unread' ? "You're all caught up — no unread notifications." : 'No notifications yet.' }}</span>
                    </div>
                @endforelse
            </div>

            <div style="margin-top:20px;">
                {{ $notifications->links('vendor.pagination.custom') }}
            </div>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        const CSRF = '{{ csrf_token() }}';

        document.querySelectorAll('.notif-page-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;
                const url = this.dataset.url;
                fetch(`/client/notifications/${id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
                }).finally(() => {
                    if (url) window.location.href = url;
                    else window.location.reload();
                });
            });
        });
    </script>
</body>
</html>
