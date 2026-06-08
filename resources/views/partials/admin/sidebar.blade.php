<aside class="admin-sidebar">
    <nav class="admin-sidebar-nav">

        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           title="Dashboard">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.projects') }}"
           class="{{ request()->routeIs(['admin.projects', 'admin.project_view']) ? 'active' : '' }}"
           title="Projects">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>Projects</span>
        </a>

        <a href="{{ route('admin.employees') }}"
           class="{{ request()->routeIs('admin.employees') ? 'active' : '' }}"
           title="Employees">
            <div class="sidebar-icon"><i data-lucide="users"></i></div>
            <span>Employees</span>
        </a>

        <a href="{{ route('admin.clients') }}"
           class="{{ request()->routeIs('admin.clients') ? 'active' : '' }}"
           title="Clients">
            <div class="sidebar-icon"><i data-lucide="building-2"></i></div>
            <span>Clients</span>
        </a>

        <a href="{{ route('admin.material_requests') }}"
           class="{{ request()->routeIs('admin.material_requests') ? 'active' : '' }}"
           title="Material Requests">
            <div class="sidebar-icon"><i data-lucide="clipboard-list"></i></div>
            <span>Material Requests</span>
        </a>

        <a href="{{ route('admin.payments') }}"
           class="{{ request()->routeIs('admin.payments') ? 'active' : '' }}"
           title="Payments">
            <div class="sidebar-icon"><i data-lucide="credit-card"></i></div>
            <span>Payments</span>
        </a>

        <a href="{{ route('admin.messages') }}"
           class="{{ request()->routeIs('admin.messages') ? 'active' : '' }}"
           title="Messages">
            <div class="sidebar-icon"><i data-lucide="message-square"></i></div>
            <span>Messages</span>
        </a>

        <a href="{{ route('admin.settings') }}"
           class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}"
           title="Settings">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>

    </nav>
</aside>