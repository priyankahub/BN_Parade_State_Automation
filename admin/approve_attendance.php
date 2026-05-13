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
    $ids    = array_filter(array_map('intval', (array)($_POST["ids"] ?? [])));

    if (in_array($action, ['approve', 'reject'], true) && $ids) {
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($conn,
            "UPDATE attendance SET approval_status=?, approved_by=?, approved_at=NOW()
             WHERE id IN ($ph) AND approval_status='Pending'"
        );
        $params = array_merge([$newStatus, $userId], $ids);
        mysqli_stmt_bind_param($stmt, 'si' . str_repeat('i', count($ids)), ...$params);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = "$n record(s) $newStatus successfully.";
        $msgType = $action === 'approve' ? 'success' : 'error';
    }
}

// Filters
$fDate   = $_GET["date"] ?? date("Y-m-d");
$fCoy    = (int)($_GET["coy"] ?? 0);
$fStatus = in_array($_GET["status"] ?? '', ['All','Pending','Approved','Rejected']) ? $_GET["status"] : 'Pending';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fDate)) $fDate = date("Y-m-d");

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

// Stats for selected date
$statsRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(approval_status='Pending') AS pending,
            SUM(approval_status='Approved') AS approved,
            SUM(approval_status='Rejected') AS rejected,
            COUNT(*) AS total
     FROM attendance WHERE attendance_date = '$fDate'"));
$stats = $statsRow ?: ['pending'=>0,'approved'=>0,'rejected'=>0,'total'=>0];

