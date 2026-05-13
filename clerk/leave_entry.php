<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$clerkCoyId = (int)($_SESSION["company_id"] ?? 0);
$enteredBy  = (int)$_SESSION["user_id"];
$message = ''; $msgType = 'info';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();
    $action = $_POST["action"] ?? '';

    if ($action === "add") {
        $pid       = (int)($_POST["personnel_id"] ?? 0);
        $leaveType = trim($_POST["leave_type"] ?? '');
        $fromDate  = trim($_POST["from_date"] ?? '');
        $toDate    = trim($_POST["to_date"] ?? '');
        $remarks   = trim($_POST["remarks"] ?? '');

        $validTypes = ['Annual Leave','Casual Leave','Medical Leave','Compassionate Leave','Study Leave','Maternity Leave','Paternity Leave','Quarantine Leave'];

        if (!$pid || !in_array($leaveType, $validTypes) || !$fromDate || !$toDate) {
            $message = "Please fill all required fields.";
        } elseif ($fromDate > $toDate) {
            $message = "From date cannot be after To date.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO leave_records (personnel_id,leave_type,from_date,to_date,approval_status,return_status,remarks,entered_by)
                 VALUES (?,?,?,?,'Pending','Not Returned',?,?)");
            mysqli_stmt_bind_param($stmt, 'issssi', $pid, $leaveType, $fromDate, $toDate, $remarks, $enteredBy);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Leave record added successfully."; $msgType = 'success';
            } else {
                $message = "Error saving record: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["record_id"] ?? 0);
        $stmt = mysqli_prepare($conn,
            "DELETE FROM leave_records WHERE id=? AND approval_status='Pending'
             AND personnel_id IN (SELECT id FROM personnel WHERE company_id=?)");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $clerkCoyId);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = $n ? "Record deleted." : "Cannot delete — record may be approved.";
        $msgType = $n ? 'success' : 'error';
    }
}

// Personnel for this company
$personnel = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, army_no, rank_name, full_name FROM personnel
     WHERE company_id=$clerkCoyId AND service_status='Serving'
     ORDER BY rank_name, full_name"), MYSQLI_ASSOC);

// Existing leave records
$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT lr.id, lr.leave_type, lr.from_date, lr.to_date, lr.approval_status, lr.return_status, lr.remarks,
            p.army_no, p.rank_name, p.full_name,
            DATEDIFF(lr.to_date, lr.from_date)+1 AS days
     FROM leave_records lr
     JOIN personnel p ON p.id = lr.personnel_id
     WHERE p.company_id = $clerkCoyId
     ORDER BY lr.from_date DESC LIMIT 100"), MYSQLI_ASSOC);

$coyRow = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';

$leaveTypes = ['Annual Leave','Casual Leave','Medical Leave','Compassionate Leave','Study Leave','Maternity Leave','Paternity Leave','Quarantine Leave'];

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Leave Entry | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Leave Entry</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Leave Entry</h1>
        <p class="page-sub"><?php echo h($coyName); ?> — record and manage leave applications.</p>
    </div>

    <?php if ($message): ?>
    <div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="split-layout">
        <!-- Add Form -->
        <div class="panel">
            <h2 style="font-size:16px;margin-bottom:18px;">Add Leave Record</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <label for="l-pid">Soldier <span style="color:var(--saffron)">*</span></label>
                <select id="l-pid" name="personnel_id" required>
                    <option value="">— Select Personnel —</option>
                    <?php foreach ($personnel as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['rank_name'].' '.$p['full_name'].' ('.$p['army_no'].')'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="l-type">Leave Type <span style="color:var(--saffron)">*</span></label>
                <select id="l-type" name="leave_type" required>
                    <option value="">— Select Type —</option>
                    <?php foreach ($leaveTypes as $lt): ?>
                    <option value="<?php echo h($lt); ?>"><?php echo h($lt); ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label for="l-from">From Date <span style="color:var(--saffron)">*</span></label>
                        <input type="date" id="l-from" name="from_date" required>
                    </div>
                    <div>
                        <label for="l-to">To Date <span style="color:var(--saffron)">*</span></label>
                        <input type="date" id="l-to" name="to_date" required>
                    </div>
                </div>

                <label for="l-remarks">Remarks</label>
                <textarea id="l-remarks" name="remarks" rows="3" placeholder="Optional remarks..."></textarea>

                <button class="btn" type="submit" style="width:100%;margin-top:4px;">Add Leave Record</button>
            </form>
        </div>

        <!-- Records table -->
        <div>
            <?php if (empty($records)): ?>
            <div class="panel">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <h3>No Leave Records</h3>
                    <p>No leave records found for <?php echo h($coyName); ?>. Use the form to add the first entry.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="panel" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
                    <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s) — <?php echo h($coyName); ?></p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Army No</th><th>Name</th><th>Leave Type</th><th>From</th><th>To</th><th class="num">Days</th><th>Status</th><th>Return</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="mono"><?php echo h($r['army_no']); ?></td>
                            <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                            <td><?php echo h($r['leave_type']); ?></td>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['from_date']))); ?></td>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['to_date']))); ?></td>
                            <td class="num"><?php echo (int)$r['days']; ?></td>
                            <td><span class="status-badge <?php echo match($r['approval_status']){'Approved'=>'status-present','Rejected'=>'status-absent',default=>'status-duty'}; ?>"><?php echo h($r['approval_status']); ?></span></td>
                            <td><span class="status-badge <?php echo $r['return_status']==='Returned'?'status-present':'status-absent'; ?>"><?php echo h($r['return_status']); ?></span></td>
                            <td>
                                <?php if ($r['approval_status'] === 'Pending'): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="record_id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn danger sm" type="submit" aria-label="Delete record">✕</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php if ($message): ?><script>showToast(<?php echo json_encode($message); ?>,'<?php echo $msgType; ?>');</script><?php endif; ?>
</body>
</html>
