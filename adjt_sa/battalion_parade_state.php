<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pdf_esc($v) { return str_replace(["\\","(",")"], ["\\\\","\\(","\\)"], (string)$v); }

$_role  = $_SESSION["role"];
$isPriv = in_array($_role, ["ADJT_SA","ADMIN"]);

$today    = date("Y-m-d");
$viewDate = $_GET["date"] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = $today;
$istDate  = (new DateTime("now", new DateTimeZone("Asia/Kolkata")))->format("d-m-Y");

// DB companies list (for add-soldier form dropdown)
$dbCompanies = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, company_name, short_name FROM companies WHERE is_active=1 ORDER BY id"), MYSQLI_ASSOC);

// ── Add Soldier POST ─────────────────────────────────────────
$addMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_soldier" && $isPriv) {
    $armyNo   = trim($_POST["army_no"]    ?? "");
    $rankName = trim($_POST["rank_name"]  ?? "");
    $fullName = trim($_POST["full_name"]  ?? "");
    $compId   = (int)($_POST["company_id"] ?? 0);
    $trade    = trim($_POST["trade"]      ?? "");
    if ($armyNo && $rankName && $fullName && $compId) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO personnel (army_no, rank_name, full_name, company_id, trade) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sssis", $armyNo, $rankName, $fullName, $compId, $trade);
        $addMsg = mysqli_stmt_execute($stmt) ? "ok" : "err:" . mysqli_error($conn);
    } else {
        $addMsg = "err:All required fields must be filled.";
    }
}

// ── Live BN Summary (privileged) ────────────────────────────
$bnRows = [];
$totals = array_fill_keys(['total_serving','present','absent','on_leave','on_course','on_duty','sick','td','others','not_entered','pending_approval'], 0);
$bnPct  = 0;
$pendingCount = 0;

