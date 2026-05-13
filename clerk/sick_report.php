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
        $pid      = (int)($_POST["personnel_id"] ?? 0);
        $cat      = trim($_POST["category"] ?? '');
        $rdate    = trim($_POST["report_date"] ?? '');
        $expRet   = trim($_POST["expected_return"] ?? '');
        $remarks  = trim($_POST["remarks"] ?? '');
        $validCats = ['Sick Report','MH','OPD','Rest Advised'];

        if (!$pid || !in_array($cat, $validCats) || !$rdate) {
            $message = "Please fill all required fields.";
        } else {
            $expRetVal = $expRet ?: null;
            $stmt = mysqli_prepare($conn,
                "INSERT INTO sick_reports (personnel_id,report_date,category,expected_return,remarks,entered_by)
                 VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'issssi', $pid, $rdate, $cat, $expRetVal, $remarks, $enteredBy);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Sick report added."; $msgType = 'success';
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["record_id"] ?? 0);
        $stmt = mysqli_prepare($conn,
            "DELETE sr FROM sick_reports sr
             JOIN personnel p ON p.id = sr.personnel_id
             WHERE sr.id=? AND p.company_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $clerkCoyId);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = $n ? "Record deleted." : "Could not delete.";
        $msgType = $n ? 'success' : 'error';
    }
}

$personnel = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, army_no, rank_name, full_name FROM personnel
     WHERE company_id=$clerkCoyId AND service_status='Serving'
     ORDER BY rank_name, full_name"), MYSQLI_ASSOC);

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT sr.id, sr.report_date, sr.category, sr.expected_return, sr.remarks,
            p.army_no, p.rank_name, p.full_name
     FROM sick_reports sr
     JOIN personnel p ON p.id = sr.personnel_id
     WHERE p.company_id = $clerkCoyId
     ORDER BY sr.report_date DESC LIMIT 80"), MYSQLI_ASSOC);

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';
$categories = ['Sick Report','MH','OPD','Rest Advised'];
$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Sick Report | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Sick Report / MH</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>Sick Report / MH</h1>
        <p class="page-sub"><?php echo h($coyName); ?> — record sick reports, MH admissions and expected return dates.</p>
    </div>

    <?php if ($message): ?><div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div><?php endif; ?>

    <div class="split-layout">
        <div class="panel">
            <h2 style="font-size:16px;margin-bottom:18px;">Add Sick Report</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <label for="s-pid">Soldier <span style="color:var(--saffron)">*</span></label>
                <select id="s-pid" name="personnel_id" required>
                    <option value="">— Select Personnel —</option>
                    <?php foreach ($personnel as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['rank_name'].' '.$p['full_name'].' ('.$p['army_no'].')'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="s-cat">Category <span style="color:var(--saffron)">*</span></label>
                <select id="s-cat" name="category" required>
                    <option value="">— Select Category —</option>
                    <?php foreach ($categories as $cat): ?><option value="<?php echo $cat; ?>"><?php echo $cat; ?></option><?php endforeach; ?>
                </select>

                <label for="s-date">Report Date <span style="color:var(--saffron)">*</span></label>
                <input type="date" id="s-date" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>

                <label for="s-ret">Expected Return</label>
                <input type="date" id="s-ret" name="expected_return">

                <label for="s-rem">Remarks</label>
                <textarea id="s-rem" name="remarks" rows="2" placeholder="e.g. Reported to MI Room, fit certificate awaited"></textarea>

                <button class="btn" type="submit" style="width:100%;margin-top:4px;">Add Record</button>
            </form>
        </div>

        <div>
            <?php if (empty($records)): ?>
            <div class="panel">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    <h3>No Sick Reports</h3>
                    <p>No sick reports found for <?php echo h($coyName); ?>.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="panel" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
                    <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Army No</th><th>Name</th><th>Category</th><th>Exp. Return</th><th>Remarks</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['report_date']))); ?></td>
                            <td class="mono"><?php echo h($r['army_no']); ?></td>
                            <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                            <td><span class="status-badge status-sick"><?php echo h($r['category']); ?></span></td>
                            <td class="mono"><?php echo $r['expected_return'] ? h(date('d M Y',strtotime($r['expected_return']))) : '<span class="text-muted">—</span>'; ?></td>
                            <td class="text-muted" style="font-size:12px;max-width:180px;"><?php echo h($r['remarks'] ?: '—'); ?></td>
                            <td>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="record_id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn danger sm" type="submit" aria-label="Delete">✕</button>
                                </form>
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
