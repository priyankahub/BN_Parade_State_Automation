<?php
session_start();
if (!isset($_SESSION["role"])) { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today    = date("Y-m-d");
$viewDate = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

$rows = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.id, c.company_name, c.short_name,
            -- strength_on_record: authoritative count from personnel table (never 0 due to missing attendance)
            (SELECT COUNT(*) FROM personnel WHERE company_id = c.id AND service_status = 'Serving') AS total_serving,
            SUM(a.status = 'Present')  AS present,
            SUM(a.status = 'Absent')   AS absent,
            SUM(a.status = 'Leave')    AS on_leave,
            SUM(a.status = 'Course')   AS on_course,
            SUM(a.status = 'Duty')     AS on_duty,
            SUM(a.status IN ('Sick Report','MH')) AS sick,
            SUM(a.status = 'TD')       AS td,
            SUM(a.status IN ('Attached Out','Other')) AS others,
            SUM(a.status IS NULL AND p.id IS NOT NULL) AS not_entered
     FROM companies c
     LEFT JOIN personnel p ON p.company_id = c.id AND p.service_status = 'Serving'
     LEFT JOIN attendance a ON a.personnel_id = p.id AND a.attendance_date = '$viewDate'
     WHERE c.is_active = 1
     GROUP BY c.id, c.company_name, c.short_name
     ORDER BY c.id"), MYSQLI_ASSOC);

// Totals
$totals = array_fill_keys(['total_serving','present','absent','on_leave','on_course','on_duty','sick','td','others','not_entered'], 0);
foreach ($rows as $r) foreach (array_keys($totals) as $k) $totals[$k] += (int)$r[$k];

$totalPresent = $totals['total_serving'] > 0 ? round($totals['present'] / $totals['total_serving'] * 100) : 0;

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Company Strength | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="../dashboard.php">Reports</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Company Strength</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>Company Strength Summary</h1>
            <p class="page-sub">Company-wise present and available strength for <?php echo h(date('d M Y', strtotime($viewDate))); ?>.</p>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
            <div class="tf">
                <label for="view-date" style="font-size:11px;">View Date</label>
                <input type="date" id="view-date" name="date" value="<?php echo h($viewDate); ?>" style="min-height:38px;margin:0;">
            </div>
            <button class="btn secondary" type="submit">Go</button>
        </form>
    </div>

    <div class="summary-tiles">
        <div class="summary-tile"><strong><?php echo $totals['total_serving']; ?></strong><span>Total Serving</span></div>
        <div class="summary-tile green"><strong><?php echo $totals['present']; ?></strong><span>Present</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['absent']; ?></strong><span>Absent</span></div>
        <div class="summary-tile blue"><strong><?php echo $totals['on_leave']; ?></strong><span>On Leave</span></div>
        <div class="summary-tile purple"><strong><?php echo $totals['on_course']; ?></strong><span>On Course</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['on_duty']; ?></strong><span>On Duty</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['sick']; ?></strong><span>Sick / MH</span></div>
        <div class="summary-tile"><strong><?php echo $totals['not_entered']; ?></strong><span>Not Entered</span></div>
    </div>

    <?php if (empty($rows)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No Data</h3>
            <p>No company data found. Ensure companies and personnel are configured.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
            <table aria-label="Company strength summary">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th class="num">Serving</th>
                        <th class="num">Present</th>
                        <th class="num">Absent</th>
                        <th class="num">Leave</th>
                        <th class="num">Course</th>
                        <th class="num">Duty</th>
                        <th class="num">Sick/MH</th>
                        <th class="num">TD</th>
                        <th class="num">Others</th>
                        <th class="num">Not Entered</th>
                        <th>Present %</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $pct = $r['total_serving'] > 0 ? round($r['present'] / $r['total_serving'] * 100) : 0;
                    $pctColor = $pct >= 80 ? 'var(--status-present)' : ($pct >= 60 ? 'var(--saffron)' : 'var(--status-absent)');
                ?>
                <tr>
                    <td><strong><?php echo h($r['company_name']); ?></strong> <span class="mono-sm text-muted"><?php echo h($r['short_name']); ?></span></td>
                    <td class="num fw-bold"><?php echo (int)$r['total_serving']; ?></td>
                    <td class="num text-green fw-bold"><?php echo (int)$r['present']; ?></td>
                    <td class="num text-red"><?php echo (int)$r['absent']; ?></td>
                    <td class="num text-blue"><?php echo (int)$r['on_leave']; ?></td>
                    <td class="num text-purple"><?php echo (int)$r['on_course']; ?></td>
                    <td class="num text-orange"><?php echo (int)$r['on_duty']; ?></td>
                    <td class="num text-red"><?php echo (int)$r['sick']; ?></td>
                    <td class="num"><?php echo (int)$r['td']; ?></td>
                    <td class="num"><?php echo (int)$r['others']; ?></td>
                    <td class="num text-orange"><?php echo (int)$r['not_entered']; ?></td>
                    <td>
                        <div class="strength-bar-cell">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $pctColor; ?>;"></div>
                            </div>
                            <span class="strength-pct" style="color:<?php echo $pctColor; ?>;"><?php echo $pct; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-elevated);border-top:2px solid var(--gold-muted);">
                        <th>Battalion Total</th>
                        <th class="num"><?php echo $totals['total_serving']; ?></th>
                        <th class="num text-green"><?php echo $totals['present']; ?></th>
                        <th class="num text-red"><?php echo $totals['absent']; ?></th>
                        <th class="num text-blue"><?php echo $totals['on_leave']; ?></th>
                        <th class="num text-purple"><?php echo $totals['on_course']; ?></th>
                        <th class="num text-orange"><?php echo $totals['on_duty']; ?></th>
                        <th class="num text-red"><?php echo $totals['sick']; ?></th>
                        <th class="num"><?php echo $totals['td']; ?></th>
                        <th class="num"><?php echo $totals['others']; ?></th>
                        <th class="num text-orange"><?php echo $totals['not_entered']; ?></th>
                        <th>
                            <span class="mono-sm" style="color:<?php echo $totalPresent>=80?'var(--status-present)':($totalPresent>=60?'var(--saffron)':'var(--status-absent)'); ?>">
                                <?php echo $totalPresent; ?>%
                            </span>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