if ($isPriv) {
    $bnRows = mysqli_fetch_all(mysqli_query($conn,
        "SELECT c.id, c.company_name,
                COUNT(DISTINCT p.id) AS total_serving,
                SUM(a.status='Present') AS present,
                SUM(a.status='Absent') AS absent,
                SUM(a.status='Leave') AS on_leave,
                SUM(a.status='Course') AS on_course,
                SUM(a.status='Duty') AS on_duty,
                SUM(a.status IN ('Sick Report','MH')) AS sick,
                SUM(a.status='TD') AS td,
                SUM(a.status IN ('Attached Out','Other')) AS others,
                SUM(a.status IS NULL AND p.id IS NOT NULL) AS not_entered,
                SUM(a.approval_status='Pending') AS pending_approval
         FROM companies c
         LEFT JOIN personnel p ON p.company_id=c.id AND p.service_status='Serving'
         LEFT JOIN attendance a ON a.personnel_id=p.id AND a.attendance_date='$viewDate'
         WHERE c.is_active=1
         GROUP BY c.id, c.company_name ORDER BY c.id"), MYSQLI_ASSOC);

    foreach ($bnRows as $r) foreach (array_keys($totals) as $k) $totals[$k] += (int)$r[$k];
    $bnPct = $totals['total_serving'] > 0 ? round($totals['present'] / $totals['total_serving'] * 100) : 0;
    $pendingCount = (int)mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM attendance WHERE approval_status='Pending' AND attendance_date='$viewDate'"))[0];
}

// ── Excel Export ─────────────────────────────────────────────
if (($_ = $_GET["export"] ?? "") === "excel" && $isPriv) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=battalion_parade_state_$viewDate.xls");
    echo "<table border='1'><tr><th colspan='13'>Battalion Parade State &mdash; $viewDate</th></tr>";
    echo "<tr><th>Company</th><th>Strength</th><th>Present</th><th>Absent</th><th>Leave</th><th>Course</th><th>Duty</th><th>Sick/MH</th><th>TD</th><th>Others</th><th>N/E</th><th>Pending</th><th>Present%</th></tr>";
    foreach ($bnRows as $c) {
        $p = $c['total_serving'] > 0 ? round($c['present'] / $c['total_serving'] * 100) : 0;
        echo "<tr><td>" . h($c['company_name']) . "</td><td>{$c['total_serving']}</td><td>{$c['present']}</td><td>{$c['absent']}</td><td>{$c['on_leave']}</td><td>{$c['on_course']}</td><td>{$c['on_duty']}</td><td>{$c['sick']}</td><td>{$c['td']}</td><td>{$c['others']}</td><td>{$c['not_entered']}</td><td>{$c['pending_approval']}</td><td>$p%</td></tr>";
    }
    echo "<tr><th>BN Total</th><th>{$totals['total_serving']}</th><th>{$totals['present']}</th><th>{$totals['absent']}</th><th>{$totals['on_leave']}</th><th>{$totals['on_course']}</th><th>{$totals['on_duty']}</th><th>{$totals['sick']}</th><th>{$totals['td']}</th><th>{$totals['others']}</th><th>{$totals['not_entered']}</th><th>{$totals['pending_approval']}</th><th>$bnPct%</th></tr>";
    echo "</table>";
    exit;
}

// ── PDF Export ────────────────────────────────────────────────
if (($_ = $_GET["export"] ?? "") === "pdf" && $isPriv) {
    // Landscape A4 raw PDF
    $objects = [];

    $cols = [
        ['Company', 20, 88], ['Str', 108, 30], ['Present', 138, 40], ['Absent', 178, 36],
        ['Leave', 214, 36], ['Course', 250, 38], ['Duty', 288, 36], ['Sick', 324, 36],
        ['TD', 360, 28], ['Others', 388, 40], ['N/E', 428, 28], ['Pending', 456, 42], ['Pres%', 498, 36],
    ];

    $ls = [];
    $ls[] = "BT /F1 12 Tf 20 572 Td (" . pdf_esc("Battalion Parade State — $viewDate") . ") Tj ET";
    // header row
    $ls[] = "BT /F1 8 Tf";
    foreach ($cols as [$lbl, $x, $w]) $ls[] = "$x 550 Td (" . pdf_esc($lbl) . ") Tj 0 0 Td";
    $ls[] = "ET";
    $ls[] = "0.4 w 20 546 m 534 546 l S";

    $y = 534;
    foreach ($bnRows as $r) {
        $p = $r['total_serving'] > 0 ? round($r['present'] / $r['total_serving'] * 100) . "%" : "0%";
        $vals = [$r['company_name'], $r['total_serving'], $r['present'], $r['absent'],
                 $r['on_leave'], $r['on_course'], $r['on_duty'], $r['sick'],
                 $r['td'], $r['others'], $r['not_entered'], $r['pending_approval'], $p];
        $ls[] = "BT /F1 8 Tf";
        foreach ($cols as $i => [$lbl, $x, $w]) $ls[] = "$x $y Td (" . pdf_esc((string)$vals[$i]) . ") Tj 0 0 Td";
        $ls[] = "ET";
        $y -= 15;
    }

    $ls[] = "0.4 w 20 " . ($y + 11) . " m 534 " . ($y + 11) . " l S";
    $tvals = ['BN Total', $totals['total_serving'], $totals['present'], $totals['absent'],
              $totals['on_leave'], $totals['on_course'], $totals['on_duty'], $totals['sick'],
              $totals['td'], $totals['others'], $totals['not_entered'], $totals['pending_approval'], "$bnPct%"];
    $ls[] = "BT /F1 8 Tf";
    foreach ($cols as $i => [$lbl, $x, $w]) $ls[] = "$x $y Td (" . pdf_esc((string)$tvals[$i]) . ") Tj 0 0 Td";
    $ls[] = "ET";

    $content  = implode("\n", $ls);
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream"; // 1
    $objects[] = "<< /Type /Page /Parent 0 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 0 0 R >> >> /Contents 1 0 R >>"; // 2
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"; // 3
    $objects[] = "<< /Type /Pages /Kids [2 0 R] /Count 1 >>"; // 4
    $objects[] = "<< /Type /Catalog /Pages 4 0 R >>"; // 5
    $objects[1] = str_replace(["/Parent 0 0 R", "/F1 0 0 R"], ["/Parent 4 0 R", "/F1 3 0 R"], $objects[1]);

    $pdf = "%PDF-1.4\n";
    $off = [0];
    foreach ($objects as $i => $obj) { $off[] = strlen($pdf); $pdf .= ($i+1) . " 0 obj\n$obj\nendobj\n"; }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects)+1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $off[$i]);
    $pdf .= "trailer\n<< /Size " . (count($objects)+1) . " /Root 5 0 R >>\nstartxref\n$xref\n%%EOF";

    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=battalion_parade_state_$viewDate.pdf");
    echo $pdf;
    exit;
}

