<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php");
    exit;
}

$role = $_SESSION["role"];
if (!in_array($role, ["ADMIN", "ADJT_SA", "CHM_CLERK"])) {
    header("Location: ../dashboard.php");
    exit;
}

include("../config/db.php");

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function pdf_text($value) {
    return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], (string) $value);
}

function build_nominal_roll_pdf($rows) {
    $objects = [];
    $pages   = [];
    $chunks  = array_chunk($rows, 32);
    if (!$chunks) $chunks = [[]];

    foreach ($chunks as $chunk) {
        $lines   = [];
        $lines[] = "BT /F1 16 Tf 40 550 Td (Nominal Roll Personnel) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 528 Td (Army No        Rank   Name                               Company      Status) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 516 Td (--------------------------------------------------------------------------) Tj ET";
        $y       = 502;

        foreach ($chunk as $row) {
            $line    = sprintf(
                "%-14s %-6s %-34s %-12s %s",
                substr($row["army_no"], 0, 14),
                substr($row["rank_name"], 0, 6),
                substr($row["full_name"], 0, 34),
                substr($row["company_name"], 0, 12),
                $row["service_status"]
            );
            $lines[] = "BT /F1 9 Tf 40 $y Td (" . pdf_text($line) . ") Tj ET";
            $y -= 14;
        }

        $content   = implode("\n", $lines);
        $contentId = count($objects) + 1;
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        $pageId    = count($objects) + 1;
        $objects[] = "<< /Type /Page /Parent 0 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 0 0 R >> >> /Contents $contentId 0 R >>";
        $pages[]   = $pageId;
    }

    $fontId  = count($objects) + 1;
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $pagesId   = count($objects) + 1;
    $kids      = implode(" ", array_map(fn($id) => "$id 0 R", $pages));
    $objects[] = "<< /Type /Pages /Kids [$kids] /Count " . count($pages) . " >>";
    $catalogId = count($objects) + 1;
    $objects[] = "<< /Type /Catalog /Pages $pagesId 0 R >>";

    foreach ($pages as $pageId) {
        $objects[$pageId - 1] = str_replace(
            ["/Parent 0 0 R", "/F1 0 0 R"],
            ["/Parent $pagesId 0 R", "/F1 $fontId 0 R"],
            $objects[$pageId - 1]
        );
    }

    $pdf     = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n$object\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root $catalogId 0 R >>\nstartxref\n$xref\n%%EOF";

    return $pdf;
}

$isClerk         = ($role === "CHM_CLERK");
$sessionCompanyId = (int) ($_SESSION["company_id"] ?? 0);

