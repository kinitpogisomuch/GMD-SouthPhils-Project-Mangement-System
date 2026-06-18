<aside class="admin-sidebar">
    <nav class="admin-sidebar-nav">

        <div class="sidebar-section-label">Overview</div>
        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           title="Dashboard">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.reports') }}"
           class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}"
           title="KPI Reports">
            <div class="sidebar-icon"><i data-lucide="bar-chart-2"></i></div>
            <span>KPI Reports</span>
        </a>

        <div class="sidebar-section-label">Project Management</div>
        <a href="{{ route('admin.projects') }}"
           class="{{ request()->routeIs(['admin.projects', 'admin.project_view']) ? 'active' : '' }}"
           title="Projects">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>Projects</span>
        </a>

        <a href="{{ route('admin.project_materials') }}"
           class="{{ request()->routeIs(['admin.project_materials', 'admin.project_materials.detail']) ? 'active' : '' }}"
           title="Project Quotations">
            <div class="sidebar-icon"><i data-lucide="package"></i></div>
            <span>Project Quotations</span>
        </a>

        <a href="{{ route('admin.material_usage') }}"
           class="{{ request()->routeIs(['admin.material_usage', 'admin.material_usage.detail']) ? 'active' : '' }}"
           title="Materials">
            <div class="sidebar-icon"><i data-lucide="clipboard-list"></i></div>
            <span>Materials</span>
        </a>

        <div class="sidebar-section-label">Financial Management</div>
        <a href="{{ route('admin.payments') }}"
           class="{{ request()->routeIs('admin.payments') ? 'active' : '' }}"
           title="Payments">
            <div class="sidebar-icon"><i data-lucide="credit-card"></i></div>
            <span>Payments</span>
        </a>

        <a href="{{ route('admin.revolving_fund') }}"
           class="{{ request()->routeIs('admin.revolving_fund') ? 'active' : '' }}"
           title="Revolving Fund">
            <div class="sidebar-icon"><i data-lucide="refresh-cw"></i></div>
            <span>Revolving Fund</span>
        </a>

        <div class="sidebar-section-label">People</div>
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

        <div class="sidebar-section-label">Communication</div>
        <a href="{{ route('admin.messages') }}"
           class="{{ request()->routeIs('admin.messages') ? 'active' : '' }}"
           title="Messages">
            <div class="sidebar-icon">
                <i data-lucide="message-square"></i>
                <span class="sidebar-badge" id="sidebarMessagesBadge" style="display:none;"></span>
            </div>
            <span>Messages</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('admin.settings') }}"
           class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}"
           title="Settings">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>

    </nav>
</aside>
<div class="sidebar-overlay"></div>
