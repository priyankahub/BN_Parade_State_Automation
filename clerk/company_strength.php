<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$clerkCoyId = (int)($_SESSION["company_id"] ?? 0);
$today = date("Y-m-d");
$viewDate = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name, short_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';

// Status breakdown for this company on viewDate
$statuses = mysqli_fetch_all(mysqli_query($conn,
    "SELECT COALESCE(a.status,'Not Entered') AS status, COUNT(*) AS cnt
     FROM personnel p
     LEFT JOIN attendance a ON a.personnel_id=p.id AND a.attendance_date='$viewDate'
     WHERE p.company_id=$clerkCoyId AND p.service_status='Serving'
     GROUP BY COALESCE(a.status,'Not Entered')
     ORDER BY cnt DESC"), MYSQLI_ASSOC);

$totalPersonnel = array_sum(array_column($statuses, 'cnt'));
$presentCount   = 0;
foreach ($statuses as $s) if ($s['status']==='Present') $presentCount = $s['cnt'];

// 14-day trend for this company
$trend = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.attendance_date,
            SUM(a.status='Present') AS present,
            COUNT(p.id) AS total
     FROM personnel p
     LEFT JOIN attendance a ON a.personnel_id=p.id
     WHERE p.company_id=$clerkCoyId AND p.service_status='Serving'
       AND a.attendance_date >= DATE_SUB('$today', INTERVAL 14 DAY)
     GROUP BY a.attendance_date
     ORDER BY a.attendance_date"), MYSQLI_ASSOC);

$statusColors = [
    'Present'=>'#22863a','Absent'=>'#c0392b','Leave'=>'#2471a3',
    'Course'=>'#7d3c98','Duty'=>'#d68910','Sick Report'=>'#cb4335',
    'MH'=>'#1abc9c','TD'=>'#5d6d7e','Not Entered'=>'#4a5568','Other'=>'#95a5a6'
];

$pct = $totalPersonnel > 0 ? round($presentCount / $totalPersonnel * 100) : 0;
$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Company Strength | BN Parade State Portal</title>
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
        <span class="breadcrumb-current">Company Strength</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><?php echo h($coyName); ?> — Strength</h1>
            <p class="page-sub">Company attendance strength for <?php echo h(date('d M Y', strtotime($viewDate))); ?>.</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
            <div class="tf">
                <label for="v-date" style="font-size:11px;">Date</label>
                <input type="date" id="v-date" name="date" value="<?php echo h($viewDate); ?>" style="min-height:38px;margin:0;">
            </div>
            <button class="btn secondary" type="submit">Go</button>
        </form>
    </div>

    <div class="summary-tiles">
        <div class="summary-tile"><strong><?php echo $totalPersonnel; ?></strong><span>Total Serving</span></div>
        <div class="summary-tile green"><strong><?php echo $presentCount; ?></strong><span>Present</span></div>
        <div class="summary-tile" style="border-left-color:<?php echo $pct>=80?'var(--status-present)':($pct>=60?'var(--saffron)':'var(--status-absent)'); ?>">
            <strong style="color:<?php echo $pct>=80?'var(--status-present)':($pct>=60?'var(--saffron)':'var(--status-absent)'); ?>"><?php echo $pct; ?>%</strong>
            <span>Present Rate</span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start;">
        <!-- Donut chart -->
        <div class="panel" style="text-align:center;">
            <h2 style="font-size:14px;margin-bottom:16px;">Status Breakdown</h2>
            <?php if (empty($statuses)): ?>
            <p class="muted" style="font-size:13px;">No data for this date.</p>
            <?php else: ?>
            <div class="chart-wrap" style="height:200px;">
                <canvas id="donutChart" aria-label="Company strength donut chart"></canvas>
            </div>
            <div style="margin-top:16px;display:grid;gap:6px;">
                <?php foreach ($statuses as $s):
                    $clr = $statusColors[$s['status']] ?? '#7a8fa6';
                ?>
                <div class="legend-item">
                    <span class="legend-swatch" style="background:<?php echo $clr; ?>"></span>
                    <span><?php echo h($s['status']); ?></span>
                    <span class="mono-sm text-gold"><?php echo $s['cnt']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 14-day trend -->
        <div class="panel">
            <h2 style="font-size:14px;margin-bottom:16px;">14-Day Present Trend</h2>
            <?php if (empty($trend)): ?>
            <div class="empty-state" style="padding:30px;"><p>No trend data available.</p></div>
            <?php else: ?>
            <div class="chart-wrap" style="height:220px;">
                <canvas id="trendChart" aria-label="14-day trend chart"></canvas>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed table -->
    <div class="panel" style="padding:0;overflow:hidden;margin-top:0;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;">Status breakdown summary</p>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Status</th><th class="num">Count</th><th class="num">Percentage</th><th>Visual</th></tr></thead>
                <tbody>
                <?php foreach ($statuses as $s):
                    $sPct = $totalPersonnel > 0 ? round($s['cnt']/$totalPersonnel*100) : 0;
                    $clr  = $statusColors[$s['status']] ?? '#7a8fa6';
                ?>
                <tr>
                    <td><span class="status-badge" style="background:<?php echo $clr; ?>22;color:<?php echo $clr; ?>;border:1px solid <?php echo $clr; ?>44;"><?php echo h($s['status']); ?></span></td>
                    <td class="num fw-bold" style="color:<?php echo $clr; ?>"><?php echo $s['cnt']; ?></td>
                    <td class="num"><?php echo $sPct; ?>%</td>
                    <td>
                        <div class="strength-bar-cell">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?php echo $sPct; ?>%;background:<?php echo $clr; ?>;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php if (!empty($statuses)): ?>
<script>
var statusColors = <?php echo json_encode($statusColors); ?>;
var distData = <?php echo json_encode(array_values($statuses)); ?>;
new Chart(document.getElementById('donutChart'), {
    type:'doughnut',
    data:{
        labels: distData.map(d=>d.status),
        datasets:[{ data:distData.map(d=>parseInt(d.cnt)), backgroundColor:distData.map(d=>statusColors[d.status]||'#7a8fa6'), borderWidth:2, borderColor:'#132b45' }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, cutout:'60%' }
});
<?php if (!empty($trend)): ?>
var trendData = <?php echo json_encode(array_values($trend)); ?>;
new Chart(document.getElementById('trendChart'), {
    type:'line',
    data:{
        labels: trendData.map(d=>d.attendance_date.substr(5)),
        datasets:[{ label:'Present', data:trendData.map(d=>parseInt(d.present)||0), borderColor:'#22863a', backgroundColor:'rgba(34,134,58,.15)', fill:true, tension:0.3, pointRadius:4 }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true } } }
});
<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