// ── Handle POST: add new personnel ──────────────────────────────────────────
$addMsg = "";
$addErr = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_personnel"])) {
    $newArmyNo    = trim($_POST["n_army_no"] ?? "");
    $newRank      = trim($_POST["n_rank"] ?? "");
    $newName      = trim($_POST["n_full_name"] ?? "");
    $newTrade     = trim($_POST["n_trade"] ?? "");
    $newStatus    = trim($_POST["n_service_status"] ?? "Serving");
    $newRemark    = trim($_POST["n_status_remark"] ?? "");
    $newDob       = trim($_POST["n_dob"] ?? "");
    $newDoe       = trim($_POST["n_date_of_enrolment"] ?? "");
    $newDOJ       = trim($_POST["n_date_of_joining"] ?? "");
    $newBlood     = trim($_POST["n_blood_group"] ?? "");
    $newState     = trim($_POST["n_home_state"] ?? "");
    $newPin       = trim($_POST["n_pin_code"] ?? "");
    $newMobile    = trim($_POST["n_mobile_no"] ?? "");
    $newMarital   = trim($_POST["n_marital_status"] ?? "Single");
    $newMedCat    = trim($_POST["n_med_cat"] ?? "AYE");
    $newAL        = (int)($_POST["n_al_balance"] ?? 0);
    $newCL        = (int)($_POST["n_cl_balance"] ?? 0);
    $newTeam      = trim($_POST["n_bn_team"] ?? "");
    $newEmergency = trim($_POST["n_emergency_contact"] ?? "");
    $newPlatoon   = (int)($_POST["n_platoon_id"] ?? 0) ?: null;

    // Clerk's company is always from session; admin/adjt pick from POST
    $newCompany = $isClerk ? $sessionCompanyId : (int)($_POST["n_company_id"] ?? 0);

    $validStats = ["Serving","Attached Out","Posted Out","Retired","Other"];

    if ($newArmyNo === "" || $newRank === "" || $newName === "") {
        $addErr = "Army No, Rank and Name are required.";
    } elseif (!in_array($newStatus, $validStats)) {
        $addErr = "Invalid service status.";
    } elseif ($newStatus === "Other" && $newRemark === "") {
        $addErr = "Remark is required when status is 'Other'.";
    } else {
        // Check duplicate army_no
        $dup = mysqli_prepare($conn, "SELECT id FROM personnel WHERE army_no = ?");
        mysqli_stmt_bind_param($dup, "s", $newArmyNo);
        mysqli_stmt_execute($dup);
        mysqli_stmt_store_result($dup);
        if (mysqli_stmt_num_rows($dup) > 0) {
            $addErr = "Army No &laquo;" . h($newArmyNo) . "&raquo; already exists.";
        }
        mysqli_stmt_close($dup);
    }

    if (!$addErr) {
        $remarkVal  = ($newStatus === "Other") ? $newRemark : null;
        $dobVal     = $newDob     ?: null;
        $doeVal     = $newDoe     ?: null;
        $dojVal     = $newDOJ     ?: null;
        $bloodVal   = $newBlood   ?: null;
        $stateVal   = $newState   ?: null;
        $pinVal     = $newPin     ?: null;
        $mobileVal  = $newMobile  ?: null;
        $teamVal    = $newTeam    ?: null;
        $emergVal   = $newEmergency ?: null;
        $tradeVal   = $newTrade   ?: null;

        $ins = mysqli_prepare($conn,
            "INSERT INTO personnel
             (army_no, rank_name, full_name, company_id, platoon_id, trade,
              service_status, status_remark, dob, date_of_enrolment, date_of_joining,
              blood_group, home_state, pin_code, mobile_no, marital_status, med_cat,
              al_balance, cl_balance, bn_team, emergency_contact)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($ins, "sssiisssssssssssssiiss",
            $newArmyNo, $newRank, $newName, $newCompany, $newPlatoon, $tradeVal,
            $newStatus, $remarkVal, $dobVal, $doeVal, $dojVal, $bloodVal,
            $stateVal, $pinVal, $mobileVal, $newMarital, $newMedCat,
            $newAL, $newCL, $teamVal, $emergVal);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
        $addMsg = "Personnel &laquo;" . h($newName) . "&raquo; added successfully.";
    }
}

// ── Handle POST: update service status ─────────────────────────────────────
$updateMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    $upArmyNo   = trim($_POST["army_no"] ?? "");
    $upStatus   = trim($_POST["service_status"] ?? "");
    $upRemark   = trim($_POST["status_remark"] ?? "");
    $validStats = ["Serving", "Attached Out", "Posted Out", "Retired", "Other"];

    if ($upArmyNo !== "" && in_array($upStatus, $validStats)) {
        $allowed = true;

        // Clerks may only edit personnel in their own company
        if ($isClerk) {
            $chk = mysqli_prepare($conn, "SELECT id FROM personnel WHERE army_no = ? AND company_id = ?");
            mysqli_stmt_bind_param($chk, "si", $upArmyNo, $sessionCompanyId);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) === 0) $allowed = false;
            mysqli_stmt_close($chk);
        }

        if ($allowed) {
            $upTrade   = trim($_POST["trade"] ?? "");
            $upRank    = trim($_POST["rank_name"] ?? "");
            $upDOJ     = trim($_POST["date_of_joining"] ?? "") ?: null;
            $remarkVal = ($upStatus === "Other") ? $upRemark : null;
            $upd = mysqli_prepare($conn,
                "UPDATE personnel SET trade = ?, rank_name = ?, service_status = ?, status_remark = ?, date_of_joining = ? WHERE army_no = ?");
            mysqli_stmt_bind_param($upd, "ssssss", $upTrade, $upRank, $upStatus, $remarkVal, $upDOJ, $upArmyNo);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $updateMsg = "Record updated successfully.";
        }
    }
}

// ── Filters ─────────────────────────────────────────────────────────────────
$armyNo    = trim($_GET["army_no"] ?? "");
$companyId = trim($_GET["company_id"] ?? "");
$rank      = trim($_GET["rank"] ?? "");
$sort      = $_GET["sort"] ?? "name";
$direction = strtolower($_GET["direction"] ?? "asc") === "desc" ? "DESC" : "ASC";
$export    = $_GET["export"] ?? "";

