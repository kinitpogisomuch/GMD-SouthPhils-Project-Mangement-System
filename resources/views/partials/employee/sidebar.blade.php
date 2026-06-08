<aside class="employee-sidebar">
    <nav class="employee-sidebar-nav">
        <a href="{{ route('employee.dashboard') }}"
           class="{{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="layout-dashboard"></i></div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('employee.projects') }}"
           class="{{ request()->routeIs(['employee.projects']) ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="folder-kanban"></i></div>
            <span>Projects</span>
        </a>
        <a href="{{ route('employee.tasks') }}"
           class="{{ request()->routeIs('employee.tasks') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="check-square"></i></div>
            <span>My Tasks</span>
        </a>
        <a href="{{ route('employee.timesheets') }}"
           class="{{ request()->routeIs('employee.timesheets') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="clock"></i></div>
            <span>Timesheets</span>
        </a>
        <a href="{{ route('employee.messages') }}"
           class="{{ request()->routeIs('employee.messages') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="message-square"></i></div>
            <span>Messages</span>
        </a>
        <a href="{{ route('employee.settings') }}"
           class="{{ request()->routeIs('employee.settings') ? 'active' : '' }}">
            <div class="sidebar-icon"><i data-lucide="settings"></i></div>
            <span>Settings</span>
        </a>
    </nav>
</aside>
<main class="employee-content">