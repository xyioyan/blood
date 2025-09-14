<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : ''; ?>">
                <a href="manage_students.php">
                    <i class="fas fa-users"></i> Manage Students
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_requests.php' ? 'active' : ''; ?>">
                <a href="manage_requests.php">
                    <i class="fas fa-request"></i> Manage Blood Requests
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'donation_events.php' ? 'active' : ''; ?>">
                <a href="donation_events.php">
                    <i class="fas fa-calendar-alt"></i> Donation Events
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </nav>
</aside>