// ── Static Detailed Company Data ─────────────────────────────
$rankColumns  = ["Col","Lt Col","Maj","Capt","Lt","Sub Maj","Sub","Nb/Sub","Hav","Lhav","Nk","Lnk","Sep","Jco/Clk","OR/Clk","JCO/ERE","OR/ERE","RT JCO"];
$totalColumns = ["OFFR","JCO","OR","GRAND"];
$rowLabels    = ["AUTH STR","POSTED","In Unit","AL","CL","Sikh Leave","Temp Duty","Local Att","Att Duty","MH","Course/Cadre","AWL/OSL","Fwd/Op Area","Bde/Div","Pension Drill","Posting Out","Other Out","Total Absent","Total Present"];

function values($items) {
    $v = array_fill(0, 18, 0);
    foreach ($items as $i => $val) $v[$i] = $val;
    return $v;
}
function row_data($values, $totals, $class = "") {
    return ["values" => $values, "totals" => $totals, "class" => $class];
}
function company_rows($overrides) {
    global $rowLabels;
    $rows = [];
    foreach ($rowLabels as $label) $rows[$label] = row_data(values([]), [0,0,0,0]);
    foreach ($overrides as $label => $row) $rows[$label] = $row;
    $rows["Total Absent"]["class"]  = "summary-row";
    $rows["Total Present"]["class"] = "summary-row";
    return $rows;
}