// Clerks are always restricted to their own company — ignore any GET override
if ($isClerk) {
    $companyId = (string) $sessionCompanyId;
}

$sortColumns = [
    "name"    => "p.full_name",
    "army_no" => "p.army_no",
    "rank"    => "p.rank_name",
    "company" => "c.company_name",
];
$sortColumn = $sortColumns[$sort] ?? $sortColumns["name"];

$where  = [];
$params = [];
$types  = "";

if ($armyNo !== "") {
    $where[]  = "p.army_no LIKE ?";
    $params[] = "%" . $armyNo . "%";
    $types   .= "s";
}
if ($companyId !== "") {
    $where[]  = "p.company_id = ?";
    $params[] = (int) $companyId;
    $types   .= "i";
}
if ($rank !== "") {
    $where[]  = "p.rank_name = ?";
    $params[] = $rank;
    $types   .= "s";
}

// Auto-add missing columns (status_remark, date_of_joining)
$colCheck = mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel' AND COLUMN_NAME = 'status_remark'");
if (!(int)(mysqli_fetch_assoc($colCheck)["cnt"] ?? 0)) {
    mysqli_query($conn,
        "ALTER TABLE personnel
           MODIFY COLUMN service_status ENUM('Serving','Attached Out','Posted Out','Retired','Other') DEFAULT 'Serving',
           ADD COLUMN status_remark VARCHAR(255) DEFAULT NULL AFTER service_status");
}
$dojCheck = mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'personnel' AND COLUMN_NAME = 'date_of_joining'");
if (!(int)(mysqli_fetch_assoc($dojCheck)["cnt"] ?? 0)) {
    mysqli_query($conn, "ALTER TABLE personnel ADD COLUMN date_of_joining DATE DEFAULT NULL AFTER date_of_enrolment");
}

$whereSql  = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql       = "SELECT p.army_no, p.rank_name, p.full_name, p.trade,
                     p.service_status, p.status_remark, p.date_of_joining,
                     c.company_name, c.short_name,
                     CASE
                       WHEN p.rank_name IN ('Sep','Lnk') THEN DATE_ADD(p.date_of_joining, INTERVAL 17 YEAR)
                       WHEN p.rank_name = 'Nk'           THEN DATE_ADD(p.date_of_joining, INTERVAL 22 YEAR)
                       WHEN p.rank_name = 'Hav'          THEN DATE_ADD(p.date_of_joining, INTERVAL 24 YEAR)
                       WHEN p.rank_name IN ('Nb Sub','NbSub') THEN DATE_ADD(p.date_of_joining, INTERVAL 26 YEAR)
                       WHEN p.rank_name = 'Sub'          THEN DATE_ADD(p.date_of_joining, INTERVAL 28 YEAR)
                       ELSE DATE_ADD(p.date_of_joining, INTERVAL 32 YEAR)
                     END AS retirement_date,
                     DATEDIFF(
                       CASE
                         WHEN p.rank_name IN ('Sep','Lnk') THEN DATE_ADD(p.date_of_joining, INTERVAL 17 YEAR)
                         WHEN p.rank_name = 'Nk'           THEN DATE_ADD(p.date_of_joining, INTERVAL 22 YEAR)
                         WHEN p.rank_name = 'Hav'          THEN DATE_ADD(p.date_of_joining, INTERVAL 24 YEAR)
                         WHEN p.rank_name IN ('Nb Sub','NbSub') THEN DATE_ADD(p.date_of_joining, INTERVAL 26 YEAR)
                         WHEN p.rank_name = 'Sub'          THEN DATE_ADD(p.date_of_joining, INTERVAL 28 YEAR)
                         ELSE DATE_ADD(p.date_of_joining, INTERVAL 32 YEAR)
                       END, CURDATE()
                     ) AS days_to_retirement
              FROM personnel p
              JOIN companies c ON c.id = p.company_id
              $whereSql
              ORDER BY $sortColumn $direction, p.full_name ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die('<p style="color:red;padding:20px;">Query error: ' . h(mysqli_error($conn)) . '</p>');
}
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result    = mysqli_stmt_get_result($stmt);
$personnel = mysqli_fetch_all($result, MYSQLI_ASSOC);

$companies = mysqli_query($conn, "SELECT id, company_name, short_name FROM companies ORDER BY id");
$ranks     = mysqli_query($conn, "SELECT DISTINCT rank_name FROM personnel ORDER BY rank_name");

