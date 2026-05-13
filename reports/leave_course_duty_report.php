<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}

include("../config/db.php");

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function pdf_text($value) {
    return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], (string) $value);
}

function build_lcd_pdf($rows) {
    $objects = [];
    $pages = [];
    $chunks = array_chunk($rows, 34);
    if (!$chunks) {
        $chunks = [[]];
    }

    foreach ($chunks as $chunk) {
        $lines = [];
        $lines[] = "BT /F1 16 Tf 40 550 Td (Leave / Course / Duty Report) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 528 Td (Ser  Army No      Rank  Name                         Coy  Location) Tj ET";
        $lines[] = "BT /F1 9 Tf 40 516 Td (--------------------------------------------------------------------) Tj ET";
        $y = 502;

        foreach ($chunk as $row) {
            $line = sprintf(
                "%-4s %-12s %-5s %-28s %-4s %s",
                $row["ser_no"],
                substr($row["army_no"], 0, 12),
                substr($row["rank_name"], 0, 5),
                substr($row["full_name"], 0, 28),
                substr($row["company_code"], 0, 4),
                substr($row["location"], 0, 18)
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
        $number = $index + 1;
        $pdf .= "$number 0 obj\n$object\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root $catalogId 0 R >>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}

$companyCodes = [
    "HQ Company" => "HQ",
    "A Company" => "A",
    "B Company" => "B",
    "C Company" => "C",
    "D Company" => "D",
    "SP Company" => "SP"
];

$locationMap = [
    "17027948A" => "AL", "2793160H" => "Temp Duty", "2793291N" => "AWL", "2793976M" => "CL", "2794183W" => "Posting Out",
    "2795176M" => "AL", "2802654W" => "AL", "2804124A" => "AL", "2804229K" => "AL", "2804236A" => "AL",
    "2810379W" => "AL", "2810670H" => "AL", "2810739F" => "AL", "2811470B" => "AL",
    "2795922K" => "Temp Duty", "2799411X" => "Temp Duty", "2800191N" => "Temp Duty", "2802262H" => "Temp Duty",
    "2802334F" => "AL", "2803159K" => "AL", "2804378X" => "AL", "2810414B" => "AL", "2811140A" => "AL",
    "2811542X" => "TD", "2813133H" => "TD", "2813204B" => "TD", "2813234W" => "TD", "2813284F" => "TD",
    "2813386X" => "TD", "2813530M" => "TD", "2813533P" => "TD", "2813551X" => "TD", "2813569N" => "TD", "2814497F" => "TD",
    "2804434L" => "Att Duty", "2805464P" => "Bde/Div", "2805878H" => "Pension Drill", "2806219B" => "Local Att",
    "2810153L" => "AL", "2811251X" => "AL", "2811337W" => "AL", "2811539X" => "AL", "2815517W" => "Pension Drill",
    "2815588X" => "Pension Drill", "2815798X" => "Pension Drill", "2815901X" => "Pension Drill",
    "2804527A" => "AL", "2804920H" => "AL", "2805360N" => "Pension Drill", "2810386M" => "AL",
    "2815569M" => "Att Duty", "2815820X" => "Att Duty", "2815975M" => "Att Duty", "2816074M" => "Att Duty",
    "2808917A" => "Fwd/Op Area", "2811451N" => "Fwd/Op Area", "2792603M" => "Fwd/Op Area",
    "2796133K" => "Posting Out", "2797397X" => "Att Duty", "2797852P" => "Bde/Div", "2798046P" => "CL",
    "2798724K" => "CL", "2798991L" => "CL", "2801516H" => "Att Duty", "2804846M" => "Att Duty",
    "2805214N" => "Posting Out", "2806282N" => "Pension Drill", "2806435A" => "Pension Drill",
    "2806635H" => "Local Att", "2807509H" => "Pension Drill", "2808457K" => "Pension Drill",
    "2808541K" => "Course/Cadre", "2808625X" => "Pension Drill", "2808643A" => "Att Duty",
    "2808732B" => "Pension Drill", "2808926F" => "Pension Drill", "2809271M" => "Pension Drill",
    "2802117L" => "Att Duty", "2804503W" => "Att Duty", "2805215W" => "AL", "2805411W" => "Att Duty",
    "2809736F" => "AL", "2809755M" => "AL", "2809860H" => "AL", "2810364N" => "AL", "2810392A" => "AL",
    "2810418K" => "AL", "2810455X" => "AL", "2811030L" => "AL", "2819413M" => "In Unit"
];

$company = $_GET["company"] ?? "";
$rank = $_GET["rank"] ?? "";
$location = $_GET["location"] ?? "";
$armyNo = trim($_GET["army_no"] ?? "");
$sort = $_GET["sort"] ?? "company";
$direction = strtolower($_GET["direction"] ?? "desc") === "desc" ? "DESC" : "ASC";
$export = $_GET["export"] ?? "";

$sortColumns = [
    "ser_no" => "p.id",
    "army_no" => "p.army_no",
    "rank" => "p.rank_name",
    "name" => "p.full_name",
    "company" => "c.company_name"
];
$sortColumn = $sortColumns[$sort] ?? $sortColumns["company"];
$where = [];
$params = [];
$types = "";

if ($company !== "") {
    $where[] = "c.short_name = ?";
    $params[] = $company === "SP" ? "SP" : ($company === "HQ" ? "HQ" : $company . " Coy");
    $types .= "s";
}
if ($rank !== "") {
    $where[] = "p.rank_name = ?";
    $params[] = $rank;
    $types .= "s";
}
if ($armyNo !== "") {
    $where[] = "p.army_no LIKE ?";
    $params[] = "%" . $armyNo . "%";
    $types .= "s";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT p.id, p.army_no, p.rank_name, p.full_name, c.company_name, c.short_name
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

$rows = [];
$ser = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $companyCode = $companyCodes[$row["company_name"]] ?? str_replace(" Coy", "", $row["short_name"]);
    $row["ser_no"] = $ser++;
    $row["company_code"] = $companyCode;
    $row["location"] = $locationMap[$row["army_no"]] ?? "In Unit";
    $rows[] = $row;
}

if ($location !== "") {
    $rows = array_values(array_filter($rows, function ($row) use ($location) {
        return $row["location"] === $location;
    }));
    foreach ($rows as $index => $row) {
        $rows[$index]["ser_no"] = $index + 1;
    }
}

if ($sort === "location") {
    usort($rows, function ($a, $b) use ($direction) {
        $result = strcmp($a["location"], $b["location"]);
        return $direction === "DESC" ? -$result : $result;
    });
    foreach ($rows as $index => $row) {
        $rows[$index]["ser_no"] = $index + 1;
    }
}

$locations = array_values(array_unique(array_merge(["AL", "Att Duty", "AWL", "Bde/Div", "CL", "Course/Cadre", "Fwd/Op Area", "In Unit", "Local Att", "MH", "Pension Drill", "Posting Out", "TD", "Temp Duty"], array_values($locationMap))));
sort($locations);
$ranks = mysqli_query($conn, "SELECT DISTINCT rank_name FROM personnel ORDER BY rank_name");

if ($export === "excel") {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=leave_course_duty_report.xls");
    echo "<table border=\"1\"><tr><th>SER NO</th><th>Army NO</th><th>Rank</th><th>Name</th><th>Coy</th><th>Location</th></tr>";
    foreach ($rows as $row) {
        echo "<tr><td>" . h($row["ser_no"]) . "</td><td>" . h($row["army_no"]) . "</td><td>" . h($row["rank_name"]) . "</td><td>" . h($row["full_name"]) . "</td><td>" . h($row["company_code"]) . "</td><td>" . h($row["location"]) . "</td></tr>";
    }
    echo "</table>";
    exit;
}

if ($export === "pdf") {
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=leave_course_duty_report.pdf");
    echo build_lcd_pdf($rows);
    exit;
}

$queryParams = $_GET;
unset($queryParams["export"]);
$exportPrefix = http_build_query($queryParams);
$exportPrefix = $exportPrefix ? $exportPrefix . "&" : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Leave / Course / Duty Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="#">Reports</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Leave / Course / Duty</span>
    </nav>
    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Company Nominal Roll Locations</h2>
                <p class="muted">Leave, course, duty and in-unit details by company.</p>
            </div>
            <div class="nav-actions">
                <a class="btn secondary" href="leave_course_duty_report.php?<?php echo h($exportPrefix); ?>export=pdf">Download PDF</a>
                <a class="btn secondary" href="leave_course_duty_report.php?<?php echo h($exportPrefix); ?>export=excel">Download Excel</a>
            </div>
        </div>

        <form method="GET" class="form-grid filters">
            <div>
                <label>Company</label>
                <select name="company">
                    <option value="">All Companies</option>
                    <?php foreach (["A", "B", "C", "D", "SP", "HQ"] as $code): ?>
                        <option value="<?php echo h($code); ?>" <?php echo $company === $code ? "selected" : ""; ?>><?php echo h($code); ?> Coy</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Location</label>
                <select name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo h($loc); ?>" <?php echo $location === $loc ? "selected" : ""; ?>><?php echo h($loc); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Rank</label>
                <select name="rank">
                    <option value="">All Ranks</option>
                    <?php while ($rankRow = mysqli_fetch_assoc($ranks)): ?>
                        <option value="<?php echo h($rankRow["rank_name"]); ?>" <?php echo $rank === $rankRow["rank_name"] ? "selected" : ""; ?>><?php echo h($rankRow["rank_name"]); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Army No</label>
                <input type="text" name="army_no" value="<?php echo h($armyNo); ?>" placeholder="Search Army No">
            </div>
            <div>
                <label>Sort By</label>
                <select name="sort">
                    <option value="company" <?php echo $sort === "company" ? "selected" : ""; ?>>Company</option>
                    <option value="name" <?php echo $sort === "name" ? "selected" : ""; ?>>Name</option>
                    <option value="army_no" <?php echo $sort === "army_no" ? "selected" : ""; ?>>Army No</option>
                    <option value="rank" <?php echo $sort === "rank" ? "selected" : ""; ?>>Rank</option>
                    <option value="location" <?php echo $sort === "location" ? "selected" : ""; ?>>Location</option>
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
                <a class="btn secondary" href="leave_course_duty_report.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2><?php echo $company ? h($company . " Coy Nominal Roll") : "All Company Nominal Roll"; ?></h2>
        <p class="muted"><?php echo count($rows); ?> record(s) found</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SER NO</th>
                        <th>Army NO</th>
                        <th>Rank</th>
                        <th>Name</th>
                        <th>Coy</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo h($row["ser_no"]); ?></td>
                            <td><?php echo h($row["army_no"]); ?></td>
                            <td><?php echo h($row["rank_name"]); ?></td>
                            <td><?php echo h($row["full_name"]); ?></td>
                            <td><?php echo h($row["company_code"]); ?></td>
                            <td><?php echo h($row["location"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
