<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$role = $_SESSION["role"];
$name = $_SESSION["name"];

include("config/db.php");

$today = date("Y-m-d");

if ($role === "CHM_CLERK") {
    // ── Clerk: stats scoped to their own company ──────────────────────────
    $clerkCid = (int)($_SESSION["company_id"] ?? 0);

    $totalPersonnel = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM personnel
         WHERE company_id = $clerkCid"))[0] ?? 0;

    $totalCompanies = null; // not used for clerk view

    $presentToday = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(DISTINCT a.personnel_id)
         FROM attendance a
         JOIN personnel p ON p.id = a.personnel_id
         WHERE a.attendance_date = '$today' AND a.status = 'Present'
           AND p.company_id = $clerkCid"))[0] ?? 0;

    $notEnteredToday = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM personnel p
         WHERE p.service_status = 'Serving' AND p.company_id = $clerkCid
           AND p.id NOT IN (
               SELECT personnel_id FROM attendance WHERE attendance_date = '$today'
           )"))[0] ?? 0;

    $pendingApprovals = null; // not used for clerk view

    $clerkCoyName = mysqli_fetch_row(mysqli_query($conn,
        "SELECT company_name FROM companies WHERE id = $clerkCid"))[0] ?? "Your Company";

} else {
    // ── Admin / Adjt / User: global battalion stats ───────────────────────
    $totalPersonnel = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM personnel WHERE service_status = 'Serving'"))[0] ?? 0;

    $totalCompanies = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM companies WHERE is_active = 1"))[0] ?? 0;

    $presentToday = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND status = 'Present'"))[0] ?? 0;

    $notEnteredToday = null;

    $pendingApprovals = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM attendance WHERE approval_status = 'Pending'"))[0] ?? 0;

    $clerkCoyName = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/head_meta.php"); ?>
    <title>Dashboard | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = ''; include("includes/topbar.php"); ?>

<main id="main-content" class="container">
    <div class="panel">
        <div class="page-header" style="margin-bottom:0;">
            <h1 style="font-size:22px;">Welcome, <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-sub">Manage daily attendance, strength, leave, duty details, reports and approvals.</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value"><?php echo $totalPersonnel; ?></span>
                <span class="stat-label"><?php echo $role === "CHM_CLERK" ? h($clerkCoyName) . " Strength" : "Serving Personnel"; ?></span>
            </div>
        </div>

        <?php if ($role !== "CHM_CLERK"): ?>
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value"><?php echo $totalCompanies; ?></span>
                <span class="stat-label">Active Companies</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,134,58,.15);color:var(--status-present)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value" style="color:var(--status-present)"><?php echo $presentToday; ?></span>
                <span class="stat-label">Present Today</span>
            </div>
        </div>

        <?php if ($role === "CHM_CLERK"): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(228,126,20,.12);color:var(--saffron,#e07b2a)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value" style="color:var(--saffron,#e07b2a)"><?php echo $notEnteredToday; ?></span>
                <span class="stat-label">Attendance Pending</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ["ADMIN","ADJT_SA"])): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--saffron-muted);color:var(--saffron)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="stat-body">
                <span class="stat-value" style="color:<?php echo $pendingApprovals > 0 ? 'var(--saffron)' : 'var(--gold)'; ?>"><?php echo $pendingApprovals; ?></span>
                <span class="stat-label">Pending Approvals</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid">
        <div class="card">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Nominal Roll
            </h3>
            <div class="link-list">
                <a href="admin/manage_soldiers.php">View Personnel</a>
                <a href="admin/nominal_roll_analytics.php">Analytics Dashboard</a>
            </div>
        </div>

        <?php if ($role === "ADMIN"): ?>
            <div class="card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Master Data
                </h3>
                <div class="link-list">
                    <a href="admin/company_master.php">Company Master</a>
                    <a href="admin/platoon_section_master.php">Platoon / Section Master</a>
                    <a href="admin/manage_soldiers.php">Soldier Master</a>
                </div>
            </div>
            <div class="card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    User Control
                </h3>
                <div class="link-list">
                    <a href="admin/create_user.php">Create User</a>
                    <a href="admin/manage_users.php">Manage Users</a>
                    <a href="admin/notification_settings.php">Notification Settings</a>
                </div>
            </div>
            <div class="card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Approvals
                </h3>
                <div class="link-list">
                    <a href="admin/approve_attendance.php">Approve Attendance</a>
                    <a href="admin/approve_leave.php">Approve Leave</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($role === "ADJT_SA"): ?>
            <div class="card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Parade State Control
                </h3>
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
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Nominal Roll
                </h3>
                <div class="link-list">
                    <a href="admin/manage_soldiers.php">My Company Personnel</a>
                </div>
            </div>
            <div class="card">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Company Entries
                </h3>
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
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Reports
                </h3>
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
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Read Only
                </h3>
                <div class="link-list">
                    <a href="user/view_parade_state.php">View Parade State</a>
                    <a href="user/view_reports.php">View Reports</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