// Clerk's own company name for heading
$clerkCompanyName = "";
$clerkPlatoons    = [];
if ($isClerk && $sessionCompanyId) {
    $cq = mysqli_prepare($conn, "SELECT company_name FROM companies WHERE id = ?");
    mysqli_stmt_bind_param($cq, "i", $sessionCompanyId);
    mysqli_stmt_execute($cq);
    $cqR = mysqli_stmt_get_result($cq);
    $clerkCompanyName = mysqli_fetch_assoc($cqR)["company_name"] ?? "";
    mysqli_stmt_close($cq);

    $pq = mysqli_prepare($conn, "SELECT id, platoon_name FROM platoons WHERE company_id = ? AND is_active = 1 ORDER BY platoon_name");
    mysqli_stmt_bind_param($pq, "i", $sessionCompanyId);
    mysqli_stmt_execute($pq);
    $pqR = mysqli_stmt_get_result($pq);
    while ($pl = mysqli_fetch_assoc($pqR)) $clerkPlatoons[] = $pl;
    mysqli_stmt_close($pq);
}

if ($export === "excel") {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=nominal_roll_personnel.xls");
    echo "<table border=\"1\">";
    echo "<tr><th>Army No</th><th>Rank</th><th>Name</th><th>Company</th><th>Trade</th><th>Date of Joining</th><th>Retirement Date</th><th>Status</th><th>Remark</th></tr>";
    foreach ($personnel as $row) {
        echo "<tr>"
           . "<td>" . h($row["army_no"]) . "</td>"
           . "<td>" . h($row["rank_name"]) . "</td>"
           . "<td>" . h($row["full_name"]) . "</td>"
           . "<td>" . h($row["company_name"]) . "</td>"
           . "<td>" . h($row["trade"]) . "</td>"
           . "<td>" . ($row["date_of_joining"] ? date("d M Y", strtotime($row["date_of_joining"])) : "") . "</td>"
           . "<td>" . ($row["retirement_date"] ? date("d M Y", strtotime($row["retirement_date"])) : "") . "</td>"
           . "<td>" . h($row["service_status"]) . "</td>"
           . "<td>" . h($row["status_remark"] ?? "") . "</td>"
           . "</tr>";
    }
    echo "</table>";
    exit;
}

if ($export === "pdf") {
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=nominal_roll_personnel.pdf");
    echo build_nominal_roll_pdf($personnel);
    exit;
}

$queryParams = $_GET;
unset($queryParams["export"]);
$baseQuery    = http_build_query($queryParams);
$exportPrefix = $baseQuery ? $baseQuery . "&" : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Nominal Roll | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>

