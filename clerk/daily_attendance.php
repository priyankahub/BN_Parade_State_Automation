<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$clerkCompanyId = (int)($_SESSION["company_id"] ?? 0);
$enteredBy      = (int)$_SESSION["user_id"];
$today          = date("Y-m-d");
$viewDate       = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;

$message      = "";
$messageClass = "message";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $attendanceDate = $_POST["attendance_date"] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)) $attendanceDate = $today;

    $statuses       = $_POST["status"] ?? [];
    $remarksPost    = $_POST["remarks"] ?? [];
    $validStatuses  = ['Present','Absent','Leave','Course','Sick Report','MH','TD','Duty','Attached Out','Other'];
    $saved = 0;

    foreach ($statuses as $pid => $status) {
        $pid = (int)$pid;
        if (!in_array($status, $validStatuses, true)) continue;
        $remark = trim($remarksPost[$pid] ?? "");

        $stmt = mysqli_prepare($conn,
            "INSERT INTO attendance (personnel_id, attendance_date, status, remarks, entered_by, approval_status)
             VALUES (?, ?, ?, ?, ?, 'Pending')
             ON DUPLICATE KEY UPDATE
               status           = VALUES(status),
               remarks          = VALUES(remarks),
               entered_by       = VALUES(entered_by),
               approval_status  = 'Pending'"
        );
        mysqli_stmt_bind_param($stmt, "isssi", $pid, $attendanceDate, $status, $remark, $enteredBy);
        if (mysqli_stmt_execute($stmt)) $saved++;
        mysqli_stmt_close($stmt);
    }

    $message      = "$saved attendance record(s) saved for $attendanceDate.";
    $messageClass = "message success";
    $viewDate     = $attendanceDate;
}

// Fetch company personnel + their attendance for the view date
$stmt = mysqli_prepare($conn,
    "SELECT p.id, p.army_no, p.rank_name, p.full_name,
            a.status AS att_status, a.remarks AS att_remarks, a.approval_status
     FROM personnel p
     LEFT JOIN attendance a ON a.personnel_id = p.id AND a.attendance_date = ?
     WHERE p.company_id = ? AND p.service_status = 'Serving'
     ORDER BY p.rank_name, p.full_name"
);
mysqli_stmt_bind_param($stmt, "si", $viewDate, $clerkCompanyId);
mysqli_stmt_execute($stmt);
$personnel = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$companyRow  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT company_name FROM companies WHERE id = $clerkCompanyId LIMIT 1"));
$companyName = $companyRow["company_name"] ?? "Your Company";

$statusOptions = ['Present','Absent','Leave','Course','Sick Report','MH','TD','Duty','Attached Out','Other'];

$statusCount = array_count_values(array_column(
    array_filter($personnel, fn($p) => $p['att_status'] !== null),
    'att_status'
));
$totalEntered = array_sum($statusCount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Daily Attendance | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .att-table td, .att-table th { padding: 8px 10px; }
        .att-table select { margin: 0; padding: 5px 8px; font-size: 12px; width: auto; }
        .att-table input[type=text] { margin: 0; padding: 5px 8px; font-size: 12px; width: 160px; }
        .att-summary { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .att-stat { background: var(--bg-secondary); border: 1px solid var(--border-subtle);
                    border-radius: 6px; padding: 8px 16px; font-size: 13px; }
        .att-stat strong { font-family: 'Fira Code', monospace; font-size: 18px;
                           display: block; color: var(--gold); }
        .approved-row td { opacity: 0.7; }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container wide-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Daily Attendance</span>
    </nav>
    <div class="panel">
        <div class="panel-heading">
            <div>
                <h1 style="font-size:20px;margin:0;">Daily Attendance Entry</h1>
                <p class="muted"><?php echo h($companyName); ?> — <?php echo count($personnel); ?> serving personnel</p>
            </div>
            <!-- Date navigation -->
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <label style="margin:0;text-transform:none;font-size:13px;">View Date:</label>
                <input type="date" name="date" value="<?php echo h($viewDate); ?>"
                       style="padding:6px 10px;font-size:13px;margin:0;width:auto;">
                <button class="btn secondary" type="submit" style="padding:6px 14px;">Go</button>
            </form>
        </div>

        <?php if ($totalEntered > 0): ?>
        <div class="att-summary">
            <?php foreach ($statusCount as $s => $n): ?>
                <div class="att-stat">
                    <strong><?php echo $n; ?></strong>
                    <?php echo h($s); ?>
                </div>
            <?php endforeach; ?>
            <div class="att-stat">
                <strong><?php echo count($personnel) - $totalEntered; ?></strong>
                Not Entered
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <?php if (empty($personnel)): ?>
        <div class="panel">
            <p class="muted">No serving personnel found for <?php echo h($companyName); ?>. Add soldiers via Soldier Master.</p>
        </div>
    <?php else: ?>
    <form method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="attendance_date" value="<?php echo h($viewDate); ?>">

        <div class="panel">
            <div class="table-wrap">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Army No</th>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Status *</th>
                            <th>Remarks</th>
                            <th>Approval</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($personnel as $i => $p): ?>
                        <?php
                            $isApproved = $p['approval_status'] === 'Approved';
                            $current    = $p['att_status'] ?? 'Present';
                        ?>
                        <tr <?php echo $isApproved ? 'class="approved-row"' : ''; ?>>
                            <td style="color:var(--text-muted);font-size:12px;"><?php echo $i + 1; ?></td>
                            <td class="mono"><?php echo h($p['army_no']); ?></td>
                            <td><span class="status-badge status-duty"><?php echo h($p['rank_name']); ?></span></td>
                            <td><?php echo h($p['full_name']); ?></td>
                            <td>
                                <select name="status[<?php echo (int)$p['id']; ?>]"
                                        <?php echo $isApproved ? 'disabled' : ''; ?>>
                                    <?php foreach ($statusOptions as $opt): ?>
                                        <option value="<?php echo $opt; ?>"
                                            <?php echo $current === $opt ? 'selected' : ''; ?>>
                                            <?php echo $opt; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="remarks[<?php echo (int)$p['id']; ?>]"
                                       value="<?php echo h($p['att_remarks'] ?? ''); ?>"
                                       placeholder="Optional"
                                       <?php echo $isApproved ? 'disabled' : ''; ?>>
                            </td>
                            <td>
                                <?php if ($p['approval_status']): ?>
                                    <span class="status-badge <?php
                                        echo match($p['approval_status']) {
                                            'Approved' => 'status-present',
                                            'Rejected' => 'status-absent',
                                            default    => 'status-duty'
                                        }; ?>">
                                        <?php echo h($p['approval_status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;">
                <button class="btn" type="submit">Save Attendance</button>
                <span class="muted" style="align-self:center;font-size:12px;">
                    Approved records cannot be edited.
                </span>
            </div>
        </div>
    </form>
    <?php endif; ?>
</main>
</body>
</html>
