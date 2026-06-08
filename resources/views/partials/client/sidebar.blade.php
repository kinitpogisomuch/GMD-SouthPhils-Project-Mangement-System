<aside class="client-sidebar">
    <nav class="client-sidebar-nav">

        <a href="{{ route('client.dashboard') }}"
           class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}"
           title="Dashboard">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>

        <a href="{{ url('/client/projects') }}"
           class="{{ request()->routeIs('client.projects') ? 'active' : '' }}"
           title="My Projects">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>My Projects</span>
        </a>

        <a href="{{ route('client.documents') }}"
           class="{{ request()->routeIs('client.documents') ? 'active' : '' }}"
           title="Documents">
            <div class="sidebar-icon"><i data-lucide="file-text"></i></div>
            <span>Documents</span>
        </a>

        <a href="{{ route('client.payments') }}"
           class="{{ request()->routeIs('client.payments') ? 'active' : '' }}"
           title="Payments">
            <div class="sidebar-icon"><i data-lucide="credit-card"></i></div>
            <span>Payments</span>
        </a>

        <a href="{{ route('client.messages') }}"
           class="{{ request()->routeIs('client.messages') ? 'active' : '' }}"
           title="Messages">
            <div class="sidebar-icon"><i data-lucide="message-square"></i></div>
            <span>Messages</span>
        </a>

        <a href="{{ route('client.settings') }}"
           class="{{ request()->routeIs('client.settings') ? 'active' : '' }}"
           title="Settings">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>

    </nav>
</aside>
