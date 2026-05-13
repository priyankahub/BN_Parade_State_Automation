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
        $dtype    = trim($_POST["duty_type"] ?? '');
        $ddate    = trim($_POST["duty_date"] ?? '');
        $dtime    = trim($_POST["duty_time"] ?? '');
        $location = trim($_POST["location"] ?? '');
        $remarks  = trim($_POST["remarks"] ?? '');
        $validTypes = ['Guard','Sentry','QRT','Office Duty','Special Task','Other'];

        if (!$pid || !in_array($dtype, $validTypes) || !$ddate) {
            $message = "Please fill all required fields.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO duty_roster (personnel_id,duty_type,duty_date,duty_time,location,remarks,entered_by)
                 VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isssssi', $pid, $dtype, $ddate, $dtime, $location, $remarks, $enteredBy);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Duty record added."; $msgType = 'success';
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["record_id"] ?? 0);
        $stmt = mysqli_prepare($conn,
            "DELETE dr FROM duty_roster dr
             JOIN personnel p ON p.id = dr.personnel_id
             WHERE dr.id=? AND p.company_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $clerkCoyId);
        mysqli_stmt_execute($stmt);
        $n = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $message = $n ? "Record deleted." : "Could not delete record.";
        $msgType = $n ? 'success' : 'error';
    }
}

$personnel = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, army_no, rank_name, full_name FROM personnel
     WHERE company_id=$clerkCoyId AND service_status='Serving'
     ORDER BY rank_name, full_name"), MYSQLI_ASSOC);

$records = mysqli_fetch_all(mysqli_query($conn,
    "SELECT dr.id, dr.duty_type, dr.duty_date, dr.duty_time, dr.location, dr.remarks,
            p.army_no, p.rank_name, p.full_name
     FROM duty_roster dr
     JOIN personnel p ON p.id = dr.personnel_id
     WHERE p.company_id = $clerkCoyId
     ORDER BY dr.duty_date DESC LIMIT 80"), MYSQLI_ASSOC);

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';
$dutyTypes = ['Guard','Sentry','QRT','Office Duty','Special Task','Other'];
$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Duty Roster | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Duty Roster</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Duty Roster</h1>
        <p class="page-sub"><?php echo h($coyName); ?> — log and view duty assignments.</p>
    </div>

    <?php if ($message): ?><div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div><?php endif; ?>

    <div class="split-layout">
        <div class="panel">
            <h2 style="font-size:16px;margin-bottom:18px;">Add Duty Entry</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <label for="d-pid">Soldier <span style="color:var(--saffron)">*</span></label>
                <select id="d-pid" name="personnel_id" required>
                    <option value="">— Select Personnel —</option>
                    <?php foreach ($personnel as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['rank_name'].' '.$p['full_name'].' ('.$p['army_no'].')'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="d-type">Duty Type <span style="color:var(--saffron)">*</span></label>
                <select id="d-type" name="duty_type" required>
                    <option value="">— Select Type —</option>
                    <?php foreach ($dutyTypes as $dt): ?><option value="<?php echo $dt; ?>"><?php echo $dt; ?></option><?php endforeach; ?>
                </select>

                <label for="d-date">Duty Date <span style="color:var(--saffron)">*</span></label>
                <input type="date" id="d-date" name="duty_date" value="<?php echo date('Y-m-d'); ?>" required>

                <label for="d-time">Duty Hours</label>
                <input type="text" id="d-time" name="duty_time" placeholder="e.g. 0600–1800 hrs">

                <label for="d-loc">Location</label>
                <input type="text" id="d-loc" name="location" placeholder="e.g. Main Gate">

                <label for="d-rem">Remarks</label>
                <textarea id="d-rem" name="remarks" rows="2" placeholder="Optional..."></textarea>

                <button class="btn" type="submit" style="width:100%;margin-top:4px;">Add Duty Entry</button>
            </form>
        </div>

        <div>
            <?php if (empty($records)): ?>
            <div class="panel">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <h3>No Duty Records</h3>
                    <p>No duty entries found for <?php echo h($coyName); ?>.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="panel" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
                    <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Army No</th><th>Name</th><th>Duty Type</th><th>Hours</th><th>Location</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['duty_date']))); ?></td>
                            <td class="mono"><?php echo h($r['army_no']); ?></td>
                            <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                            <td><span class="status-badge status-duty"><?php echo h($r['duty_type']); ?></span></td>
                            <td class="mono-sm text-muted"><?php echo h($r['duty_time'] ?: '—'); ?></td>
                            <td><?php echo h($r['location'] ?: '—'); ?></td>
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
