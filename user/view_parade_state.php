<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "USER") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today    = date("Y-m-d");
$viewDate = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

// Battalion-level summary per company
$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.id, c.company_name, c.short_name,
            COUNT(CASE WHEN p.service_status='Serving' THEN 1 END) AS total,
            COUNT(CASE WHEN a.status='Present'      THEN 1 END) AS present,
            COUNT(CASE WHEN a.status='Absent'       THEN 1 END) AS absent,
            COUNT(CASE WHEN a.status='Leave'        THEN 1 END) AS on_leave,
            COUNT(CASE WHEN a.status='Course'       THEN 1 END) AS on_course,
            COUNT(CASE WHEN a.status='Duty'         THEN 1 END) AS on_duty,
            COUNT(CASE WHEN a.status IN ('Sick Report','MH') THEN 1 END) AS sick,
            COUNT(CASE WHEN a.status='TD'           THEN 1 END) AS td
     FROM companies c
     LEFT JOIN personnel p ON p.company_id = c.id AND p.service_status = 'Serving'
     LEFT JOIN attendance a ON a.personnel_id = p.id AND a.attendance_date = '$viewDate'
     WHERE c.is_active = 1
     GROUP BY c.id
     ORDER BY c.id"), MYSQLI_ASSOC);

$totals = ['total'=>0,'present'=>0,'absent'=>0,'on_leave'=>0,'on_course'=>0,'on_duty'=>0,'sick'=>0,'td'=>0];
foreach ($companies as $c) {
    foreach ($totals as $k => $_) $totals[$k] += (int)$c[$k];
}

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>View Parade State | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Parade State</span>
    </nav>

    <div class="panel">
        <div class="page-header-row">
            <div class="page-header">
                <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Battalion Parade State</h1>
                <p class="page-sub">Read-only view — <?php echo h(date('D, d M Y', strtotime($viewDate))); ?></p>
            </div>
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <label style="margin:0;font-size:13px;text-transform:none;">Date:</label>
                <input type="date" name="date" value="<?php echo h($viewDate); ?>"
                       style="padding:6px 10px;font-size:13px;margin:0;width:auto;">
                <button class="btn secondary" type="submit" style="padding:6px 14px;">Go</button>
            </form>
        </div>
    </div>

    <div class="summary-tiles">
        <div class="summary-tile green"><strong><?php echo $totals['present']; ?></strong><span>Present</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['absent']; ?></strong><span>Absent</span></div>
        <div class="summary-tile blue"><strong><?php echo $totals['on_leave']; ?></strong><span>On Leave</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['on_course']; ?></strong><span>On Course</span></div>
        <div class="summary-tile"><strong><?php echo $totals['on_duty']; ?></strong><span>On Duty</span></div>
        <div class="summary-tile"><strong><?php echo $totals['sick']; ?></strong><span>Sick/MH</span></div>
        <div class="summary-tile"><strong><?php echo $totals['total']; ?></strong><span>Total Serving</span></div>
    </div>

    <?php if (empty($companies)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No Companies Found</h3>
            <p>No active companies are configured in the system.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th class="num">Total</th>
                        <th class="num" style="color:var(--status-present)">Present</th>
                        <th class="num" style="color:var(--status-absent)">Absent</th>
                        <th class="num">Leave</th>
                        <th class="num">Course</th>
                        <th class="num">Duty</th>
                        <th class="num">Sick/MH</th>
                        <th class="num">TD</th>
                        <th>Strength</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($companies as $c):
                    $pct = $c['total'] > 0 ? round($c['present'] / $c['total'] * 100) : 0;
                    $barColor = $pct >= 80 ? 'var(--status-present)' : ($pct >= 60 ? 'var(--saffron)' : 'var(--status-absent)');
                ?>
                <tr>
                    <td><strong><?php echo h($c['short_name'] ?: $c['company_name']); ?></strong></td>
                    <td class="num fw-bold"><?php echo $c['total']; ?></td>
                    <td class="num text-green fw-bold"><?php echo $c['present']; ?></td>
                    <td class="num text-red"><?php echo $c['absent']; ?></td>
                    <td class="num"><?php echo $c['on_leave']; ?></td>
                    <td class="num"><?php echo $c['on_course']; ?></td>
                    <td class="num"><?php echo $c['on_duty']; ?></td>
                    <td class="num"><?php echo $c['sick']; ?></td>
                    <td class="num"><?php echo $c['td']; ?></td>
                    <td class="strength-bar-cell">
                        <div class="progress-bar" style="width:120px;" title="<?php echo $pct; ?>% present">
                            <div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>;"></div>
                        </div>
                        <span class="strength-pct"><?php echo $pct; ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:600;border-top:2px solid var(--border-subtle);">
                        <td>TOTAL</td>
                        <td class="num"><?php echo $totals['total']; ?></td>
                        <td class="num text-green"><?php echo $totals['present']; ?></td>
                        <td class="num text-red"><?php echo $totals['absent']; ?></td>
                        <td class="num"><?php echo $totals['on_leave']; ?></td>
                        <td class="num"><?php echo $totals['on_course']; ?></td>
                        <td class="num"><?php echo $totals['on_duty']; ?></td>
                        <td class="num"><?php echo $totals['sick']; ?></td>
                        <td class="num"><?php echo $totals['td']; ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
