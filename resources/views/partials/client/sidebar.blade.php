<aside class="client-sidebar">
    <nav class="client-sidebar-nav">

        <div class="sidebar-section-label">Overview</div>
        <a href="{{ route('client.dashboard') }}"
           class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}"
           title="Dashboard">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-label">Projects</div>
        <a href="{{ url('/client/projects') }}"
           class="{{ request()->routeIs('client.projects') ? 'active' : '' }}"
           title="My Projects">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>My Projects</span>
        </a>

        <div class="sidebar-section-label">Finance</div>
        <a href="{{ route('client.payments') }}"
           class="{{ request()->routeIs('client.payments') ? 'active' : '' }}"
           title="Payments">
            <div class="sidebar-icon"><i data-lucide="credit-card"></i></div>
            <span>Payments</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('client.settings') }}"
           class="{{ request()->routeIs('client.settings') ? 'active' : '' }}"
           title="Settings">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>

    </nav>
</aside>
<div class="sidebar-overlay"></div>
