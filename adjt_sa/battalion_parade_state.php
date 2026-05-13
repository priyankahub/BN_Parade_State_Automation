<?php
session_start();
if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["ADJT_SA","ADMIN"])) {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today    = date("Y-m-d");
$viewDate = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

// Company-wise summary
$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.id, c.company_name, c.short_name,
            COUNT(DISTINCT p.id) AS total_serving,
            SUM(a.status='Present') AS present,
            SUM(a.status='Absent') AS absent,
            SUM(a.status='Leave') AS on_leave,
            SUM(a.status='Course') AS on_course,
            SUM(a.status='Duty') AS on_duty,
            SUM(a.status IN ('Sick Report','MH')) AS sick,
            SUM(a.status='TD') AS td,
            SUM(a.status IN ('Attached Out','Other')) AS others,
            SUM(a.status IS NULL AND p.id IS NOT NULL) AS not_entered,
            SUM(a.approval_status='Pending') AS pending_approval
     FROM companies c
     LEFT JOIN personnel p ON p.company_id=c.id AND p.service_status='Serving'
     LEFT JOIN attendance a ON a.personnel_id=p.id AND a.attendance_date='$viewDate'
     WHERE c.is_active=1
     GROUP BY c.id, c.company_name, c.short_name
     ORDER BY c.id"), MYSQLI_ASSOC);

$totals = array_fill_keys(['total_serving','present','absent','on_leave','on_course','on_duty','sick','td','others','not_entered','pending_approval'], 0);
foreach ($companies as $r) foreach (array_keys($totals) as $k) $totals[$k] += (int)$r[$k];

$bnPct = $totals['total_serving']>0 ? round($totals['present']/$totals['total_serving']*100) : 0;

// Pending approvals count
$pendingCount = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM attendance WHERE approval_status='Pending' AND attendance_date='$viewDate'"))[0];

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Battalion Parade State | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container wide-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Battalion Parade State</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Battalion Parade State</h1>
            <p class="page-sub">Live battalion strength for <?php echo h(date('D, d M Y', strtotime($viewDate))); ?>.
                <?php if ($pendingCount > 0): ?>
                <span style="color:var(--saffron);font-weight:600;"><?php echo $pendingCount; ?> pending approval(s).</span>
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
                <div class="tf"><label for="v-date" style="font-size:11px;">Date</label>
                <input type="date" id="v-date" name="date" value="<?php echo h($viewDate); ?>" style="min-height:38px;margin:0;"></div>
                <button class="btn secondary" type="submit">Go</button>
            </form>
            <?php if ($pendingCount > 0): ?>
            <a class="btn" href="approve_parade_state.php?date=<?php echo h($viewDate); ?>">Approve</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- BN totals -->
    <div class="summary-tiles">
        <div class="summary-tile"><strong><?php echo $totals['total_serving']; ?></strong><span>BN Strength</span></div>
        <div class="summary-tile green"><strong><?php echo $totals['present']; ?></strong><span>Present</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['absent']; ?></strong><span>Absent</span></div>
        <div class="summary-tile blue"><strong><?php echo $totals['on_leave']; ?></strong><span>On Leave</span></div>
        <div class="summary-tile purple"><strong><?php echo $totals['on_course']; ?></strong><span>On Course</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['on_duty']; ?></strong><span>On Duty</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['sick']; ?></strong><span>Sick/MH</span></div>
        <div class="summary-tile"><strong><?php echo $totals['td']; ?></strong><span>TD</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['not_entered']; ?></strong><span>Not Entered</span></div>
        <div class="summary-tile" style="border-left-color:<?php echo $bnPct>=80?'var(--status-present)':($bnPct>=60?'var(--saffron)':'var(--status-absent)'); ?>">
            <strong style="color:<?php echo $bnPct>=80?'var(--status-present)':($bnPct>=60?'var(--saffron)':'var(--status-absent)'); ?>"><?php echo $bnPct; ?>%</strong>
            <span>BN Present</span>
        </div>
    </div>

    <?php if (empty($companies)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No Data</h3>
            <p>No companies found. Please configure the battalion structure.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
            <table aria-label="Battalion parade state">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th class="num">Strength</th>
                        <th class="num text-green">Present</th>
                        <th class="num text-red">Absent</th>
                        <th class="num text-blue">Leave</th>
                        <th class="num text-purple">Course</th>
                        <th class="num text-orange">Duty</th>
                        <th class="num text-red">Sick</th>
                        <th class="num">TD</th>
                        <th class="num">Others</th>
                        <th class="num text-orange">N/E</th>
                        <th class="num text-orange">Pending</th>
                        <th>Present %</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($companies as $c):
                    $pct = $c['total_serving']>0 ? round($c['present']/$c['total_serving']*100) : 0;
                    $pctColor = $pct>=80?'var(--status-present)':($pct>=60?'var(--saffron)':'var(--status-absent)');
                ?>
                <tr>
                    <td><strong><?php echo h($c['company_name']); ?></strong></td>
                    <td class="num fw-bold"><?php echo (int)$c['total_serving']; ?></td>
                    <td class="num text-green fw-bold"><?php echo (int)$c['present']; ?></td>
                    <td class="num text-red"><?php echo (int)$c['absent']; ?></td>
                    <td class="num text-blue"><?php echo (int)$c['on_leave']; ?></td>
                    <td class="num text-purple"><?php echo (int)$c['on_course']; ?></td>
                    <td class="num text-orange"><?php echo (int)$c['on_duty']; ?></td>
                    <td class="num text-red"><?php echo (int)$c['sick']; ?></td>
                    <td class="num"><?php echo (int)$c['td']; ?></td>
                    <td class="num"><?php echo (int)$c['others']; ?></td>
                    <td class="num text-orange"><?php echo (int)$c['not_entered']; ?></td>
                    <td class="num"><?php echo $c['pending_approval']>0 ? '<span style="color:var(--saffron);font-weight:700;">'.(int)$c['pending_approval'].'</span>' : '—'; ?></td>
                    <td>
                        <div class="strength-bar-cell">
                            <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $pctColor; ?>;"></div></div>
                            <span class="strength-pct" style="color:<?php echo $pctColor; ?>"><?php echo $pct; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-elevated);border-top:2px solid var(--gold-muted);">
                        <th>BN Total</th>
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
                        <th class="num"><?php echo $totals['pending_approval']>0?'<span style="color:var(--saffron);font-weight:700;">'.$totals['pending_approval'].'</span>':'—'; ?></th>
                        <th><span class="mono-sm" style="color:<?php echo $bnPct>=80?'var(--status-present)':($bnPct>=60?'var(--saffron)':'var(--status-absent)'); ?>"><?php echo $bnPct; ?>%</span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
