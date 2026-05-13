<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$userId  = (int)$_SESSION["user_id"];
$message = '';
$msgType = 'info';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $action = $_POST["action"] ?? '';
    $id     = (int)($_POST["leave_id"] ?? 0);

    if (in_array($action, ['approve','reject'], true) && $id > 0) {
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
        $stmt = mysqli_prepare($conn,
            "UPDATE leave_records SET approval_status=? WHERE id=? AND approval_status='Pending'");
        mysqli_stmt_bind_param($stmt, 'si', $newStatus, $id);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = $n ? "Leave record $newStatus." : "Record already processed.";
        $msgType = $action === 'approve' ? 'success' : 'error';
    }

    if ($action === 'mark_returned') {
        $id = (int)($_POST["leave_id"] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE leave_records SET return_status='Returned' WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "Personnel marked as Returned.";
            $msgType = 'success';
        }
    }
}

$fCoy    = (int)($_GET["coy"] ?? 0);
$fStatus = in_array($_GET["status"] ?? '', ['All','Pending','Approved','Rejected']) ? $_GET["status"] : 'Pending';
$fType   = trim($_GET["type"] ?? '');

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

// Stats
$statsRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(approval_status='Pending') AS pending,
            SUM(approval_status='Approved' AND return_status='Not Returned') AS active_leave,
            SUM(return_status='Returned') AS returned,
            COUNT(*) AS total
     FROM leave_records"));
$stats = $statsRow ?: ['pending'=>0,'active_leave'=>0,'returned'=>0,'total'=>0];

$whereParts = ['1=1'];
if ($fCoy) $whereParts[] = "p.company_id = $fCoy";
if ($fStatus !== 'All') $whereParts[] = "lr.approval_status = '$fStatus'";
if ($fType !== '') $whereParts[] = "lr.leave_type = '" . mysqli_real_escape_string($conn, $fType) . "'";
$where = implode(' AND ', $whereParts);

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT lr.id, lr.leave_type, lr.from_date, lr.to_date, lr.approval_status,
            lr.return_status, lr.remarks,
            p.army_no, p.rank_name, p.full_name, p.id AS pid,
            c.company_name, c.short_name,
            DATEDIFF(lr.to_date, lr.from_date)+1 AS leave_days
     FROM leave_records lr
     JOIN personnel p ON p.id = lr.personnel_id
     JOIN companies c ON c.id = p.company_id
     WHERE $where
     ORDER BY lr.approval_status='Pending' DESC, lr.from_date DESC
     LIMIT 400"), MYSQLI_ASSOC);

$leaveTypes = mysqli_fetch_all(mysqli_query($conn,
    "SELECT DISTINCT leave_type FROM leave_records ORDER BY leave_type"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Approve Leave | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Approve Leave</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Approve Leave</h1>
        <p class="page-sub">Review, approve or reject leave applications across companies.</p>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $msgType === 'success' ? 'success' : ''; ?>" role="alert"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="summary-tiles">
        <div class="summary-tile orange"><strong><?php echo (int)$stats['pending']; ?></strong><span>Pending</span></div>
        <div class="summary-tile blue"><strong><?php echo (int)$stats['active_leave']; ?></strong><span>On Leave</span></div>
        <div class="summary-tile green"><strong><?php echo (int)$stats['returned']; ?></strong><span>Returned</span></div>
        <div class="summary-tile"><strong><?php echo (int)$stats['total']; ?></strong><span>Total Records</span></div>
    </div>

    <form method="GET" class="table-toolbar">
        <div class="tf">
            <label for="f-coy">Company</label>
            <select id="f-coy" name="coy">
                <option value="0">All Companies</option>
                <?php foreach ($companies as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $fCoy==$c['id']?'selected':''; ?>><?php echo h($c['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tf">
            <label for="f-status">Status</label>
            <select id="f-status" name="status">
                <?php foreach (['All','Pending','Approved','Rejected'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $fStatus===$s?'selected':''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tf">
            <label for="f-type">Leave Type</label>
            <select id="f-type" name="type">
                <option value="">All Types</option>
                <?php foreach ($leaveTypes as $lt): ?>
                <option value="<?php echo h($lt['leave_type']); ?>" <?php echo $fType===$lt['leave_type']?'selected':''; ?>><?php echo h($lt['leave_type']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar-actions"><button class="btn secondary" type="submit">Apply</button></div>
    </form>

    <?php if (empty($records)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <h3>No Leave Records</h3>
            <p>No leave records match the current filter criteria.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
        </div>
        <div class="table-wrap">
            <table aria-label="Leave records">
                <thead>
                    <tr>
                        <th>Army No</th>
                        <th>Name</th>
                        <th>Coy</th>
                        <th>Leave Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="num">Days</th>
                        <th>Approval</th>
                        <th>Return</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $r):
                    $isPending  = $r['approval_status'] === 'Pending';
                    $isApproved = $r['approval_status'] === 'Approved';
                    $notRet     = $isApproved && $r['return_status'] === 'Not Returned';
                ?>
                <tr>
                    <td class="mono"><?php echo h($r['army_no']); ?></td>
                    <td>
                        <span class="status-badge status-duty" style="font-size:10px;margin-right:4px;"><?php echo h($r['rank_name']); ?></span><?php echo h($r['full_name']); ?>
                    </td>
                    <td><?php echo h($r['short_name']); ?></td>
                    <td><?php echo h($r['leave_type']); ?></td>
                    <td class="mono"><?php echo h(date('d M Y', strtotime($r['from_date']))); ?></td>
                    <td class="mono"><?php echo h(date('d M Y', strtotime($r['to_date']))); ?></td>
                    <td class="num"><?php echo (int)$r['leave_days']; ?></td>
                    <td>
                        <span class="status-badge <?php echo match($r['approval_status']){'Approved'=>'status-present','Rejected'=>'status-absent',default=>'status-duty'}; ?>"><?php echo h($r['approval_status']); ?></span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $r['return_status']==='Returned'?'status-present':'status-absent'; ?>"><?php echo h($r['return_status']); ?></span>
                    </td>
                    <td>
                        <div class="action-group">
                        <?php if ($isPending): ?>
                            <form method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="leave_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn approve sm" type="submit" aria-label="Approve leave">✓</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="leave_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn reject sm" type="submit" aria-label="Reject leave">✗</button>
                            </form>
                        <?php elseif ($notRet): ?>
                            <form method="POST" style="display:inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_returned">
                                <input type="hidden" name="leave_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn secondary sm" type="submit">Returned</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
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
<?php if ($message): ?>
<script>showToast(<?php echo json_encode($message); ?>, '<?php echo $msgType; ?>');</script>
<?php endif; ?>
</body>
</html>
