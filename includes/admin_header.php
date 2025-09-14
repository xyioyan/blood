<header class="admin-header">
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/blood-requests.css">

    <div class="header-content">
        <div class="logo">
            <h1>Blood Group Management</h1>
            <p>Admin Panel</p>
        </div>
        <div class="admin-nav">
            <div class="admin-info">
                <span class="admin-name">Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                <img src="../assets/images/admin-avatar.png" alt="Admin Avatar" class="admin-avatar">
            </div>
            <div class="admin-dropdown">
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="/blood/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>