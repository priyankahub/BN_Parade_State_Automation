<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}

$role = $_SESSION["role"];
$name = $_SESSION["name"];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">BN Parade State Portal</div>
    <div class="nav-actions">
        <span class="badge"><?php echo $role; ?></span>
        <a class="btn secondary" href="profile.php">Profile</a>
        <a class="btn secondary" href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="panel">
        <h2>Welcome, <?php echo htmlspecialchars($name); ?></h2>
        <p class="muted">Manage daily attendance, strength, leave, duty details, reports and approvals.</p>
    </div>

    <div class="grid">
        <?php if ($role === "ADMIN"): ?>
            <div class="card">
                <h3>Master Data</h3>
                <div class="link-list">
                    <a href="admin/company_master.php">Company Master</a>
                    <a href="admin/platoon_section_master.php">Platoon / Section Master</a>
                    <a href="admin/manage_soldiers.php">Soldier Master</a>
                </div>
            </div>
            <div class="card">
                <h3>User Control</h3>
                <div class="link-list">
                    <a href="admin/create_user.php">Create User</a>
                    <a href="admin/manage_users.php">Manage Users</a>
                    <a href="admin/notification_settings.php">Notification Settings</a>
                </div>
            </div>
            <div class="card">
                <h3>Approvals</h3>
                <div class="link-list">
                    <a href="admin/approve_attendance.php">Approve Attendance</a>
                    <a href="admin/approve_leave.php">Approve Leave</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($role === "ADJT_SA"): ?>
            <div class="card">
                <h3>Parade State Control</h3>
                <div class="link-list">
                    <a href="adjt_sa/battalion_parade_state.php">Battalion Parade State</a>
                    <a href="adjt_sa/approve_parade_state.php">Approve Parade State</a>
                    <a href="adjt_sa/manpower_shortages.php">Manpower Shortages</a>
                    <a href="adjt_sa/duty_overview.php">Duty Overview</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($role === "CHM_CLERK"): ?>
            <div class="card">
                <h3>Company Entries</h3>
                <div class="link-list">
                    <a href="clerk/daily_attendance.php">Daily Attendance</a>
                    <a href="clerk/leave_entry.php">Leave Entry</a>
                    <a href="clerk/duty_roster.php">Duty Roster</a>
                    <a href="clerk/course_entry.php">Course Entry</a>
                    <a href="clerk/sick_report.php">Sick Report / MH</a>
                    <a href="clerk/company_strength.php">Company Strength</a>
                    <a href="clerk/my_activity.php">My Activity</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($role, ["ADMIN", "ADJT_SA", "USER"])): ?>
            <div class="card">
                <h3>Reports</h3>
                <div class="link-list">
                    <a href="reports/daily_parade_state.php">Daily Parade State</a>
                    <a href="reports/coy_strength_summary.php">Coy-wise Strength Summary</a>
                    <a href="reports/leave_course_duty_report.php">Leave / Course / Duty Report</a>
                    <a href="reports/attendance_analytics.php">Attendance Analytics</a>
                    <a href="reports/archive.php">Archive</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($role === "USER"): ?>
            <div class="card">
                <h3>Read Only</h3>
                <div class="link-list">
                    <a href="user/view_parade_state.php">View Parade State</a>
                    <a href="user/view_reports.php">View Reports</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
