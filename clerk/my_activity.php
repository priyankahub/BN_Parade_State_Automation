<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$clerkCoyId = (int)($_SESSION["company_id"] ?? 0);
$clerkId    = (int)$_SESSION["user_id"];
$today      = date("Y-m-d");

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';

// Attendance stats for this clerk's entries
$attStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(approval_status='Approved') AS approved,
            SUM(approval_status='Pending') AS pending,
            SUM(approval_status='Rejected') AS rejected
     FROM attendance
     WHERE entered_by = $clerkId
       AND attendance_date >= DATE_SUB('$today', INTERVAL 30 DAY)")) ?: [];

// Recent attendance entries (last 30 days)
$recentAtt = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.attendance_date, a.status, a.approval_status,
            p.army_no, p.rank_name, p.full_name
     FROM attendance a
     JOIN personnel p ON p.id = a.personnel_id
     WHERE a.entered_by = $clerkId
     ORDER BY a.attendance_date DESC, a.created_at DESC
     LIMIT 50"), MYSQLI_ASSOC);

// Recent leave entries
$recentLeave = mysqli_fetch_all(mysqli_query($conn,
    "SELECT lr.leave_type, lr.from_date, lr.to_date, lr.approval_status,
            p.army_no, p.rank_name, p.full_name
     FROM leave_records lr
     JOIN personnel p ON p.id = lr.personnel_id
     WHERE lr.entered_by = $clerkId
     ORDER BY lr.from_date DESC LIMIT 15"), MYSQLI_ASSOC);

// Recent duty entries
$recentDuty = mysqli_fetch_all(mysqli_query($conn,
    "SELECT dr.duty_type, dr.duty_date, dr.location,
            p.army_no, p.rank_name, p.full_name
     FROM duty_roster dr
     JOIN personnel p ON p.id = dr.personnel_id
     WHERE dr.entered_by = $clerkId
     ORDER BY dr.duty_date DESC LIMIT 10"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>My Activity | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">My Activity</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>My Activity</h1>
        <p class="page-sub"><?php echo h($coyName); ?> — your entries and approval trail for the last 30 days.</p>
    </div>

    <!-- Attendance Stats -->
    <div class="summary-tiles">
        <div class="summary-tile"><strong><?php echo (int)($attStats['total']??0); ?></strong><span>Att. Entries (30d)</span></div>
        <div class="summary-tile green"><strong><?php echo (int)($attStats['approved']??0); ?></strong><span>Approved</span></div>
        <div class="summary-tile orange"><strong><?php echo (int)($attStats['pending']??0); ?></strong><span>Pending</span></div>
        <div class="summary-tile red"><strong><?php echo (int)($attStats['rejected']??0); ?></strong><span>Rejected</span></div>
        <div class="summary-tile blue"><strong><?php echo count($recentLeave); ?></strong><span>Leave Entries</span></div>
        <div class="summary-tile orange"><strong><?php echo count($recentDuty); ?></strong><span>Duty Entries</span></div>
    </div>

    <!-- Recent Attendance -->
    <div class="panel" style="margin-bottom:24px;">
        <h2 style="font-size:15px;margin-bottom:14px;">Recent Attendance Entries</h2>
        <?php if (empty($recentAtt)): ?>
        <div class="empty-state" style="padding:28px;">
            <p>No attendance entries in the last 30 days.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Army No</th><th>Name</th><th>Status</th><th>Approval</th></tr></thead>
                <tbody>
                <?php foreach ($recentAtt as $r): ?>
                <tr>
                    <td class="mono"><?php echo h(date('D d M', strtotime($r['attendance_date']))); ?></td>
                    <td class="mono"><?php echo h($r['army_no']); ?></td>
                    <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace([' ','/'],'-',$r['status'])); ?>"><?php echo h($r['status']); ?></span></td>
                    <td><span class="status-badge <?php echo match($r['approval_status']){'Approved'=>'status-present','Rejected'=>'status-absent',default=>'status-duty'}; ?>"><?php echo h($r['approval_status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <!-- Recent Leave -->
        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:14px;">Leave Entries</h2>
            <?php if (empty($recentLeave)): ?>
            <p class="muted" style="font-size:13px;">No leave entries recorded.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Leave Type</th><th>From</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentLeave as $r): ?>
                    <tr>
                        <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                        <td><?php echo h($r['leave_type']); ?></td>
                        <td class="mono"><?php echo h(date('d M Y',strtotime($r['from_date']))); ?></td>
                        <td><span class="status-badge <?php echo match($r['approval_status']){'Approved'=>'status-present','Rejected'=>'status-absent',default=>'status-duty'}; ?>"><?php echo h($r['approval_status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Duty -->
        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:14px;">Duty Entries</h2>
            <?php if (empty($recentDuty)): ?>
            <p class="muted" style="font-size:13px;">No duty entries recorded.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Name</th><th>Duty Type</th><th>Date</th><th>Location</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentDuty as $r): ?>
                    <tr>
                        <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                        <td><span class="status-badge status-duty"><?php echo h($r['duty_type']); ?></span></td>
                        <td class="mono"><?php echo h(date('d M Y',strtotime($r['duty_date']))); ?></td>
                        <td class="text-muted"><?php echo h($r['location'] ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
