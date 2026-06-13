<aside class="employee-sidebar">
    <nav class="employee-sidebar-nav">
        <div class="sidebar-section-label">Overview</div>
        <a href="{{ route('employee.dashboard') }}"
           class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-label">Work</div>
        <a href="{{ route('employee.projects') }}"
           class="{{ request()->routeIs(['employee.projects']) ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>Projects</span>
        </a>
        <a href="{{ route('employee.project_materials') }}"
           class="{{ request()->routeIs(['employee.project_materials', 'employee.project_materials.detail']) ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="package"></i></div>
            <span>Project Materials</span>
        </a>
        <a href="{{ route('employee.material_usage') }}"
           class="{{ request()->routeIs(['employee.material_usage', 'employee.material_usage.detail']) ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="clipboard-list"></i></div>
            <span>Material Usage</span>
        </a>

        <div class="sidebar-section-label">Communication</div>
        <a href="{{ route('employee.messages') }}"
           class="{{ request()->routeIs('employee.messages') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="message-square"></i></div>
            <span>Messages</span>
        </a>

        <div class="sidebar-section-label">Finance</div>
        <a href="{{ route('employee.salary') }}"
           class="{{ request()->routeIs('employee.salary') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="wallet"></i></div>
            <span>My Salary</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('employee.settings') }}"
           class="{{ request()->routeIs('employee.settings') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>
    </nav>
</aside>
<div class="sidebar-overlay"></div>
<main class="employee-content">
