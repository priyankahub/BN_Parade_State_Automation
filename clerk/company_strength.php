<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$clerkCoyId = (int)($_SESSION["company_id"] ?? 0);
$today      = date("Y-m-d");
$viewDate   = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT company_name, short_name FROM companies WHERE id = $clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';

// ── 1. Total on roll — ALL personnel regardless of service status (= Nominal Roll count) ──
$totalOnRoll = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM personnel WHERE company_id = $clerkCoyId"))[0];

// ── 2. Service-status breakdown (from personnel table — no date dependency) ──
$serviceBreakdown = mysqli_fetch_all(mysqli_query($conn,
    "SELECT service_status AS label, COUNT(*) AS cnt
     FROM personnel
     WHERE company_id = $clerkCoyId
     GROUP BY service_status
     ORDER BY FIELD(service_status,'Serving','Attached Out','Posted Out','Retired','Other')"),
    MYSQLI_ASSOC);

$servingCount = 0;
foreach ($serviceBreakdown as $sb) {
    if ($sb['label'] === 'Serving') $servingCount = (int)$sb['cnt'];
}

// ── 3. Attendance breakdown for viewDate (ALL personnel in the company) ──
$attendanceBreakdown = mysqli_fetch_all(mysqli_query($conn,
    "SELECT COALESCE(a.status, 'Not Entered') AS label, COUNT(*) AS cnt
     FROM personnel p
     LEFT JOIN attendance a ON a.personnel_id = p.id AND a.attendance_date = '$viewDate'
     WHERE p.company_id = $clerkCoyId
     GROUP BY COALESCE(a.status, 'Not Entered')
     ORDER BY cnt DESC"),
    MYSQLI_ASSOC);

$presentCount   = 0;
$notEnteredCount = 0;
foreach ($attendanceBreakdown as $ab) {
    if ($ab['label'] === 'Present')     $presentCount    = (int)$ab['cnt'];
    if ($ab['label'] === 'Not Entered') $notEnteredCount = (int)$ab['cnt'];
}

$presentPct = $totalOnRoll > 0 ? round($presentCount / $totalOnRoll * 100) : 0;

// ── 4. 14-day present trend (total = totalOnRoll for all dates) ──
$trend = mysqli_fetch_all(mysqli_query($conn,
    "SELECT d.dt AS attendance_date,
            COALESCE(SUM(a.status = 'Present'), 0) AS present,
            $totalOnRoll AS total
     FROM (
         SELECT DATE_SUB('$today', INTERVAL seq DAY) AS dt
         FROM (
             SELECT 0 AS seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
             UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
             UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11
             UNION SELECT 12 UNION SELECT 13
         ) n
     ) d
     LEFT JOIN (
         SELECT a.attendance_date, a.status
         FROM attendance a
         JOIN personnel p ON p.id = a.personnel_id
         WHERE p.company_id = $clerkCoyId
     ) a ON a.attendance_date = d.dt
     GROUP BY d.dt
     ORDER BY d.dt ASC"),
    MYSQLI_ASSOC);

// ── Colour maps ──
$serviceColors = [
    'Serving'      => '#22863a',
    'Attached Out' => '#c9a227',
    'Posted Out'   => '#2471a3',
    'Retired'      => '#7a8fa6',
    'Other'        => '#e07b2a',
];
$attendanceColors = [
    'Present'     => '#22863a',
    'Absent'      => '#c0392b',
    'Leave'       => '#2471a3',
    'Course'      => '#7d3c98',
    'Duty'        => '#d68910',
    'Sick Report' => '#cb4335',
    'MH'          => '#1abc9c',
    'TD'          => '#5d6d7e',
    'Not Entered' => '#4a5568',
    'Other'       => '#95a5a6',
];

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
        <a href="../dashboard.php">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Company Strength</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;vertical-align:-4px;margin-right:6px;">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                <?php echo h($coyName); ?> — Strength
            </h1>
            <p class="page-sub">
                Nominal roll &amp; attendance strength for
                <?php echo h(date('d M Y', strtotime($viewDate))); ?>.
            </p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
            <div class="tf">
                <label for="v-date" style="font-size:11px;">Date</label>
                <input type="date" id="v-date" name="date"
                       value="<?php echo h($viewDate); ?>"
                       style="min-height:38px;margin:0;">
            </div>
            <button class="btn secondary" type="submit">Go</button>
        </form>
    </div>

    <!-- ── Summary Tiles ── -->
    <div class="summary-tiles">
        <div class="summary-tile">
            <strong><?php echo $totalOnRoll; ?></strong>
            <span>Total on Roll</span>
        </div>
        <div class="summary-tile" style="border-left-color:#22863a;">
            <strong style="color:#22863a;"><?php echo $servingCount; ?></strong>
            <span>Currently Serving</span>
        </div>
        <div class="summary-tile green">
            <strong><?php echo $presentCount; ?></strong>
            <span>Present (<?php echo h($viewDate === $today ? 'Today' : date('d M', strtotime($viewDate))); ?>)</span>
        </div>
        <div class="summary-tile" style="border-left-color:<?php echo $presentPct>=80?'#22863a':($presentPct>=60?'#c9a227':'#c0392b'); ?>">
            <strong style="color:<?php echo $presentPct>=80?'#22863a':($presentPct>=60?'#c9a227':'#c0392b'); ?>">
                <?php echo $presentPct; ?>%
            </strong>
            <span>Present Rate</span>
        </div>
        <?php if ($notEnteredCount > 0): ?>
        <div class="summary-tile" style="border-left-color:#e07b2a;">
            <strong style="color:#e07b2a;"><?php echo $notEnteredCount; ?></strong>
            <span>Attendance Pending</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Charts Row ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;align-items:start;margin-bottom:20px;">

        <!-- Service Status Donut -->
        <div class="panel" style="text-align:center;">
            <h2 style="font-size:13px;margin-bottom:14px;color:var(--muted);">SERVICE STATUS</h2>
            <p style="font-size:11px;color:var(--muted);margin:-10px 0 12px;">From Nominal Roll</p>
            <div class="chart-wrap" style="height:180px;">
                <canvas id="serviceChart"></canvas>
            </div>
            <div style="margin-top:14px;display:grid;gap:5px;text-align:left;">
                <?php foreach ($serviceBreakdown as $sb):
                    $clr = $serviceColors[$sb['label']] ?? '#7a8fa6';
                    $pct = $totalOnRoll > 0 ? round($sb['cnt'] / $totalOnRoll * 100) : 0;
                ?>
                <div class="legend-item">
                    <span class="legend-swatch" style="background:<?php echo $clr; ?>"></span>
                    <span style="flex:1;"><?php echo h($sb['label']); ?></span>
                    <span class="mono-sm" style="color:<?php echo $clr; ?>;font-weight:700;"><?php echo $sb['cnt']; ?></span>
                    <span class="mono-sm text-muted" style="width:32px;text-align:right;"><?php echo $pct; ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Attendance Status Donut -->
        <div class="panel" style="text-align:center;">
            <h2 style="font-size:13px;margin-bottom:14px;color:var(--muted);">ATTENDANCE STATUS</h2>
            <p style="font-size:11px;color:var(--muted);margin:-10px 0 12px;">For <?php echo h(date('d M Y', strtotime($viewDate))); ?></p>
            <?php if (empty($attendanceBreakdown)): ?>
                <p class="muted" style="font-size:13px;padding:40px 0;">No data for this date.</p>
            <?php else: ?>
            <div class="chart-wrap" style="height:180px;">
                <canvas id="attendanceChart"></canvas>
            </div>
            <div style="margin-top:14px;display:grid;gap:5px;text-align:left;">
                <?php foreach ($attendanceBreakdown as $ab):
                    $clr = $attendanceColors[$ab['label']] ?? '#7a8fa6';
                    $pct = $totalOnRoll > 0 ? round($ab['cnt'] / $totalOnRoll * 100) : 0;
                ?>
                <div class="legend-item">
                    <span class="legend-swatch" style="background:<?php echo $clr; ?>"></span>
                    <span style="flex:1;"><?php echo h($ab['label']); ?></span>
                    <span class="mono-sm" style="color:<?php echo $clr; ?>;font-weight:700;"><?php echo $ab['cnt']; ?></span>
                    <span class="mono-sm text-muted" style="width:32px;text-align:right;"><?php echo $pct; ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 14-day Trend -->
        <div class="panel">
            <h2 style="font-size:13px;margin-bottom:4px;color:var(--muted);">14-DAY TREND</h2>
            <p style="font-size:11px;color:var(--muted);margin:0 0 14px;">Present vs Total on Roll</p>
            <div class="chart-wrap" style="height:220px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── Detail Tables ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- Service Status Table -->
        <div class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 18px;border-bottom:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;">
                <strong style="font-size:13px;">Service Status Breakdown</strong>
                <span class="mono-sm text-muted">Total: <?php echo $totalOnRoll; ?></span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="num">Count</th>
                            <th class="num">%</th>
                            <th>Bar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($serviceBreakdown as $sb):
                        $clr  = $serviceColors[$sb['label']] ?? '#7a8fa6';
                        $sPct = $totalOnRoll > 0 ? round($sb['cnt'] / $totalOnRoll * 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <span class="status-badge"
                                  style="background:<?php echo $clr; ?>22;color:<?php echo $clr; ?>;border:1px solid <?php echo $clr; ?>44;">
                                <?php echo h($sb['label']); ?>
                            </span>
                        </td>
                        <td class="num fw-bold" style="color:<?php echo $clr; ?>"><?php echo $sb['cnt']; ?></td>
                        <td class="num"><?php echo $sPct; ?>%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?php echo $sPct; ?>%;background:<?php echo $clr; ?>;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--gold-muted);">
                            <th>Total</th>
                            <th class="num"><?php echo $totalOnRoll; ?></th>
                            <th class="num">100%</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="panel" style="padding:0;overflow:hidden;">
            <div style="padding:12px 18px;border-bottom:1px solid var(--border-subtle);display:flex;justify-content:space-between;align-items:center;">
                <strong style="font-size:13px;">Attendance — <?php echo h(date('d M Y', strtotime($viewDate))); ?></strong>
                <span class="mono-sm text-muted">Total: <?php echo $totalOnRoll; ?></span>
            </div>
            <div class="table-wrap">
                <?php if (empty($attendanceBreakdown)): ?>
                <p class="muted" style="padding:20px;text-align:center;">No attendance data for this date.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Attendance</th>
                            <th class="num">Count</th>
                            <th class="num">%</th>
                            <th>Bar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attendanceBreakdown as $ab):
                        $clr  = $attendanceColors[$ab['label']] ?? '#7a8fa6';
                        $aPct = $totalOnRoll > 0 ? round($ab['cnt'] / $totalOnRoll * 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <span class="status-badge"
                                  style="background:<?php echo $clr; ?>22;color:<?php echo $clr; ?>;border:1px solid <?php echo $clr; ?>44;">
                                <?php echo h($ab['label']); ?>
                            </span>
                        </td>
                        <td class="num fw-bold" style="color:<?php echo $clr; ?>"><?php echo $ab['cnt']; ?></td>
                        <td class="num"><?php echo $aPct; ?>%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?php echo $aPct; ?>%;background:<?php echo $clr; ?>;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--gold-muted);">
                            <th>Total</th>
                            <th class="num"><?php echo $totalOnRoll; ?></th>
                            <th class="num">100%</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</main>

<script>
var svcColors = <?php echo json_encode($serviceColors); ?>;
var attColors = <?php echo json_encode($attendanceColors); ?>;

// ── Service Status Donut ──────────────────────────────────────────────────
var svcData = <?php echo json_encode(array_values($serviceBreakdown)); ?>;
if (svcData.length) {
    new Chart(document.getElementById('serviceChart'), {
        type: 'doughnut',
        data: {
            labels: svcData.map(d => d.label),
            datasets: [{
                data: svcData.map(d => parseInt(d.cnt)),
                backgroundColor: svcData.map(d => svcColors[d.label] || '#7a8fa6'),
                borderWidth: 2,
                borderColor: '#132b45'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '60%'
        }
    });
}

// ── Attendance Status Donut ───────────────────────────────────────────────
var attData = <?php echo json_encode(array_values($attendanceBreakdown)); ?>;
if (attData.length && document.getElementById('attendanceChart')) {
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: attData.map(d => d.label),
            datasets: [{
                data: attData.map(d => parseInt(d.cnt)),
                backgroundColor: attData.map(d => attColors[d.label] || '#7a8fa6'),
                borderWidth: 2,
                borderColor: '#132b45'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '60%'
        }
    });
}

// ── 14-Day Trend Line ─────────────────────────────────────────────────────
var trendData = <?php echo json_encode(array_values($trend)); ?>;
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendData.map(d => d.attendance_date.substr(5)),
        datasets: [
            {
                label: 'Present',
                data: trendData.map(d => parseInt(d.present) || 0),
                borderColor: '#22863a',
                backgroundColor: 'rgba(34,134,58,.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 4
            },
            {
                label: 'Total on Roll',
                data: trendData.map(d => parseInt(d.total)),
                borderColor: '#c9a227',
                borderDash: [5, 4],
                backgroundColor: 'transparent',
                fill: false,
                tension: 0,
                pointRadius: 0
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top', labels: { color: '#8fa8c6', boxWidth: 12 } } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
