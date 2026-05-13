<?php
session_start();
if (!isset($_SESSION["role"])) { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$fMonth = $_GET["month"] ?? date("Y-m");
if (!preg_match('/^\d{4}-\d{2}$/', $fMonth)) $fMonth = date("Y-m");
$fCoy   = (int)($_GET["coy"] ?? 0);

$monthStart = $fMonth . "-01";
$monthEnd   = date("Y-m-t", strtotime($monthStart));

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name, short_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

// Daily summary for the selected month
$whereExtra = $fCoy ? "AND p.company_id = $fCoy" : "";
$dailySummary = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.attendance_date,
            COUNT(CASE WHEN a.status='Present' THEN 1 END) AS present,
            COUNT(CASE WHEN a.status='Absent'  THEN 1 END) AS absent,
            COUNT(CASE WHEN a.status='Leave'   THEN 1 END) AS on_leave,
            COUNT(*) AS total_entered
     FROM attendance a
     JOIN personnel p ON p.id = a.personnel_id
     WHERE a.attendance_date BETWEEN '$monthStart' AND '$monthEnd' $whereExtra
     GROUP BY a.attendance_date
     ORDER BY a.attendance_date DESC
     LIMIT 62"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Archive | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="#">Reports</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Archive</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>Archive</h1>
            <p class="page-sub">Historical parade state records — <?php echo h(date('F Y', strtotime($monthStart))); ?>.</p>
        </div>
    </div>

    <form method="GET" class="table-toolbar">
        <div class="tf"><label for="f-month">Month</label>
        <input type="month" id="f-month" name="month" value="<?php echo h($fMonth); ?>"></div>
        <div class="tf"><label for="f-coy">Company</label>
        <select id="f-coy" name="coy">
            <option value="0">All Companies</option>
            <?php foreach ($companies as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $fCoy === (int)$c['id'] ? 'selected' : ''; ?>><?php echo h($c['company_name']); ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="toolbar-actions"><button class="btn secondary" type="submit">Apply</button></div>
    </form>

    <?php if (empty($dailySummary)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <h3>No Records Found</h3>
            <p>No attendance data for <?php echo h(date('F Y', strtotime($monthStart))); ?><?php echo $fCoy ? ' for the selected company' : ''; ?>.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;"><?php echo count($dailySummary); ?> day(s) with records in <?php echo h(date('F Y', strtotime($monthStart))); ?></p>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th class="num" style="color:var(--status-present)">Present</th>
                        <th class="num" style="color:var(--status-absent)">Absent</th>
                        <th class="num">On Leave</th>
                        <th class="num">Total Entered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dailySummary as $row):
                    $pct = $row['total_entered'] > 0 ? round($row['present'] / $row['total_entered'] * 100) : 0;
                    $barColor = $pct >= 80 ? 'var(--status-present)' : ($pct >= 60 ? 'var(--saffron)' : 'var(--status-absent)');
                ?>
                <tr>
                    <td class="mono"><?php echo h($row['attendance_date']); ?></td>
                    <td class="text-muted"><?php echo h(date('D', strtotime($row['attendance_date']))); ?></td>
                    <td class="num text-green fw-bold"><?php echo $row['present']; ?></td>
                    <td class="num text-red"><?php echo $row['absent']; ?></td>
                    <td class="num"><?php echo $row['on_leave']; ?></td>
                    <td class="num"><?php echo $row['total_entered']; ?></td>
                    <td>
                        <a class="btn secondary" style="padding:4px 10px;font-size:12px;"
                           href="daily_parade_state.php?date=<?php echo h($row['attendance_date']); ?><?php echo $fCoy ? '&company='.$fCoy : ''; ?>">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
