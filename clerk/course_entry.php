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
        $cname    = trim($_POST["course_name"] ?? '');
        $loc      = trim($_POST["location"] ?? '');
        $fromDate = trim($_POST["from_date"] ?? '');
        $toDate   = trim($_POST["to_date"] ?? '');
        $remarks  = trim($_POST["remarks"] ?? '');

        if (!$pid || !$cname || !$fromDate || !$toDate) {
            $message = "Please fill all required fields.";
        } elseif ($fromDate > $toDate) {
            $message = "From date cannot be after To date.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO course_records (personnel_id,course_name,location,from_date,to_date,remarks,entered_by)
                 VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isssssi', $pid, $cname, $loc, $fromDate, $toDate, $remarks, $enteredBy);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Course record added."; $msgType = 'success';
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["record_id"] ?? 0);
        $stmt = mysqli_prepare($conn,
            "DELETE cr FROM course_records cr
             JOIN personnel p ON p.id = cr.personnel_id
             WHERE cr.id=? AND p.company_id=?");
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
    "SELECT cr.id, cr.course_name, cr.location, cr.from_date, cr.to_date, cr.remarks,
            p.army_no, p.rank_name, p.full_name,
            DATEDIFF(cr.to_date, cr.from_date)+1 AS duration
     FROM course_records cr
     JOIN personnel p ON p.id = cr.personnel_id
     WHERE p.company_id = $clerkCoyId
     ORDER BY cr.from_date DESC LIMIT 80"), MYSQLI_ASSOC);

$coyRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name FROM companies WHERE id=$clerkCoyId LIMIT 1"));
$coyName = $coyRow['company_name'] ?? 'Your Company';
$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Course Entry | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Course Entry</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>Course Entry</h1>
        <p class="page-sub"><?php echo h($coyName); ?> — log training and course attendance.</p>
    </div>

    <?php if ($message): ?><div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div><?php endif; ?>

    <div class="split-layout">
        <div class="panel">
            <h2 style="font-size:16px;margin-bottom:18px;">Add Course Record</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <label for="c-pid">Soldier <span style="color:var(--saffron)">*</span></label>
                <select id="c-pid" name="personnel_id" required>
                    <option value="">— Select Personnel —</option>
                    <?php foreach ($personnel as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo h($p['rank_name'].' '.$p['full_name'].' ('.$p['army_no'].')'); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="c-name">Course Name <span style="color:var(--saffron)">*</span></label>
                <input type="text" id="c-name" name="course_name" placeholder="e.g. Section Commanders Course" required>

                <label for="c-loc">Location</label>
                <input type="text" id="c-loc" name="location" placeholder="e.g. ITC Belgaum">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label for="c-from">From Date <span style="color:var(--saffron)">*</span></label>
                        <input type="date" id="c-from" name="from_date" required>
                    </div>
                    <div>
                        <label for="c-to">To Date <span style="color:var(--saffron)">*</span></label>
                        <input type="date" id="c-to" name="to_date" required>
                    </div>
                </div>

                <label for="c-rem">Remarks</label>
                <textarea id="c-rem" name="remarks" rows="2" placeholder="e.g. Nominated by CO"></textarea>

                <button class="btn" type="submit" style="width:100%;margin-top:4px;">Add Course Record</button>
            </form>
        </div>

        <div>
            <?php if (empty($records)): ?>
            <div class="panel">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <h3>No Course Records</h3>
                    <p>No course entries found for <?php echo h($coyName); ?>.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="panel" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
                    <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Army No</th><th>Name</th><th>Course</th><th>Location</th><th>From</th><th>To</th><th class="num">Days</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="mono"><?php echo h($r['army_no']); ?></td>
                            <td><?php echo h($r['rank_name'].' '.$r['full_name']); ?></td>
                            <td><span class="status-badge status-course"><?php echo h($r['course_name']); ?></span></td>
                            <td class="text-muted"><?php echo h($r['location'] ?: '—'); ?></td>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['from_date']))); ?></td>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['to_date']))); ?></td>
                            <td class="num"><?php echo (int)$r['duration']; ?></td>
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
