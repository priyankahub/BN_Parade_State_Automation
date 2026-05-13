<?php
session_start();
if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["ADJT_SA","ADMIN"])) {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$userId  = (int)$_SESSION["user_id"];
$message = ''; $msgType = 'info';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $action  = $_POST["action"] ?? '';
    $fDate   = $_POST["date"] ?? date("Y-m-d");
    $fCoy    = (int)($_POST["coy"] ?? 0);

    if ($action === "approve_all") {
        $whereCoy = $fCoy ? "AND p.company_id = $fCoy" : '';
        $stmt = mysqli_prepare($conn,
            "UPDATE attendance a
             JOIN personnel p ON p.id = a.personnel_id
             SET a.approval_status='Approved', a.approved_by=?, a.approved_at=NOW()
             WHERE a.attendance_date=? AND a.approval_status='Pending' $whereCoy");
        mysqli_stmt_bind_param($stmt, 'is', $userId, $fDate);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = "Approved $n attendance record(s) for " . date('d M Y', strtotime($fDate)) . ".";
        $msgType = 'success';
    }

    if ($action === "single") {
        $id = (int)($_POST["att_id"] ?? 0);
        $act = $_POST["sub_action"] ?? 'approve';
        $newStatus = $act === 'approve' ? 'Approved' : 'Rejected';
        $stmt = mysqli_prepare($conn,
            "UPDATE attendance SET approval_status=?, approved_by=?, approved_at=NOW()
             WHERE id=? AND approval_status='Pending'");
        mysqli_stmt_bind_param($stmt, 'sii', $newStatus, $userId, $id);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = $n ? "Record $newStatus." : "Record already processed.";
        $msgType = $act === 'approve' ? 'success' : 'error';
    }
}

$fDate = $_GET["date"] ?? date("Y-m-d");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate)) $fDate = date("Y-m-d");
$fCoy  = (int)($_GET["coy"] ?? 0);

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

// Stats
$statsRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(a.approval_status='Pending') AS pending,
            SUM(a.approval_status='Approved') AS approved,
            COUNT(*) AS total
     FROM attendance a
     JOIN personnel p ON p.id=a.personnel_id
     WHERE a.attendance_date='$fDate'" . ($fCoy ? " AND p.company_id=$fCoy" : '')));
$stats = $statsRow ?: ['pending'=>0,'approved'=>0,'total'=>0];

$whereParts = ["a.attendance_date='$fDate'", "a.approval_status='Pending'"];
if ($fCoy) $whereParts[] = "p.company_id=$fCoy";

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.id, a.status, a.remarks,
            p.army_no, p.rank_name, p.full_name,
            c.short_name, c.company_name
     FROM attendance a
     JOIN personnel p ON p.id=a.personnel_id
     JOIN companies c ON c.id=p.company_id
     WHERE " . implode(' AND ', $whereParts) . "
     ORDER BY c.id, p.rank_name, p.full_name LIMIT 600"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Approve Parade State | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="battalion_parade_state.php">Parade State</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Approve</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>Approve Parade State</h1>
        <p class="page-sub">Review and approve pending attendance for <?php echo h(date('D, d M Y', strtotime($fDate))); ?>.</p>
    </div>

    <?php if ($message): ?><div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div><?php endif; ?>

    <div class="summary-tiles">
        <div class="summary-tile orange"><strong><?php echo (int)$stats['pending']; ?></strong><span>Pending</span></div>
        <div class="summary-tile green"><strong><?php echo (int)$stats['approved']; ?></strong><span>Approved</span></div>
        <div class="summary-tile"><strong><?php echo (int)$stats['total']; ?></strong><span>Total</span></div>
    </div>

    <form method="GET" class="table-toolbar">
        <div class="tf"><label for="f-date">Date</label>
        <input type="date" id="f-date" name="date" value="<?php echo h($fDate); ?>"></div>
        <div class="tf"><label for="f-coy">Company</label>
        <select id="f-coy" name="coy">
            <option value="0">All Companies</option>
            <?php foreach ($companies as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php echo $fCoy==$c['id']?'selected':''; ?>><?php echo h($c['company_name']); ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="toolbar-actions">
            <button class="btn secondary" type="submit">Filter</button>
        </div>
    </form>

    <?php if ((int)$stats['pending'] > 0): ?>
    <form method="POST" style="margin-bottom:20px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="approve_all">
        <input type="hidden" name="date" value="<?php echo h($fDate); ?>">
        <input type="hidden" name="coy" value="<?php echo $fCoy; ?>">
        <button class="btn approve" type="submit"
                onclick="return confirm('Approve all <?php echo (int)$stats['pending']; ?> pending record(s)?')">
            ✓ Bulk Approve All Pending (<?php echo (int)$stats['pending']; ?>)
        </button>
    </form>
    <?php endif; ?>

    <?php if (empty($records)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            <h3>All Clear</h3>
            <p>No pending records for the selected date<?php echo $fCoy ? ' and company' : ''; ?>.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;"><?php echo count($records); ?> pending record(s)</p>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Coy</th><th>Army No</th><th>Name</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?php echo h($r['short_name']); ?></td>
                    <td class="mono"><?php echo h($r['army_no']); ?></td>
                    <td><span class="status-badge status-duty" style="font-size:10px;margin-right:4px;"><?php echo h($r['rank_name']); ?></span><?php echo h($r['full_name']); ?></td>
                    <td><span class="status-badge status-<?php echo strtolower(str_replace(' ','-',$r['status'])); ?>"><?php echo h($r['status']); ?></span></td>
                    <td class="text-muted"><?php echo h($r['remarks'] ?: '—'); ?></td>
                    <td>
                        <div class="action-group">
                            <form method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="single">
                                <input type="hidden" name="sub_action" value="approve">
                                <input type="hidden" name="att_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="date" value="<?php echo h($fDate); ?>">
                                <input type="hidden" name="coy" value="<?php echo $fCoy; ?>">
                                <button class="btn approve sm" type="submit">✓</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="single">
                                <input type="hidden" name="sub_action" value="reject">
                                <input type="hidden" name="att_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="date" value="<?php echo h($fDate); ?>">
                                <input type="hidden" name="coy" value="<?php echo $fCoy; ?>">
                                <button class="btn reject sm" type="submit">✗</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php if ($message): ?><script>showToast(<?php echo json_encode($message); ?>,'<?php echo $msgType; ?>');</script><?php endif; ?>
</body>
</html>
