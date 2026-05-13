<?php
session_start();
if (!isset($_SESSION["role"])) { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today = date("Y-m-d");
$days  = min(30, max(7, (int)($_GET["days"] ?? 30)));

// 30-day trend: date → counts
$trend = mysqli_fetch_all(mysqli_query($conn,
    "SELECT attendance_date,
            SUM(status='Present') AS present,
            SUM(status IN ('Absent')) AS absent,
            SUM(status='Leave') AS on_leave,
            SUM(status IN ('Sick Report','MH')) AS sick,
            SUM(status='Duty') AS on_duty,
            SUM(status='Course') AS on_course,
            COUNT(*) AS total
     FROM attendance
     WHERE attendance_date >= DATE_SUB('$today', INTERVAL $days DAY)
       AND attendance_date <= '$today'
     GROUP BY attendance_date
     ORDER BY attendance_date"), MYSQLI_ASSOC);

// Company comparison for today
$coySummary = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.company_name, c.short_name,
            COUNT(DISTINCT p.id) AS total,
            SUM(a.status='Present') AS present,
            SUM(a.status='Absent') AS absent,
            SUM(a.status='Leave') AS on_leave
     FROM companies c
     LEFT JOIN personnel p ON p.company_id=c.id AND p.service_status='Serving'
     LEFT JOIN attendance a ON a.personnel_id=p.id AND a.attendance_date='$today'
     WHERE c.is_active=1
     GROUP BY c.id, c.company_name, c.short_name
     ORDER BY c.id"), MYSQLI_ASSOC);

// Overall status distribution (last 30 days)
$statusDist = mysqli_fetch_all(mysqli_query($conn,
    "SELECT status, COUNT(*) AS cnt
     FROM attendance
     WHERE attendance_date >= DATE_SUB('$today', INTERVAL 30 DAY)
     GROUP BY status ORDER BY cnt DESC"), MYSQLI_ASSOC);

// High-absence days
$absenceDays = mysqli_fetch_all(mysqli_query($conn,
    "SELECT attendance_date, SUM(status='Absent') AS absences, COUNT(*) AS total
     FROM attendance
     WHERE attendance_date >= DATE_SUB('$today', INTERVAL $days DAY)
     GROUP BY attendance_date
     HAVING absences > 0
     ORDER BY absences DESC LIMIT 5"), MYSQLI_ASSOC);

// Overall today stats
$todayRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(status='Present') AS present, SUM(status='Absent') AS absent,
            SUM(status='Leave') AS on_leave, SUM(status='Course') AS on_course,
            SUM(status='Duty') AS on_duty, SUM(status IN ('Sick Report','MH')) AS sick,
            COUNT(*) AS total
     FROM attendance WHERE attendance_date='$today'"));
$todayRow = $todayRow ?: [];

$avgPresent = 0;
if ($trend) {
    $tot = array_sum(array_column($trend,'present'));
    $cnt = count($trend);
    $avgPresent = $cnt > 0 ? round($tot / $cnt) : 0;
}

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Attendance Analytics | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        .analytics-row { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px; }
        @media(max-width:860px){ .analytics-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Attendance Analytics</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Attendance Analytics</h1>
            <p class="page-sub">Attendance trends, company comparisons and absence patterns.</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
            <div class="tf">
                <label for="days-sel" style="font-size:11px;">Trend Period</label>
                <select id="days-sel" name="days" style="min-height:38px;margin:0;">
                    <?php foreach ([7,14,30] as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo $days==$d?'selected':''; ?>><?php echo $d; ?> Days</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn secondary" type="submit">Refresh</button>
        </form>
    </div>

    <!-- Today's KPI tiles -->
    <div class="summary-tiles">
        <div class="summary-tile green"><strong><?php echo (int)($todayRow['present']??0); ?></strong><span>Present Today</span></div>
        <div class="summary-tile red"><strong><?php echo (int)($todayRow['absent']??0); ?></strong><span>Absent Today</span></div>
        <div class="summary-tile blue"><strong><?php echo (int)($todayRow['on_leave']??0); ?></strong><span>On Leave</span></div>
        <div class="summary-tile orange"><strong><?php echo (int)($todayRow['on_duty']??0); ?></strong><span>On Duty</span></div>
        <div class="summary-tile purple"><strong><?php echo (int)($todayRow['on_course']??0); ?></strong><span>On Course</span></div>
        <div class="summary-tile red"><strong><?php echo (int)($todayRow['sick']??0); ?></strong><span>Sick / MH</span></div>
        <div class="summary-tile"><strong><?php echo $avgPresent; ?></strong><span>Avg Present / Day</span></div>
    </div>

    <!-- Charts row -->
    <div class="analytics-row">
        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:16px;">Present Trend — Last <?php echo $days; ?> Days</h2>
            <?php if (empty($trend)): ?>
            <div class="empty-state" style="padding:30px;">
                <p>No attendance data for this period.</p>
            </div>
            <?php else: ?>
            <div class="chart-wrap" style="height:220px;">
                <canvas id="trendChart" aria-label="Attendance trend chart" role="img"></canvas>
            </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:16px;">Status Distribution — Last 30 Days</h2>
            <?php if (empty($statusDist)): ?>
            <div class="empty-state" style="padding:30px;"><p>No data available.</p></div>
            <?php else: ?>
            <div style="display:grid;grid-template-columns:180px 1fr;gap:20px;align-items:center;">
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="donutChart" aria-label="Status distribution donut chart" role="img"></canvas>
                </div>
                <div class="chart-legend" style="display:grid;gap:6px;">
                    <?php
                    $statusColors = [
                        'Present'=>'#22863a','Absent'=>'#c0392b','Leave'=>'#2471a3',
                        'Course'=>'#7d3c98','Duty'=>'#d68910','Sick Report'=>'#cb4335',
                        'MH'=>'#1abc9c','TD'=>'#5d6d7e','Attached Out'=>'#808b96','Other'=>'#95a5a6'
                    ];
                    foreach ($statusDist as $sd):
                        $clr = $statusColors[$sd['status']] ?? '#7a8fa6';
                    ?>
                    <div class="legend-item">
                        <span class="legend-swatch" style="background:<?php echo $clr; ?>;"></span>
                        <span><?php echo h($sd['status']); ?></span>
                        <span class="mono-sm text-gold"><?php echo number_format($sd['cnt']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Company comparison today -->
    <div class="analytics-row">
        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:16px;">Company Comparison — Today</h2>
            <?php if (empty($coySummary)): ?>
            <div class="empty-state" style="padding:30px;"><p>No company data for today.</p></div>
            <?php else: ?>
            <div class="chart-wrap" style="height:220px;">
                <canvas id="coyChart" aria-label="Company comparison bar chart" role="img"></canvas>
            </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 style="font-size:15px;margin-bottom:16px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--saffron)" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                High-Absence Days
            </h2>
            <?php if (empty($absenceDays)): ?>
            <div class="empty-state" style="padding:30px;"><p>No absence data for this period.</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th class="num">Absences</th><th class="num">Total</th><th class="num">Absent %</th></tr></thead>
                    <tbody>
                    <?php foreach ($absenceDays as $ad):
                        $pct = $ad['total'] > 0 ? round($ad['absences']/$ad['total']*100) : 0;
                    ?>
                    <tr>
                        <td class="mono"><?php echo h(date('D, d M Y', strtotime($ad['attendance_date']))); ?></td>
                        <td class="num text-red fw-bold"><?php echo (int)$ad['absences']; ?></td>
                        <td class="num"><?php echo (int)$ad['total']; ?></td>
                        <td class="num" style="color:<?php echo $pct>20?'var(--status-absent)':'var(--saffron)'; ?>"><?php echo $pct; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php if (!empty($trend)): ?>
<script>
Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#8fa8c6';
Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-subtle').trim() || '#1a3655';

var trendData = <?php echo json_encode(array_values($trend)); ?>;
var trendLabels = trendData.map(function(d){ return d.attendance_date.substr(5); });

new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: trendLabels,
        datasets: [
            { label:'Present', data: trendData.map(d=>parseInt(d.present)||0), backgroundColor:'rgba(34,134,58,.7)', borderRadius:3 },
            { label:'Absent',  data: trendData.map(d=>parseInt(d.absent)||0),  backgroundColor:'rgba(192,57,43,.6)', borderRadius:3 }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'top', labels:{ boxWidth:12, padding:12 } } },
        scales:{ x:{ stacked:false, ticks:{ maxRotation:45, font:{size:10} } }, y:{ beginAtZero:true, ticks:{ stepSize:5 } } }
    }
});

<?php if (!empty($statusDist)): ?>
var distData = <?php echo json_encode(array_values($statusDist)); ?>;
var statusColors = <?php echo json_encode($statusColors); ?>;
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: distData.map(d=>d.status),
        datasets:[{ data: distData.map(d=>parseInt(d.cnt)), backgroundColor: distData.map(d=>statusColors[d.status]||'#7a8fa6'), borderWidth:2, borderColor:'#132b45' }]
    },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, cutout:'62%' }
});
<?php endif; ?>

<?php if (!empty($coySummary)): ?>
var coyData = <?php echo json_encode(array_values($coySummary)); ?>;
new Chart(document.getElementById('coyChart'), {
    type: 'bar',
    data: {
        labels: coyData.map(d=>d.short_name),
        datasets: [
            { label:'Present', data:coyData.map(d=>parseInt(d.present)||0), backgroundColor:'rgba(34,134,58,.75)', borderRadius:4 },
            { label:'Absent',  data:coyData.map(d=>parseInt(d.absent)||0),  backgroundColor:'rgba(192,57,43,.65)', borderRadius:4 },
            { label:'Leave',   data:coyData.map(d=>parseInt(d.on_leave)||0),backgroundColor:'rgba(36,113,163,.65)', borderRadius:4 }
        ]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'top', labels:{ boxWidth:12, padding:12 } } },
        scales:{ x:{ ticks:{ font:{size:11} } }, y:{ beginAtZero:true } }
    }
});
<?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
