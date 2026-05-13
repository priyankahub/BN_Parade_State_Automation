<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php");
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
    $pages = [];
    $chunks = array_chunk($rows, 32);
    if (!$chunks) {
        $chunks = [[]];
    }

    foreach ($chunks as $chunk) {
        $lines = [];
        $lines[] = "BT /F1 16 Tf 40 550 Td (Nominal Roll Personnel) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 528 Td (Army No        Rank   Name                               Company      Status) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 516 Td (--------------------------------------------------------------------------) Tj ET";
        $y = 502;

        foreach ($chunk as $row) {
            $line = sprintf(
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

        $content = implode("\n", $lines);
        $contentId = count($objects) + 1;
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        $pageId = count($objects) + 1;
        $objects[] = "<< /Type /Page /Parent 0 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 0 0 R >> >> /Contents $contentId 0 R >>";
        $pages[] = $pageId;
    }

    $fontId = count($objects) + 1;
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $pagesId = count($objects) + 1;
    $kids = implode(" ", array_map(function ($id) {
        return "$id 0 R";
    }, $pages));
    $objects[] = "<< /Type /Pages /Kids [$kids] /Count " . count($pages) . " >>";
    $catalogId = count($objects) + 1;
    $objects[] = "<< /Type /Catalog /Pages $pagesId 0 R >>";

    foreach ($pages as $pageId) {
        $objects[$pageId - 1] = str_replace(["/Parent 0 0 R", "/F1 0 0 R"], ["/Parent $pagesId 0 R", "/F1 $fontId 0 R"], $objects[$pageId - 1]);
    }

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $objectNumber = $index + 1;
        $pdf .= "$objectNumber 0 obj\n$object\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root $catalogId 0 R >>\nstartxref\n$xref\n%%EOF";

    return $pdf;
}

$armyNo = trim($_GET["army_no"] ?? "");
$companyId = trim($_GET["company_id"] ?? "");
$rank = trim($_GET["rank"] ?? "");
$sort = $_GET["sort"] ?? "name";
$direction = strtolower($_GET["direction"] ?? "asc") === "desc" ? "DESC" : "ASC";
$export = $_GET["export"] ?? "";

$sortColumns = [
    "name" => "p.full_name",
    "army_no" => "p.army_no",
    "rank" => "p.rank_name",
    "company" => "c.company_name"
];
$sortColumn = $sortColumns[$sort] ?? $sortColumns["name"];

$where = [];
$params = [];
$types = "";

if ($armyNo !== "") {
    $where[] = "p.army_no LIKE ?";
    $params[] = "%" . $armyNo . "%";
    $types .= "s";
}

if ($companyId !== "") {
    $where[] = "p.company_id = ?";
    $params[] = (int) $companyId;
    $types .= "i";
}

if ($rank !== "") {
    $where[] = "p.rank_name = ?";
    $params[] = $rank;
    $types .= "s";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT p.army_no, p.rank_name, p.full_name, p.trade, p.service_status, c.company_name, c.short_name
        FROM personnel p
        JOIN companies c ON c.id = p.company_id
        $whereSql
        ORDER BY $sortColumn $direction, p.full_name ASC";
$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$personnel = mysqli_fetch_all($result, MYSQLI_ASSOC);

$companies = mysqli_query($conn, "SELECT id, company_name, short_name FROM companies ORDER BY id");
$ranks = mysqli_query($conn, "SELECT DISTINCT rank_name FROM personnel ORDER BY rank_name");

if ($export === "excel") {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=nominal_roll_personnel.xls");
    echo "<table border=\"1\">";
    echo "<tr><th>Army No</th><th>Rank</th><th>Name</th><th>Company</th><th>Trade</th><th>Status</th></tr>";
    foreach ($personnel as $row) {
        echo "<tr><td>" . h($row["army_no"]) . "</td><td>" . h($row["rank_name"]) . "</td><td>" . h($row["full_name"]) . "</td><td>" . h($row["company_name"]) . "</td><td>" . h($row["trade"]) . "</td><td>" . h($row["service_status"]) . "</td></tr>";
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
$baseQuery = http_build_query($queryParams);
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
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Nominal Roll</span>
    </nav>
    <div class="panel">
        <div class="page-header" style="margin-bottom:18px;">
            <h1><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;vertical-align:-4px;margin-right:6px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Nominal Roll</h1>
            <p class="page-sub">Search, filter and view all serving personnel.</p>
        </div>
        <form method="GET" class="form-grid filters">
            <div>
                <label>Search by Army No</label>
                <input type="text" name="army_no" value="<?php echo h($armyNo); ?>" placeholder="Enter Army No">
            </div>
            <div>
                <label>Company</label>
                <select name="company_id">
                    <option value="">All Companies</option>
                    <?php while ($company = mysqli_fetch_assoc($companies)): ?>
                        <option value="<?php echo h($company["id"]); ?>" <?php echo (string) $companyId === (string) $company["id"] ? "selected" : ""; ?>>
                            <?php echo h($company["company_name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Rank</label>
                <select name="rank">
                    <option value="">All Ranks</option>
                    <?php while ($rankRow = mysqli_fetch_assoc($ranks)): ?>
                        <option value="<?php echo h($rankRow["rank_name"]); ?>" <?php echo $rank === $rankRow["rank_name"] ? "selected" : ""; ?>>
                            <?php echo h($rankRow["rank_name"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Sort By</label>
                <select name="sort">
                    <option value="name" <?php echo $sort === "name" ? "selected" : ""; ?>>Name</option>
                    <option value="army_no" <?php echo $sort === "army_no" ? "selected" : ""; ?>>Army No</option>
                    <option value="rank" <?php echo $sort === "rank" ? "selected" : ""; ?>>Rank</option>
                    <option value="company" <?php echo $sort === "company" ? "selected" : ""; ?>>Company</option>
                </select>
            </div>
            <div>
                <label>Order</label>
                <select name="direction">
                    <option value="asc" <?php echo $direction === "ASC" ? "selected" : ""; ?>>Ascending</option>
                    <option value="desc" <?php echo $direction === "DESC" ? "selected" : ""; ?>>Descending</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn" type="submit">Apply</button>
                <a class="btn secondary" href="manage_soldiers.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Personnel List</h2>
                <p class="muted"><?php echo count($personnel); ?> record(s) found</p>
            </div>
            <div class="nav-actions">
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
                        <th>Company</th>
                        <th>Trade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$personnel): ?>
                        <tr><td colspan="6">No personnel found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($personnel as $row): ?>
                        <tr>
                            <td><?php echo h($row["army_no"]); ?></td>
                            <td><?php echo h($row["rank_name"]); ?></td>
                            <td><?php echo h($row["full_name"]); ?></td>
                            <td><?php echo h($row["company_name"]); ?></td>
                            <td><?php echo h($row["trade"] ?: "-"); ?></td>
                            <td><?php echo h($row["service_status"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