// Records
$whereParts = ["a.attendance_date = '$fDate'"];
if ($fCoy)              $whereParts[] = "p.company_id = $fCoy";
if ($fStatus !== 'All') $whereParts[] = "a.approval_status = '$fStatus'";
$where = implode(' AND ', $whereParts);

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT a.id, a.status, a.remarks, a.approval_status, a.approved_at,
            p.army_no, p.rank_name, p.full_name,
            c.company_name, c.short_name,
            u.name AS appr_name
     FROM attendance a
     JOIN personnel p ON p.id = a.personnel_id
     JOIN companies c ON c.id = p.company_id
     LEFT JOIN users u ON u.id = a.approved_by
     WHERE $where
     ORDER BY c.id, p.rank_name, p.full_name
     LIMIT 600"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Approve Attendance | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <span class="breadcrumb-sep" aria-hidden="true">›</span>
        <span class="breadcrumb-current">Approve Attendance</span>
    </nav>

    <div class="page-header-row">
        <div class="page-header">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                Approve Attendance
            </h1>
            <p class="page-sub">Review and action submitted attendance entries for <?php echo h(date('d M Y', strtotime($fDate))); ?>.</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $msgType === 'success' ? 'success' : ''; ?>" role="alert"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="summary-tiles">
        <div class="summary-tile orange"><strong><?php echo (int)$stats['pending']; ?></strong><span>Pending</span></div>
        <div class="summary-tile green"><strong><?php echo (int)$stats['approved']; ?></strong><span>Approved</span></div>
        <div class="summary-tile red"><strong><?php echo (int)$stats['rejected']; ?></strong><span>Rejected</span></div>
        <div class="summary-tile"><strong><?php echo (int)$stats['total']; ?></strong><span>Total</span></div>
    </div>

    <form method="GET" class="table-toolbar" role="search" aria-label="Filter records">
        <div class="tf">
            <label for="f-date">Date</label>
            <input type="date" id="f-date" name="date" value="<?php echo h($fDate); ?>">
        </div>
        <div class="tf">
            <label for="f-coy">Company</label>
            <select id="f-coy" name="coy">
                <option value="0">All Companies</option>
                <?php foreach ($companies as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $fCoy == $c['id'] ? 'selected' : ''; ?>><?php echo h($c['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tf">
            <label for="f-status">Status</label>
            <select id="f-status" name="status">
                <?php foreach (['All','Pending','Approved','Rejected'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $fStatus === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar-actions">
            <button class="btn secondary" type="submit">Apply</button>
        </div>
    </form>

    <!-- Hidden single-action form -->
    <form method="POST" id="single-form" style="display:none">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" id="sa-action">
        <input type="hidden" name="ids[]" id="sa-id">
        <input type="hidden" name="date" value="<?php echo h($fDate); ?>">
        <input type="hidden" name="coy" value="<?php echo $fCoy; ?>">
        <input type="hidden" name="status" value="<?php echo h($fStatus); ?>">
    </form>

    <?php if (empty($records)): ?>
    <div class="panel">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            <h3>No Records Found</h3>
            <p>No attendance records match the current filter. Try a different date or company.</p>
        </div>
    </div>
    <?php else: ?>
    <form method="POST" id="bulk-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" id="bulk-action">
        <input type="hidden" name="date" value="<?php echo h($fDate); ?>">
        <input type="hidden" name="coy" value="<?php echo $fCoy; ?>">
        <input type="hidden" name="status" value="<?php echo h($fStatus); ?>">
        <!-- Selected IDs injected by JS -->
    </form>

    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--border-subtle);">
            <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
            <?php if ($fStatus === 'Pending' || $fStatus === 'All'): ?>
            <div class="action-group">
                <button type="button" class="btn approve sm" onclick="submitBulk('approve')">✓ Approve Selected</button>
                <button type="button" class="btn reject sm" onclick="submitBulk('reject')">✗ Reject Selected</button>
                <button type="button" class="btn secondary sm" onclick="toggleAll()">Select All</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table aria-label="Attendance records">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="chk-all" aria-label="Select all" form="bulk-form"></th>
                        <th>Army No</th>
                        <th>Rank / Name</th>
                        <th>Coy</th>
                        <th>Attendance</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $r):
                    $isPending = $r['approval_status'] === 'Pending';
                    $statusClass = match(strtolower(str_replace(' ','-',$r['status']))) {
                        'present' => 'status-present', 'absent' => 'status-absent',
                        'leave' => 'status-leave', 'course' => 'status-course',
                        'duty' => 'status-duty', 'sick-report','mh' => 'status-sick',
                        default => 'status-duty'
                    };
                ?>
                <tr>
                    <td><?php if ($isPending): ?><input type="checkbox" class="row-chk" value="<?php echo (int)$r['id']; ?>" form="bulk-form" name="ids[]" aria-label="Select <?php echo h($r['full_name']); ?>"><?php else: ?>—<?php endif; ?></td>
                    <td class="mono"><?php echo h($r['army_no']); ?></td>
                    <td>
                        <span class="status-badge status-duty" style="font-size:10px;margin-right:4px;"><?php echo h($r['rank_name']); ?></span><?php echo h($r['full_name']); ?>
                    </td>
                    <td><?php echo h($r['short_name']); ?></td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo h($r['status']); ?></span></td>
                    <td class="text-muted"><?php echo $r['remarks'] ? h($r['remarks']) : '<span class="text-muted">—</span>'; ?></td>
                    <td>
                        <span class="status-badge <?php echo match($r['approval_status']){'Approved'=>'status-present','Rejected'=>'status-absent',default=>'status-duty'}; ?>"><?php echo h($r['approval_status']); ?></span>
                        <?php if ($r['appr_name']): ?><br><small class="mono-sm text-muted"><?php echo h($r['appr_name']); ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isPending): ?>
                        <div class="action-group">
                            <button type="button" class="btn approve sm" onclick="singleAction(<?php echo (int)$r['id']; ?>,'approve')" aria-label="Approve">✓</button>
                            <button type="button" class="btn reject sm" onclick="singleAction(<?php echo (int)$r['id']; ?>,'reject')" aria-label="Reject">✗</button>
                        </div>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function singleAction(id, action) {
    document.getElementById('sa-action').value = action;
    document.getElementById('sa-id').value = id;
    document.getElementById('single-form').submit();
}
function submitBulk(action) {
    var checked = Array.from(document.querySelectorAll('.row-chk:checked'));
    if (!checked.length) { alert('Select at least one record first.'); return; }
    document.getElementById('bulk-action').value = action;
    // Remove any previously injected ids
    document.querySelectorAll('#bulk-form .dyn-id').forEach(e => e.remove());
    checked.forEach(function(c) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = c.value;
        inp.className = 'dyn-id';
        document.getElementById('bulk-form').appendChild(inp);
    });
    // Uncheck checkboxes from form so they don't duplicate
    checked.forEach(c => { c.removeAttribute('name'); });
    document.getElementById('bulk-form').submit();
}
var _allSel = false;
function toggleAll() {
    _allSel = !_allSel;
    document.querySelectorAll('.row-chk').forEach(c => c.checked = _allSel);
    var hdr = document.getElementById('chk-all');
    if (hdr) hdr.checked = _allSel;
}
var hdrChk = document.getElementById('chk-all');
if (hdrChk) hdrChk.addEventListener('change', function() {
    document.querySelectorAll('.row-chk').forEach(c => c.checked = this.checked);
    _allSel = this.checked;
});
<?php if ($message): ?>
showToast(<?php echo json_encode($message); ?>, '<?php echo $msgType; ?>');
<?php endif; ?>
</script>
</body>
</html>
