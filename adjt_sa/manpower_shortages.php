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
    $action = $_POST["action"] ?? '';

    if ($action === "add") {
        $coyId   = (int)($_POST["company_id"] ?? 0);
        $sdate   = trim($_POST["shortage_date"] ?? '');
        $reqd    = (int)($_POST["required_strength"] ?? 0);
        $avail   = (int)($_POST["available_strength"] ?? 0);
        $prio    = in_array($_POST["priority"]??'', ['Normal','Urgent','Critical']) ? $_POST["priority"] : 'Normal';
        $remarks = trim($_POST["remarks"] ?? '');

        if (!$coyId || !$sdate || !$reqd) {
            $message = "Please fill all required fields.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO manpower_shortages (company_id,shortage_date,required_strength,available_strength,priority,remarks,marked_by)
                 VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isiissi', $coyId, $sdate, $reqd, $avail, $prio, $remarks, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Shortage record added."; $msgType = 'success';
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$fCoy = (int)($_GET["coy"] ?? 0);

$companies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

$whereCoy = $fCoy ? "WHERE ms.company_id=$fCoy" : '';
$records  = mysqli_fetch_all(mysqli_query($conn,
    "SELECT ms.id, ms.shortage_date, ms.required_strength, ms.available_strength,
            ms.priority, ms.remarks,
            c.company_name, c.short_name,
            ms.required_strength - ms.available_strength AS shortage
     FROM manpower_shortages ms
     JOIN companies c ON c.id = ms.company_id
     $whereCoy
     ORDER BY ms.shortage_date DESC, ms.priority DESC
     LIMIT 100"), MYSQLI_ASSOC);

// Summary by company (latest entry per company)
$summary = mysqli_fetch_all(mysqli_query($conn,
    "SELECT c.company_name, c.short_name,
            ms.required_strength, ms.available_strength,
            ms.required_strength - ms.available_strength AS shortage,
            ms.priority, ms.shortage_date
     FROM manpower_shortages ms
     JOIN companies c ON c.id=ms.company_id
     JOIN (SELECT company_id, MAX(shortage_date) AS mdate FROM manpower_shortages GROUP BY company_id) latest
          ON ms.company_id=latest.company_id AND ms.shortage_date=latest.mdate
     ORDER BY shortage DESC"), MYSQLI_ASSOC);

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Manpower Shortages | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Manpower Shortages</span>
    </nav>

    <div class="page-header">
        <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Manpower Shortages</h1>
        <p class="page-sub">Track and monitor company-wise strength shortages and priorities.</p>
    </div>

    <?php if ($message): ?><div class="message <?php echo $msgType==='success'?'success':''; ?>" role="alert"><?php echo h($message); ?></div><?php endif; ?>

    <div class="split-layout">
        <!-- Add form -->
        <div class="panel">
            <h2 style="font-size:16px;margin-bottom:18px;">Mark Shortage</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <label for="sh-coy">Company <span style="color:var(--saffron)">*</span></label>
                <select id="sh-coy" name="company_id" required>
                    <option value="">— Select Company —</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo h($c['company_name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="sh-date">Date <span style="color:var(--saffron)">*</span></label>
                <input type="date" id="sh-date" name="shortage_date" value="<?php echo date('Y-m-d'); ?>" required>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label for="sh-req">Required <span style="color:var(--saffron)">*</span></label>
                        <input type="number" id="sh-req" name="required_strength" min="1" max="500" required>
                    </div>
                    <div>
                        <label for="sh-avl">Available</label>
                        <input type="number" id="sh-avl" name="available_strength" min="0" max="500">
                    </div>
                </div>

                <label for="sh-prio">Priority</label>
                <select id="sh-prio" name="priority">
                    <option value="Normal">Normal</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Critical">Critical</option>
                </select>

                <label for="sh-rem">Remarks</label>
                <textarea id="sh-rem" name="remarks" rows="2" placeholder="Reason for shortage..."></textarea>

                <button class="btn" type="submit" style="width:100%;margin-top:4px;">Mark Shortage</button>
            </form>

            <!-- Latest summary -->
            <?php if (!empty($summary)): ?>
            <div style="margin-top:24px;">
                <h2 style="font-size:14px;margin-bottom:12px;">Latest Status by Company</h2>
                <?php foreach ($summary as $s):
                    $defColor = $s['priority']==='Critical'?'var(--status-absent)':($s['priority']==='Urgent'?'var(--saffron)':'var(--status-present)');
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-subtle);gap:10px;">
                    <div>
                        <span style="font-weight:600;font-size:13px;"><?php echo h($s['company_name']); ?></span>
                        <span class="mono-sm text-muted" style="margin-left:6px;"><?php echo h(date('d M',strtotime($s['shortage_date']))); ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="mono" style="color:var(--text-secondary);"><?php echo $s['available_strength']; ?>/<?php echo $s['required_strength']; ?></span>
                        <?php if ($s['shortage'] > 0): ?>
                        <span class="status-badge" style="background:<?php echo $defColor; ?>22;color:<?php echo $defColor; ?>;border:1px solid <?php echo $defColor; ?>44;font-size:10px;">
                            −<?php echo $s['shortage']; ?> <?php echo h($s['priority']); ?>
                        </span>
                        <?php else: ?>
                        <span class="status-badge status-present" style="font-size:10px;">OK</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Records table -->
        <div>
            <form method="GET" style="margin-bottom:16px;display:flex;gap:8px;align-items:flex-end;">
                <div class="tf"><label for="f-coy" style="font-size:11px;">Company</label>
                <select id="f-coy" name="coy" style="min-height:38px;margin:0;">
                    <option value="0">All Companies</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $fCoy==$c['id']?'selected':''; ?>><?php echo h($c['company_name']); ?></option>
                    <?php endforeach; ?>
                </select></div>
                <button class="btn secondary" type="submit">Filter</button>
            </form>

            <?php if (empty($records)): ?>
            <div class="panel">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <h3>No Shortage Records</h3>
                    <p>No shortage entries found. Use the form to mark a shortage.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="panel" style="padding:0;overflow:hidden;">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);">
                    <p class="muted" style="margin:0;"><?php echo count($records); ?> record(s)</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Company</th><th class="num">Required</th><th class="num">Available</th><th class="num">Shortage</th><th>Priority</th><th>Remarks</th></tr></thead>
                        <tbody>
                        <?php foreach ($records as $r):
                            $prioBg = match($r['priority']){ 'Critical'=>'var(--status-absent)', 'Urgent'=>'var(--saffron)', default=>'var(--status-present)' };
                        ?>
                        <tr>
                            <td class="mono"><?php echo h(date('d M Y',strtotime($r['shortage_date']))); ?></td>
                            <td><?php echo h($r['company_name']); ?></td>
                            <td class="num"><?php echo (int)$r['required_strength']; ?></td>
                            <td class="num"><?php echo (int)$r['available_strength']; ?></td>
                            <td class="num fw-bold" style="color:<?php echo $r['shortage']>0?'var(--status-absent)':'var(--status-present)'; ?>">
                                <?php echo $r['shortage']>0 ? '−'.(int)$r['shortage'] : '0'; ?>
                            </td>
                            <td><span class="status-badge" style="background:<?php echo $prioBg; ?>22;color:<?php echo $prioBg; ?>;border:1px solid <?php echo $prioBg; ?>44;"><?php echo h($r['priority']); ?></span></td>
                            <td class="text-muted" style="font-size:12px;max-width:200px;"><?php echo h($r['remarks'] ?: '—'); ?></td>
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
