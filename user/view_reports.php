<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "USER") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today = date("Y-m-d");

// 7-day presence trend
$trend = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.attendance_date,
            COUNT(CASE WHEN a.status='Present' THEN 1 END) AS present,
            COUNT(*) AS total
     FROM attendance a
     WHERE a.attendance_date BETWEEN DATE_SUB('$today', INTERVAL 6 DAY) AND '$today'
     GROUP BY a.attendance_date
     ORDER BY a.attendance_date"), MYSQLI_ASSOC);

// Today's quick stats
$todayStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(CASE WHEN status='Present' THEN 1 END) AS present,
            COUNT(CASE WHEN status='Absent'  THEN 1 END) AS absent,
            COUNT(CASE WHEN status='Leave'   THEN 1 END) AS on_leave,
            COUNT(CASE WHEN status='Course'  THEN 1 END) AS on_course,
            COUNT(CASE WHEN status='Duty'    THEN 1 END) AS on_duty,
            COUNT(CASE WHEN status IN ('Sick Report','MH') THEN 1 END) AS sick,
            COUNT(*) AS total
     FROM attendance WHERE attendance_date='$today'")) ?? [];

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>View Reports | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Reports</span>
    </nav>

    <div class="panel">
        <div class="page-header" style="margin-bottom:0;">
            <h1 style="font-size:22px;">Reports Overview</h1>
            <p class="page-sub">Read-only summary — <?php echo h(date('D, d M Y')); ?></p>
        </div>
    </div>

    <!-- Today's Summary -->
    <div class="summary-tiles">
        <div class="summary-tile green"><strong><?php echo (int)($todayStats['present'] ?? 0); ?></strong><span>Present Today</span></div>
        <div class="summary-tile red"><strong><?php echo (int)($todayStats['absent'] ?? 0); ?></strong><span>Absent Today</span></div>
        <div class="summary-tile blue"><strong><?php echo (int)($todayStats['on_leave'] ?? 0); ?></strong><span>On Leave</span></div>
        <div class="summary-tile orange"><strong><?php echo (int)($todayStats['on_course'] ?? 0); ?></strong><span>On Course</span></div>
        <div class="summary-tile"><strong><?php echo (int)($todayStats['on_duty'] ?? 0); ?></strong><span>On Duty</span></div>
        <div class="summary-tile"><strong><?php echo (int)($todayStats['sick'] ?? 0); ?></strong><span>Sick/MH</span></div>
    </div>

    <div class="grid two-column">
        <!-- 7-day trend chart -->
        <div class="panel">
            <h2>7-Day Presence Trend</h2>
            <?php if (empty($trend)): ?>
                <div class="empty-state" style="padding:40px 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <h3>No Data Yet</h3>
                    <p>Attendance records will appear here once entries are saved.</p>
                </div>
            <?php else: ?>
                <div class="chart-wrap"><canvas id="trendChart" height="200"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- Quick links -->
        <div class="panel">
            <h2>Available Reports</h2>
            <div class="link-list">
                <a href="../reports/daily_parade_state.php">Daily Parade State</a>
                <a href="../reports/coy_strength_summary.php">Company Strength Summary</a>
                <a href="../reports/leave_course_duty_report.php">Leave / Course / Duty Report</a>
                <a href="../reports/attendance_analytics.php">Attendance Analytics</a>
                <a href="../reports/archive.php">Archive</a>
                <a href="view_parade_state.php">Battalion Parade State (Live)</a>
            </div>
        </div>
    </div>
</main>

<?php if (!empty($trend)): ?>
<script>
(function() {
    var labels = <?php echo json_encode(array_map(fn($r) => date('D d', strtotime($r['attendance_date'])), $trend)); ?>;
    var presentData = <?php echo json_encode(array_map(fn($r) => (int)$r['present'], $trend)); ?>;
    var gold = getComputedStyle(document.documentElement).getPropertyValue('--gold').trim() || '#c9a227';
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Present',
                data: presentData,
                borderColor: gold,
                backgroundColor: gold + '22',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: gold
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