$companies = [
    "A" => [
        "title" => "A COY", "color" => "#7f1d1d",
        "leave" => ["ON LEAVE" => 11, "LEAVE DUE" => 54, "TOTAL STR" => 65, "LEAVE %" => 17],
        "rows"  => company_rows([
            "POSTED"       => row_data(values([2=>1,8=>15,10=>22,11=>12,12=>15]), [1,0,64,65]),
            "In Unit"      => row_data(values([2=>1,8=>8,10=>14,11=>6,12=>10]),   [1,0,38,39]),
            "AL"           => row_data(values([8=>3,10=>3,11=>2,12=>2]),           [0,0,10,10]),
            "CL"           => row_data(values([8=>1]),                             [0,0,1,1]),
            "Temp Duty"    => row_data(values([11=>1]),                            [0,0,1,1]),
            "Local Att"    => row_data(values([8=>1,10=>3,11=>2]),                 [0,0,6,6]),
            "AWL/OSL"      => row_data(values([12=>1]),                            [0,0,1,1]),
            "Fwd/Op Area"  => row_data(values([8=>1,10=>1,11=>1]),                 [0,0,3,3]),
            "Posting Out"  => row_data(values([10=>1]),                            [0,0,1,1]),
            "Total Absent" => row_data(values([8=>6,10=>8,11=>6,12=>3]),           [0,0,23,23]),
            "Total Present"=> row_data(values([2=>1,8=>9,10=>14,11=>6,12=>12]),    [1,0,41,42]),
        ])
    ],
    "B" => [
        "title" => "B COY", "color" => "#164e63",
        "leave" => ["ON LEAVE" => 8, "LEAVE DUE" => 58, "TOTAL STR" => 66, "LEAVE %" => 12],
        "rows"  => company_rows([
            "POSTED"        => row_data(values([4=>1,8=>10,10=>14,11=>19,12=>22]), [1,0,65,66]),
            "In Unit"       => row_data(values([8=>4,10=>6,11=>5,12=>9]),          [0,0,24,24]),
            "AL"            => row_data(values([8=>1,10=>2,11=>1,12=>1]),          [0,0,5,5]),
            "CL"            => row_data(values([11=>2,12=>1]),                     [0,0,3,3]),
            "Temp Duty"     => row_data(values([11=>2,12=>2]),                     [0,0,4,4]),
            "Course/Cadre"  => row_data(values([11=>3,12=>3]),                     [0,0,6,6]),
            "Total Absent"  => row_data(values([8=>1,10=>2,11=>8,12=>7]),          [0,0,18,18]),
            "Total Present" => row_data(values([4=>1,8=>9,10=>12,11=>11,12=>15]),  [1,0,47,48]),
        ])
    ],
    "C" => [
        "title" => "C COY", "color" => "#166534",
        "leave" => ["ON LEAVE" => 7, "LEAVE DUE" => 48, "TOTAL STR" => 55, "LEAVE %" => 13],
        "rows"  => company_rows([
            "POSTED"        => row_data(values([4=>3,8=>18,10=>7,11=>9,12=>18]),         [3,0,52,55]),
            "In Unit"       => row_data(values([8=>8,10=>6,11=>4,12=>4]),                [0,0,22,22]),
            "AL"            => row_data(values([8=>1,11=>1,12=>2]),                      [0,0,4,4]),
            "CL"            => row_data(values([8=>1,12=>2]),                            [0,0,3,3]),
            "Temp Duty"     => row_data(values([12=>1]),                                 [0,0,1,1]),
            "Local Att"     => row_data(values([4=>1,8=>2,10=>1,11=>1,12=>2]),           [1,0,6,7]),
            "Att Duty"      => row_data(values([11=>2,12=>1]),                           [0,0,3,3]),
            "Course/Cadre"  => row_data(values([12=>1]),                                 [0,0,1,1]),
            "Fwd/Op Area"   => row_data(values([4=>2,11=>1]),                            [2,0,1,3]),
            "Bde/Div"       => row_data(values([12=>1]),                                 [0,0,1,1]),
            "Pension Drill" => row_data(values([8=>4,12=>4]),                            [0,0,8,8]),
            "Total Absent"  => row_data(values([4=>3,8=>8,10=>1,11=>5,12=>14]),          [3,0,28,31]),
            "Total Present" => row_data(values([8=>10,10=>6,11=>4,12=>4]),               [0,0,24,24]),
        ])
    ],
    "D" => [
        "title" => "D COY", "color" => "#854d0e",
        "leave" => ["ON LEAVE" => 3, "LEAVE DUE" => 42, "TOTAL STR" => 45, "LEAVE %" => 7],
        "rows"  => company_rows([
            "POSTED"        => row_data(values([8=>13,10=>13,11=>15,12=>4]), [0,0,45,45]),
            "In Unit"       => row_data(values([8=>10,10=>9,11=>12,12=>3]),  [0,0,34,34]),
            "AL"            => row_data(values([8=>1,11=>2]),                [0,0,3,3]),
            "Att Duty"      => row_data(values([8=>1,10=>2,11=>1]),          [0,0,4,4]),
            "Fwd/Op Area"   => row_data(values([8=>1,10=>1,12=>1]),          [0,0,3,3]),
            "Pension Drill" => row_data(values([10=>1]),                     [0,0,1,1]),
            "Total Absent"  => row_data(values([8=>3,10=>4,11=>3,12=>1]),    [0,0,11,11]),
            "Total Present" => row_data(values([8=>10,10=>9,11=>12,12=>3]),  [0,0,34,34]),
        ])
    ],
    "SP" => [
        "title" => "SP COY", "color" => "#7f3f46",
        "leave" => ["ON LEAVE" => 6, "LEAVE DUE" => 83, "TOTAL STR" => 89, "LEAVE %" => 7],
        "rows"  => company_rows([
            "POSTED"        => row_data(values([2=>3,3=>3,8=>21,10=>16,11=>23,12=>23]), [6,0,83,89]),
            "In Unit"       => row_data(values([2=>2,8=>13,10=>7,11=>15,12=>14]),       [2,0,49,51]),
            "AL"            => row_data(values([3=>1,12=>1]),                           [1,0,1,2]),
            "CL"            => row_data(values([3=>1,8=>1,10=>1,12=>1]),               [1,0,3,4]),
            "Temp Duty"     => row_data(values([3=>1]),                                 [1,0,0,1]),
            "Local Att"     => row_data(values([8=>1]),                                 [0,0,1,1]),
            "Att Duty"      => row_data(values([8=>1,10=>3]),                           [0,0,4,4]),
            "Course/Cadre"  => row_data(values([12=>1]),                               [0,0,1,1]),
            "Fwd/Op Area"   => row_data(values([10=>1,11=>1]),                         [0,0,2,2]),
            "Bde/Div"       => row_data(values([10=>1,11=>1]),                         [0,0,2,2]),
            "Pension Drill" => row_data(values([8=>2,10=>1,11=>3,12=>2]),              [0,0,8,8]),
            "Posting Out"   => row_data(values([8=>1,12=>1]),                          [0,0,2,2]),
            "Total Absent"  => row_data(values([3=>3,8=>6,10=>7,11=>5,12=>6]),         [3,0,24,27]),
            "Total Present" => row_data(values([2=>3,8=>15,10=>9,11=>18,12=>17]),      [3,0,59,62]),
        ])
    ],
    "HQ" => [
        "title" => "HQ COY", "color" => "#374151", "totalColor" => "#3f6212",
        "leave" => ["ON LEAVE" => 9, "LEAVE DUE" => 91, "TOTAL STR" => 100, "LEAVE %" => 9],
        "rows"  => company_rows([
            "POSTED"        => row_data(values([3=>2,8=>24,10=>29,11=>24,12=>21]), [2,0,98,100]),
            "In Unit"       => row_data(values([8=>20,10=>24,11=>18,12=>19]),      [0,0,81,81]),
            "AL"            => row_data(values([3=>2,8=>2,10=>2,11=>2,12=>1]),     [2,0,7,9]),
            "Att Duty"      => row_data(values([10=>1,11=>1,12=>1]),               [0,0,3,3]),
            "Total Absent"  => row_data(values([3=>2,8=>2,10=>3,11=>3,12=>2]),     [2,0,10,12]),
            "Total Present" => row_data(values([8=>22,10=>26,11=>21,12=>19]),      [0,0,88,88]),
        ])
    ],
];

