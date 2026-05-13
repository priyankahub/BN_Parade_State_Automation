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
$fCoy     = (int)($_GET["coy"] ?? 0);
$fType    = trim($_GET["dtype"] ?? '');

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

$whereParts = ["dr.duty_date = '$viewDate'"];
if ($fCoy)  $whereParts[] = "p.company_id=$fCoy";
if ($fType) $whereParts[] = "dr.duty_type='" . mysqli_real_escape_string($conn, $fType) . "'";

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT dr.id, dr.duty_type, dr.duty_time, dr.location, dr.remarks,
            p.army_no, p.rank_name, p.full_name,
            c.company_name, c.short_name
     FROM duty_roster dr
     JOIN personnel p ON p.id = dr.personnel_id
     JOIN companies c ON c.id = p.company_id
     WHERE " . implode(' AND ', $whereParts) . "
     ORDER BY dr.duty_type, c.id, p.rank_name
     LIMIT 300"), MYSQLI_ASSOC);

// Summary by type
$typeSummary = mysqli_fetch_all(mysqli_query($conn,
    "SELECT dr.duty_type, COUNT(*) AS cnt
     FROM duty_roster dr
     JOIN personnel p ON p.id=dr.personnel_id
     WHERE dr.duty_date='$viewDate'"
     . ($fCoy ? " AND p.company_id=$fCoy" : '') . "
     GROUP BY dr.duty_type ORDER BY cnt DESC"), MYSQLI_ASSOC);

$dutyTypes = ['Guard','Sentry','QRT','Office Duty','Special Task','Other'];

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Duty Overview | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Duty Overview</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Duty Overview</h1>
            <p class="page-sub">Battalion duty assignments for <?php echo h(date('D, d M Y', strtotime($viewDate))); ?>.</p>
        </div>
    </div>

    <!-- Duty type summary -->
    <?php if (!empty($typeSummary)): ?>
    <div class="summary-tiles">
        <?php foreach ($typeSummary as $ts): ?>
        <div class="summary-tile orange"><strong><?php echo (int)$ts['cnt']; ?></strong><span><?php echo h($ts['duty_type']); ?></span></div>
        <?php endforeach; ?>
        <div class="summary-tile"><strong><?php echo count($records); ?></strong><span>Total On Duty</span></div>
    </div>
    <?php endif; ?>

    <form method="GET" class="table-toolbar">
        <div class="tf"><label for="f-date">Date</label>
        <input type="date" id="f-date" name="date" value="<?php echo h($viewDate); ?>"></div>
        <div class="tf"><label for="f-coy">Company</label>
        <select id="f-coy" name="coy">
            <option value="0">All Companies</option>
            <?php foreach ($companies as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $fCoy==$c['id']?'selected':''; ?>><?php echo h($c['company_name']); ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="tf"><label for="f-dtype">Duty Type</label>
        <select id="f-dtype" name="dtype">
            <option value="">All Types</option>
            <?php foreach ($dutyTypes as $dt): ?>
            <option value="<?php echo $dt; ?>" <?php echo $fType===$dt?'selected':''; ?>><?php echo $dt; ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="toolbar-actions"><button class="btn secondary" type="submit">Apply</button></div>
    </form>

    <?php if (empty($records)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <h3>No Duty Assignments</h3>
            <p>No duty records found for the selected date and filters.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;"><?php echo count($records); ?> personnel on duty</p>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Coy</th><th>Army No</th><th>Name</th><th>Duty Type</th><th>Hours</th><th>Location</th><th>Remarks</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?php echo h($r['short_name']); ?></td>
                    <td class="mono"><?php echo h($r['army_no']); ?></td>
                    <td><span class="status-badge status-duty" style="font-size:10px;margin-right:4px;"><?php echo h($r['rank_name']); ?></span><?php echo h($r['full_name']); ?></td>
                    <td><span class="status-badge status-duty"><?php echo h($r['duty_type']); ?></span></td>
                    <td class="mono-sm text-muted"><?php echo h($r['duty_time'] ?: '—'); ?></td>
                    <td><?php echo h($r['location'] ?: '—'); ?></td>
                    <td class="text-muted" style="font-size:12px;"><?php echo h($r['remarks'] ?: '—'); ?></td>
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