<!-- Edit Personnel Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#1a2332;border:1px solid #2d3f58;border-radius:10px;padding:28px 32px;width:460px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.6);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <div>
                <h3 style="margin:0 0 4px;font-size:16px;">Edit Personnel Record</h3>
                <p id="modal_soldier" style="margin:0;color:#8fa8c6;font-size:13px;"></p>
            </div>
            <button type="button" onclick="closeModal()" style="background:none;border:none;color:#8fa8c6;font-size:20px;cursor:pointer;line-height:1;padding:0 0 0 12px;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="army_no" id="modal_army_no">
            <?php foreach ($_GET as $k => $v): ?>
                <input type="hidden" name="<?php echo h($k); ?>" value="<?php echo h($v); ?>">
            <?php endforeach; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label for="modal_rank" style="display:block;margin-bottom:4px;font-size:13px;">Rank</label>
                    <input type="text" id="modal_rank" name="rank_name" list="modal_rank_list"
                        placeholder="Sep, Nk, Hav…"
                        style="width:100%;box-sizing:border-box;">
                    <datalist id="modal_rank_list">
                        <option value="Sep"><option value="Lnk"><option value="Nk">
                        <option value="Hav"><option value="Nb Sub"><option value="Sub">
                        <option value="Sub Maj"><option value="Lt"><option value="Capt">
                        <option value="Maj"><option value="Col">
                    </datalist>
                </div>
                <div>
                    <label for="modal_trade" style="display:block;margin-bottom:4px;font-size:13px;">Trade</label>
                    <input type="text" id="modal_trade" name="trade" placeholder="e.g. Rifleman…"
                        style="width:100%;box-sizing:border-box;">
                </div>
            </div>

            <label for="modal_doj" style="display:block;margin-bottom:4px;font-size:13px;">Date of Joining</label>
            <input type="date" id="modal_doj" name="date_of_joining"
                style="width:100%;box-sizing:border-box;margin-bottom:14px;">

            <label for="modal_status" style="display:block;margin-bottom:4px;font-size:13px;">Service Status</label>
            <select id="modal_status" name="service_status" onchange="toggleRemark(this.value)"
                style="width:100%;box-sizing:border-box;margin-bottom:14px;">
                <option value="Serving">Serving</option>
                <option value="Attached Out">Attached Out</option>
                <option value="Posted Out">Posted Out</option>
                <option value="Retired">Retired</option>
                <option value="Other">Other</option>
            </select>

            <div id="remark_wrap" style="display:none;margin-bottom:14px;">
                <label for="modal_remark" style="display:block;margin-bottom:4px;font-size:13px;">
                    Remark <span style="color:#c9a227;">*</span>
                    <span style="color:#8fa8c6;font-weight:normal;">(required when status is Other)</span>
                </label>
                <textarea id="modal_remark" name="status_remark" rows="3"
                    placeholder="Describe the reason…"
                    style="width:100%;box-sizing:border-box;resize:vertical;"></textarea>
            </div>

            <div style="display:flex;gap:10px;margin-top:8px;">
                <button class="btn" type="submit" style="flex:1;">Save</button>
                <button class="btn secondary" type="button" onclick="closeModal()" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Nominal Roll</span>
    </nav>

    <?php if ($addMsg):   ?><div class="message success" style="margin-bottom:12px;"><?php echo $addMsg; ?></div><?php endif; ?>
    <?php if ($addErr):   ?><div class="message"         style="margin-bottom:12px;"><?php echo $addErr; ?></div><?php endif; ?>
    <?php if ($updateMsg): ?><div class="message success" style="margin-bottom:12px;"><?php echo h($updateMsg); ?></div><?php endif; ?>

    <div class="panel">
        <div class="page-header" style="margin-bottom:18px;">
            <div>
                <h1>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;vertical-align:-4px;margin-right:6px;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Nominal Roll<?php echo $clerkCompanyName ? " — " . h($clerkCompanyName) : ""; ?>
                </h1>
                <p class="page-sub">
                    <?php if ($isClerk): ?>
                        View and update service status for personnel in your company.
                    <?php else: ?>
                        Search, filter and view all serving personnel.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <form method="GET" class="form-grid filters">
            <div>
                <label>Search by Army No</label>
                <input type="text" name="army_no" value="<?php echo h($armyNo); ?>" placeholder="Enter Army No">
            </div>

            <?php if (!$isClerk): ?>
            <div>
                <label>Company</label>
                <select name="company_id">
                    <option value="">All Companies</option>
                    <?php
                    mysqli_data_seek($companies, 0);
                    while ($company = mysqli_fetch_assoc($companies)):
                    ?>
                        <option value="<?php echo h($company["id"]); ?>"
                            <?php echo (string) $companyId === (string) $company["id"] ? "selected" : ""; ?>>
                            <?php echo h($company["company_name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php else: ?>
                <!-- Clerk: company locked, pass silently -->
                <input type="hidden" name="company_id" value="<?php echo h($sessionCompanyId); ?>">
            <?php endif; ?>

            <div>
                <label>Rank</label>
                <select name="rank">
                    <option value="">All Ranks</option>
                    <?php while ($rankRow = mysqli_fetch_assoc($ranks)): ?>
                        <option value="<?php echo h($rankRow["rank_name"]); ?>"
                            <?php echo $rank === $rankRow["rank_name"] ? "selected" : ""; ?>>
                            <?php echo h($rankRow["rank_name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Sort By</label>
                <select name="sort">
                    <option value="name"    <?php echo $sort === "name"    ? "selected" : ""; ?>>Name</option>
                    <option value="army_no" <?php echo $sort === "army_no" ? "selected" : ""; ?>>Army No</option>
                    <option value="rank"    <?php echo $sort === "rank"    ? "selected" : ""; ?>>Rank</option>
                    <?php if (!$isClerk): ?>
                    <option value="company" <?php echo $sort === "company" ? "selected" : ""; ?>>Company</option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label>Order</label>
                <select name="direction">
                    <option value="asc"  <?php echo $direction === "ASC"  ? "selected" : ""; ?>>Ascending</option>
                    <option value="desc" <?php echo $direction === "DESC" ? "selected" : ""; ?>>Descending</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn" type="submit">Apply</button>
                <a class="btn secondary" href="manage_soldiers.php<?php echo $isClerk ? "?company_id=" . h($sessionCompanyId) : ""; ?>">Reset</a>
            </div>
        </form>
    </div>

    <!-- ── Add Personnel Modal ──────────────────────────────────────────── -->
    <div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:30px 0;">
        <div style="background:#1a2332;border:1px solid #2d3f58;border-radius:10px;padding:28px 32px;width:620px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.6);margin:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 style="margin:0;font-size:16px;">Add New Personnel — <?php echo h($clerkCompanyName); ?></h3>
                <button type="button" onclick="closeAddModal()" style="background:none;border:none;color:#8fa8c6;font-size:22px;cursor:pointer;line-height:1;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="add_personnel" value="1">

                <!-- Row 1: Army No + Rank -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Army No <span style="color:#c9a227;">*</span></label>
                        <input type="text" name="n_army_no" required placeholder="e.g. 2817543W"
                               value="<?php echo h($_POST['n_army_no'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Rank <span style="color:#c9a227;">*</span></label>
                        <input type="text" name="n_rank" required placeholder="e.g. Sep, Nk, Hav, Capt"
                               value="<?php echo h($_POST['n_rank'] ?? ''); ?>"
                               list="rank_list" style="width:100%;box-sizing:border-box;">
                        <datalist id="rank_list">
                            <option value="Sep"><option value="Lnk"><option value="Nk">
                            <option value="Hav"><option value="Nb Sub"><option value="Sub">
                            <option value="Sub Maj"><option value="Lt"><option value="Capt">
                            <option value="Maj"><option value="Col"><option value="JC">
                        </datalist>
                    </div>
                </div>

                <!-- Row 2: Full Name -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;display:block;margin-bottom:4px;">Full Name <span style="color:#c9a227;">*</span></label>
                    <input type="text" name="n_full_name" required placeholder="Surname Firstname"
                           value="<?php echo h($_POST['n_full_name'] ?? ''); ?>"
                           style="width:100%;box-sizing:border-box;">
                </div>

                <!-- Row 3: Trade + Platoon -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Trade</label>
                        <input type="text" name="n_trade" placeholder="e.g. Rifleman, Signalman"
                               value="<?php echo h($_POST['n_trade'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Platoon</label>
                        <select name="n_platoon_id" style="width:100%;box-sizing:border-box;">
                            <option value="">— None —</option>
                            <?php foreach ($clerkPlatoons as $pl): ?>
                                <option value="<?php echo h($pl['id']); ?>"><?php echo h($pl['platoon_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Row 4: Status + Remark -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;display:block;margin-bottom:4px;">Service Status</label>
                    <select name="n_service_status" id="add_status" onchange="toggleAddRemark(this.value)"
                            style="width:100%;box-sizing:border-box;">
                        <option value="Serving">Serving</option>
                        <option value="Attached Out">Attached Out</option>
                        <option value="Posted Out">Posted Out</option>
                        <option value="Retired">Retired</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div id="add_remark_wrap" style="display:none;margin-bottom:14px;">
                    <label style="font-size:13px;display:block;margin-bottom:4px;">
                        Remark <span style="color:#c9a227;">*</span>
                        <span style="color:#8fa8c6;font-weight:normal;">(required when Other)</span>
                    </label>
                    <textarea name="n_status_remark" id="add_remark" rows="2"
                        placeholder="Describe the reason…"
                        style="width:100%;box-sizing:border-box;resize:vertical;"><?php echo h($_POST['n_status_remark'] ?? ''); ?></textarea>
                </div>

                <hr style="border:none;border-top:1px solid #2d3f58;margin:18px 0;">
                <p style="font-size:12px;color:#8fa8c6;margin:0 0 14px;">Personal Details</p>

                <!-- Row 5: DOB + Date of Enrolment -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Date of Birth</label>
                        <input type="date" name="n_dob"
                               value="<?php echo h($_POST['n_dob'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Date of Enrolment</label>
                        <input type="date" name="n_date_of_enrolment"
                               value="<?php echo h($_POST['n_date_of_enrolment'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Row 5b: Date of Joining -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;display:block;margin-bottom:4px;">
                        Date of Joining
                        <span style="color:#8fa8c6;font-weight:normal;font-size:11px;">(used to calculate retirement date)</span>
                    </label>
                    <input type="date" name="n_date_of_joining"
                           value="<?php echo h($_POST['n_date_of_joining'] ?? ''); ?>"
                           style="width:50%;box-sizing:border-box;">
                </div>

                <!-- Row 6: Blood Group + Marital Status -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Blood Group</label>
                        <select name="n_blood_group" style="width:100%;box-sizing:border-box;">
                            <option value="">— Select —</option>
                            <?php foreach (["A+","A-","B+","B-","AB+","AB-","O+","O-"] as $bg): ?>
                                <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Marital Status</label>
                        <select name="n_marital_status" style="width:100%;box-sizing:border-box;">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                </div>

                <!-- Row 7: Med Cat + BN Team -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Med Category</label>
                        <select name="n_med_cat" style="width:100%;box-sizing:border-box;">
                            <option value="SHAPE-1">SHAPE-1</option>
                            <option value="AYE" selected>AYE</option>
                            <option value="BEE">BEE</option>
                            <option value="CEE">CEE</option>
                            <option value="LOW MEDICAL CATEGORY">LOW MEDICAL CATEGORY</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">BN Team</label>
                        <input type="text" name="n_bn_team" placeholder="e.g. Assault Team"
                               value="<?php echo h($_POST['n_bn_team'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Row 8: Home State + PIN -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Home State</label>
                        <input type="text" name="n_home_state" placeholder="e.g. Maharashtra"
                               value="<?php echo h($_POST['n_home_state'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">PIN Code</label>
                        <input type="text" name="n_pin_code" placeholder="6-digit PIN"
                               maxlength="10" value="<?php echo h($_POST['n_pin_code'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Row 9: Mobile + Emergency Contact -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Mobile No</label>
                        <input type="text" name="n_mobile_no" placeholder="10-digit mobile"
                               maxlength="15" value="<?php echo h($_POST['n_mobile_no'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">Emergency Contact</label>
                        <input type="text" name="n_emergency_contact" placeholder="10-digit mobile"
                               maxlength="15" value="<?php echo h($_POST['n_emergency_contact'] ?? ''); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Row 10: AL + CL Balance -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">AL Balance (days)</label>
                        <input type="number" name="n_al_balance" min="0" max="365"
                               value="<?php echo h($_POST['n_al_balance'] ?? '0'); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:13px;display:block;margin-bottom:4px;">CL Balance (days)</label>
                        <input type="number" name="n_cl_balance" min="0" max="365"
                               value="<?php echo h($_POST['n_cl_balance'] ?? '0'); ?>"
                               style="width:100%;box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button class="btn" type="submit" style="flex:1;">Add Personnel</button>
                    <button class="btn secondary" type="button" onclick="closeAddModal()" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Personnel List</h2>
                <p class="muted"><?php echo count($personnel); ?> record(s) found</p>
            </div>
            <div class="nav-actions">
                <button class="btn" onclick="openAddModal()" style="margin-right:6px;">+ Add Personnel</button>
                <a class="btn secondary" href="manage_soldiers.php?<?php echo h($exportPrefix); ?>export=pdf">Download PDF</a>
                <a class="btn secondary" href="manage_soldiers.php?<?php echo h($exportPrefix); ?>export=excel">Download Excel</a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Army No</th>
                        <th>Rank</th>
                        <th>Name</th>
                        <?php if (!$isClerk): ?><th>Company</th><?php endif; ?>
                        <th>Trade</th>
                        <th>Date of Joining</th>
                        <th>Retirement Date</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$personnel): ?>
                        <tr><td colspan="<?php echo $isClerk ? 9 : 10; ?>">No personnel found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($personnel as $row):
                        $statusClass = match($row["service_status"]) {
                            "Serving"      => "color:var(--status-present,#22863a)",
                            "Retired"      => "color:var(--muted,#8fa8c6)",
                            "Other"        => "color:var(--saffron,#e07b2a)",
                            default        => "color:var(--gold,#c9a227)",
                        };
                        $daysLeft = isset($row['days_to_retirement']) ? (int)$row['days_to_retirement'] : null;
                        if ($row['date_of_joining'] && $row['retirement_date']) {
                            if ($daysLeft < 0) {
                                $retStyle = 'color:var(--muted,#8fa8c6)';
                                $retTag   = '<span style="font-size:10px;background:rgba(239,68,68,.12);color:#f87171;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;">Completed</span>';
                            } elseif ($daysLeft <= 90) {
                                $retStyle = 'color:var(--status-absent,#d73a49);font-weight:700';
                                $retTag   = '<span style="font-size:10px;background:rgba(239,68,68,.15);color:#f87171;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;">' . $daysLeft . 'd</span>';
                            } elseif ($daysLeft <= 365) {
                                $retStyle = 'color:#d97706;font-weight:600';
                                $mos      = round($daysLeft / 30);
                                $retTag   = '<span style="font-size:10px;background:rgba(234,179,8,.15);color:#d97706;padding:1px 6px;border-radius:4px;font-weight:700;margin-left:4px;">' . $mos . ' mo</span>';
                            } else {
                                $retStyle = 'color:var(--text)';
                                $retTag   = '';
                            }
                        } else {
                            $retStyle = 'color:var(--muted,#8fa8c6)';
                            $retTag   = '';
                        }
                    ?>
                        <tr>
                            <td><?php echo h($row["army_no"]); ?></td>
                            <td><?php echo h($row["rank_name"]); ?></td>
                            <td><?php echo h($row["full_name"]); ?></td>
                            <?php if (!$isClerk): ?>
                            <td><?php echo h($row["company_name"]); ?></td>
                            <?php endif; ?>
                            <td><?php echo h($row["trade"] ?: "—"); ?></td>
                            <td style="font-size:13px;color:var(--muted,#8fa8c6);">
                                <?php echo $row["date_of_joining"] ? date("d M Y", strtotime($row["date_of_joining"])) : "—"; ?>
                            </td>
                            <td style="font-size:13px;<?php echo $retStyle; ?>;">
                                <?php if ($row["retirement_date"]): ?>
                                    <?php echo date("d M Y", strtotime($row["retirement_date"])); ?>
                                    <?php echo $retTag; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="<?php echo $statusClass; ?>;font-weight:600;">
                                <?php echo h($row["service_status"]); ?>
                            </td>
                            <td style="color:var(--muted,#8fa8c6);font-size:12px;">
                                <?php echo $row["status_remark"] ? h($row["status_remark"]) : "—"; ?>
                            </td>
                            <td>
                                <button class="btn secondary"
                                    style="padding:4px 10px;font-size:12px;"
                                    data-army-no="<?php echo h($row['army_no']); ?>"
                                    data-name="<?php echo h($row['full_name']); ?>"
                                    data-rank="<?php echo h($row['rank_name']); ?>"
                                    data-trade="<?php echo h($row['trade'] ?? ''); ?>"
                                    data-doj="<?php echo h($row['date_of_joining'] ?? ''); ?>"
                                    data-status="<?php echo h($row['service_status']); ?>"
                                    data-remark="<?php echo h($row['status_remark'] ?? ''); ?>"
                                    onclick="openEdit(this)">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
// ── Add Personnel Modal ───────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    document.getElementById('addModal').scrollTop = 0;
}
function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}
function toggleAddRemark(val) {
    var show = (val === 'Other');
    document.getElementById('add_remark_wrap').style.display = show ? 'block' : 'none';
    document.getElementById('add_remark').required = show;
}
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
<?php if ($addErr): ?>
// Re-open add modal if there was a validation error
window.addEventListener('DOMContentLoaded', openAddModal);
<?php endif; ?>

// ── Edit Personnel Modal ──────────────────────────────────────────────────
function openEdit(btn) {
    var armyNo = btn.dataset.armyNo;
    var name   = btn.dataset.name;
    var rank   = btn.dataset.rank   || '';
    var trade  = btn.dataset.trade  || '';
    var doj    = btn.dataset.doj    || '';
    var status = btn.dataset.status;
    var remark = btn.dataset.remark || '';

    document.getElementById('modal_army_no').value       = armyNo;
    document.getElementById('modal_soldier').textContent = name + '  ·  ' + armyNo;
    document.getElementById('modal_rank').value          = rank;
    document.getElementById('modal_trade').value         = trade;
    document.getElementById('modal_doj').value           = doj;
    document.getElementById('modal_status').value        = status;
    document.getElementById('modal_remark').value        = remark;
    toggleRemark(status);
    document.getElementById('editModal').style.display   = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function toggleRemark(val) {
    var show = (val === 'Other');
    document.getElementById('remark_wrap').style.display = show ? 'block' : 'none';
    document.getElementById('modal_remark').required     = show;
}

// Close on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