$selectedCompany = $_GET["company"] ?? "all";
$reports = ($selectedCompany !== "all" && isset($companies[$selectedCompany]))
    ? [$selectedCompany => $companies[$selectedCompany]]
    : $companies;

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Battalion Parade State | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:900; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:var(--bg-primary); border:1px solid var(--border-subtle); border-radius:10px; padding:28px 32px; width:100%; max-width:480px; }
        .modal-box h3 { margin:0 0 18px; font-size:16px; }
        .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
        .alert { padding:10px 14px; border-radius:6px; margin-bottom:16px; font-size:13px; }
        .alert.success { background:rgba(34,197,94,.15); color:var(--status-present,#22c55e); border:1px solid rgba(34,197,94,.3); }
        .alert.error   { background:rgba(239,68,68,.12);  color:var(--status-absent,#ef4444);  border:1px solid rgba(239,68,68,.25); }
        @media print {
            .topbar, .sitenav, .breadcrumb, .no-print, nav { display:none !important; }
            body { background:#fff !important; color:#000 !important; }
            .company-title, .company-date { color:#000 !important; background:#eee !important; }
            .parade-state-table td, .parade-state-table th { border:1px solid #999 !important; color:#000 !important; }
        }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>

<?php if ($addMsg): ?>
<div class="modal-overlay open" id="resultOverlay">
    <div class="modal-box" style="max-width:380px;text-align:center;">
        <?php if ($addMsg === "ok"): ?>
            <div class="alert success">Soldier added successfully. The Nominal Roll has been updated.</div>
        <?php else: ?>
            <div class="alert error"><?php echo h(substr($addMsg, 4)); ?></div>
        <?php endif; ?>
        <div class="modal-actions" style="justify-content:center;">
            <button class="btn" onclick="document.getElementById('resultOverlay').classList.remove('open')">Close</button>
            <?php if ($addMsg === "ok"): ?>
            <a class="btn secondary" href="../admin/manage_soldiers.php">View Nominal Roll</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<main id="main-content" class="container wide-container">
    <nav class="breadcrumb no-print" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Battalion Parade State</span>
    </nav>

    <!-- Page header -->
    <div class="page-header-row no-print">
        <div class="page-header">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Battalion Parade State</h1>
            <p class="page-sub">Date: <strong><?php echo h($istDate); ?> (IST)</strong>
                <?php if ($isPriv && $pendingCount > 0): ?>
                &nbsp;&mdash;&nbsp;<span style="color:var(--saffron);font-weight:600;"><?php echo $pendingCount; ?> pending approval(s)</span>
                <?php endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
            <?php if ($isPriv): ?>
            <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
                <input type="hidden" name="company" value="<?php echo h($selectedCompany); ?>">
                <div class="tf"><label for="v-date" style="font-size:11px;">Date</label>
                    <input type="date" id="v-date" name="date" value="<?php echo h($viewDate); ?>" style="min-height:38px;margin:0;"></div>
                <button class="btn secondary" type="submit">Go</button>
            </form>
            <a class="btn secondary" href="?date=<?php echo h($viewDate); ?>&export=excel">&#8659; Excel</a>
            <a class="btn secondary" href="?date=<?php echo h($viewDate); ?>&export=pdf">&#8659; PDF</a>
            <button class="btn" onclick="window.print()">&#128438; Print</button>
            <?php if ($pendingCount > 0): ?>
            <a class="btn" href="approve_parade_state.php?date=<?php echo h($viewDate); ?>">Approve</a>
            <?php endif; ?>
            <button class="btn" onclick="document.getElementById('addModal').classList.add('open')">+ Add Soldier</button>
            <?php else: ?>
            <button class="btn secondary" onclick="window.print()">&#128438; Print</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isPriv): ?>
    <!-- ── Live BN Summary ─────────────────────────────────── -->
    <div class="summary-tiles no-print">
        <div class="summary-tile"><strong><?php echo $totals['total_serving']; ?></strong><span>BN Strength</span></div>
        <div class="summary-tile green"><strong><?php echo $totals['present']; ?></strong><span>Present</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['absent']; ?></strong><span>Absent</span></div>
        <div class="summary-tile blue"><strong><?php echo $totals['on_leave']; ?></strong><span>On Leave</span></div>
        <div class="summary-tile purple"><strong><?php echo $totals['on_course']; ?></strong><span>On Course</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['on_duty']; ?></strong><span>On Duty</span></div>
        <div class="summary-tile red"><strong><?php echo $totals['sick']; ?></strong><span>Sick/MH</span></div>
        <div class="summary-tile"><strong><?php echo $totals['td']; ?></strong><span>TD</span></div>
        <div class="summary-tile orange"><strong><?php echo $totals['not_entered']; ?></strong><span>Not Entered</span></div>
        <?php $pc = $bnPct >= 80 ? 'var(--status-present)' : ($bnPct >= 60 ? 'var(--saffron)' : 'var(--status-absent)'); ?>
        <div class="summary-tile" style="border-left-color:<?php echo $pc; ?>">
            <strong style="color:<?php echo $pc; ?>"><?php echo $bnPct; ?>%</strong><span>BN Present</span>
        </div>
    </div>

    <?php if (!empty($bnRows)): ?>
    <div class="panel no-print" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div class="table-wrap">
            <table aria-label="Battalion parade state summary">
                <thead>
                    <tr>
                        <th>Company</th><th class="num">Strength</th>
                        <th class="num text-green">Present</th><th class="num text-red">Absent</th>
                        <th class="num text-blue">Leave</th><th class="num text-purple">Course</th>
                        <th class="num text-orange">Duty</th><th class="num text-red">Sick</th>
                        <th class="num">TD</th><th class="num">Others</th>
                        <th class="num text-orange">N/E</th><th class="num text-orange">Pending</th>
                        <th>Present&nbsp;%</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bnRows as $c):
                    $pct = $c['total_serving'] > 0 ? round($c['present'] / $c['total_serving'] * 100) : 0;
                    $pctColor = $pct >= 80 ? 'var(--status-present)' : ($pct >= 60 ? 'var(--saffron)' : 'var(--status-absent)');
                ?>
                <tr>
                    <td><strong><?php echo h($c['company_name']); ?></strong></td>
                    <td class="num fw-bold"><?php echo (int)$c['total_serving']; ?></td>
                    <td class="num text-green fw-bold"><?php echo (int)$c['present']; ?></td>
                    <td class="num text-red"><?php echo (int)$c['absent']; ?></td>
                    <td class="num text-blue"><?php echo (int)$c['on_leave']; ?></td>
                    <td class="num text-purple"><?php echo (int)$c['on_course']; ?></td>
                    <td class="num text-orange"><?php echo (int)$c['on_duty']; ?></td>
                    <td class="num text-red"><?php echo (int)$c['sick']; ?></td>
                    <td class="num"><?php echo (int)$c['td']; ?></td>
                    <td class="num"><?php echo (int)$c['others']; ?></td>
                    <td class="num text-orange"><?php echo (int)$c['not_entered']; ?></td>
                    <td class="num"><?php echo $c['pending_approval'] > 0 ? '<span style="color:var(--saffron);font-weight:700;">' . (int)$c['pending_approval'] . '</span>' : '—'; ?></td>
                    <td>
                        <div class="strength-bar-cell">
                            <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $pctColor; ?>;"></div></div>
                            <span class="strength-pct" style="color:<?php echo $pctColor; ?>"><?php echo $pct; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-elevated);border-top:2px solid var(--gold-muted);">
                        <th>BN Total</th>
                        <th class="num"><?php echo $totals['total_serving']; ?></th>
                        <th class="num text-green"><?php echo $totals['present']; ?></th>
                        <th class="num text-red"><?php echo $totals['absent']; ?></th>
                        <th class="num text-blue"><?php echo $totals['on_leave']; ?></th>
                        <th class="num text-purple"><?php echo $totals['on_course']; ?></th>
                        <th class="num text-orange"><?php echo $totals['on_duty']; ?></th>
                        <th class="num text-red"><?php echo $totals['sick']; ?></th>
                        <th class="num"><?php echo $totals['td']; ?></th>
                        <th class="num"><?php echo $totals['others']; ?></th>
                        <th class="num text-orange"><?php echo $totals['not_entered']; ?></th>
                        <th class="num"><?php echo $totals['pending_approval'] > 0 ? '<span style="color:var(--saffron);font-weight:700;">' . $totals['pending_approval'] . '</span>' : '—'; ?></th>
                        <th><span class="mono-sm" style="color:<?php echo $pc; ?>"><?php echo $bnPct; ?>%</span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <hr style="border:none;border-top:1px solid var(--border-subtle);margin:0 0 24px;">
    <?php endif; ?>

    <!-- ── Company Filter ─────────────────────────────────── -->
    <div class="panel no-print">
        <div class="panel-heading">
            <div>
                <h2>Company-wise Detailed Parade State</h2>
                <p class="muted">Rank-wise breakdown with leave and absence details.</p>
            </div>
            <form method="GET" class="inline-filter">
                <?php if ($isPriv): ?><input type="hidden" name="date" value="<?php echo h($viewDate); ?>"><?php endif; ?>
                <label>Company</label>
                <select name="company" onchange="this.form.submit()">
                    <option value="all" <?php echo $selectedCompany === "all" ? "selected" : ""; ?>>All Companies</option>
                    <?php foreach ($companies as $key => $coy): ?>
                    <option value="<?php echo h($key); ?>" <?php echo $selectedCompany === $key ? "selected" : ""; ?>>
                        <?php echo h($coy["title"]); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- ── Company Detailed Tables ────────────────────────── -->
    <?php foreach ($reports as $report):
        $totalColor = $report["totalColor"] ?? "#0f766e";
    ?>
    <div class="company-report">
        <div class="company-title-row">
            <div class="company-title" style="background:<?php echo h($report["color"]); ?>;">
                DAILY PARADE STATE OF XYZ BN AS ON : <?php echo h($report["title"]); ?>
            </div>
            <div class="company-date" style="background:<?php echo h($report["dateColor"] ?? "#122944"); ?>;">
                DATE: <span><?php echo h($istDate); ?></span>
            </div>
        </div>

        <div class="company-report-grid">
            <div class="table-wrap">
                <table class="parade-state-table company-state-table" style="--total-color:<?php echo h($totalColor); ?>;">
                    <thead>
                        <tr>
                            <th rowspan="2" class="row-label">POSTED</th>
                            <th colspan="13"></th>
                            <th colspan="2">Clk</th>
                            <th colspan="2">ERE</th>
                            <th rowspan="2">RT JCO</th>
                            <th colspan="4">TOTAL</th>
                        </tr>
                        <tr>
                            <?php foreach ($rankColumns as $col): ?>
                                <?php if ($col !== "RT JCO"): ?>
                                    <th><?php echo h($col); ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php foreach ($totalColumns as $col): ?>
                                <th><?php echo h($col); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["rows"] as $label => $row): ?>
                        <tr class="<?php echo h($row["class"] ?? ""); ?>">
                            <th class="row-label"><?php echo h($label); ?></th>
                            <?php foreach ($row["values"] as $val): ?>
                                <td><?php echo h((string)$val); ?></td>
                            <?php endforeach; ?>
                            <?php foreach ($row["totals"] as $val): ?>
                                <td class="total-cell"><?php echo h((string)$val); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <table class="leave-table">
                <thead>
                    <tr><th colspan="2"><?php echo h($report["title"]); ?> LEAVE %</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($report["leave"] as $lbl => $val): ?>
                    <tr>
                        <th><?php echo h($lbl); ?></th>
                        <td><?php echo h((string)$val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</main>

<?php if ($isPriv): ?>
<!-- ── Add Soldier Modal ───────────────────────────────────── -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <h3>Add Soldier to Battalion</h3>
        <p class="muted" style="margin:-10px 0 16px;font-size:12px;">The soldier will automatically appear in the Nominal Roll.</p>
        <form method="POST" action="?date=<?php echo h($viewDate); ?>&company=<?php echo h($selectedCompany); ?>">
            <input type="hidden" name="action" value="add_soldier">
            <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                <div class="tf">
                    <label>Army No <span style="color:var(--saffron)">*</span></label>
                    <input type="text" name="army_no" placeholder="e.g. 15489234P" required>
                </div>
                <div class="tf">
                    <label>Rank <span style="color:var(--saffron)">*</span></label>
                    <input type="text" name="rank_name" placeholder="e.g. Sep, Hav, Nk" required>
                </div>
                <div class="tf" style="grid-column:1/-1;">
                    <label>Full Name <span style="color:var(--saffron)">*</span></label>
                    <input type="text" name="full_name" placeholder="Full name" required>
                </div>
                <div class="tf">
                    <label>Company <span style="color:var(--saffron)">*</span></label>
                    <select name="company_id" required>
                        <option value="">Select Company</option>
                        <?php foreach ($dbCompanies as $dc): ?>
                        <option value="<?php echo h($dc['id']); ?>"><?php echo h($dc['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tf">
                    <label>Trade</label>
                    <input type="text" name="trade" placeholder="Optional">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn">Add Soldier</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
</body>
</html>
