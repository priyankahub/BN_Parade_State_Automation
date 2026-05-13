<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}

$rankColumns = ["Col", "Lt Col", "Maj", "Capt", "Lt", "Sub Maj", "Sub", "Nb/Sub", "Hav", "Lhav", "Nk", "Lnk", "Sep", "Jco/Clk", "OR/Clk", "JCO/ERE", "OR/ERE", "RT JCO"];
$totalColumns = ["OFFR", "JCO", "OR", "GRAND"];
$rowLabels = ["AUTH STR", "POSTED", "In Unit", "AL", "CL", "Sikh Leave", "Temp Duty", "Local Att", "Att Duty", "MH", "Course/Cadre", "AWL/OSL", "Fwd/Op Area", "Bde/Div", "Pension Drill", "Posting Out", "Other Out", "Total Absent", "Total Present"];

function values($items) {
    $values = array_fill(0, 18, 0);
    foreach ($items as $index => $value) {
        $values[$index] = $value;
    }
    return $values;
}

function row_data($values, $totals, $class = "") {
    return ["values" => $values, "totals" => $totals, "class" => $class];
}

function company_rows($overrides) {
    global $rowLabels;
    $rows = [];
    foreach ($rowLabels as $label) {
        $rows[$label] = row_data(values([]), [0, 0, 0, 0]);
    }
    foreach ($overrides as $label => $row) {
        $rows[$label] = $row;
    }
    $rows["Total Absent"]["class"] = "summary-row";
    $rows["Total Present"]["class"] = "summary-row";
    return $rows;
}

$companies = [
    "A" => [
        "title" => "A COY",
        "date" => "03-05-2026",
        "color" => "#7f1d1d",
        "leave" => ["ON LEAVE" => 11, "LEAVE DUE" => 54, "TOTAL STR" => 65, "LEAVE %" => 17],
        "rows" => company_rows([
            "POSTED" => row_data(values([2 => 1, 8 => 15, 10 => 22, 11 => 12, 12 => 15]), [1, 0, 64, 65]),
            "In Unit" => row_data(values([2 => 1, 8 => 8, 10 => 14, 11 => 6, 12 => 10]), [1, 0, 38, 39]),
            "AL" => row_data(values([8 => 3, 10 => 3, 11 => 2, 12 => 2]), [0, 0, 10, 10]),
            "CL" => row_data(values([8 => 1]), [0, 0, 1, 1]),
            "Temp Duty" => row_data(values([11 => 1]), [0, 0, 1, 1]),
            "Local Att" => row_data(values([8 => 1, 10 => 3, 11 => 2]), [0, 0, 6, 6]),
            "AWL/OSL" => row_data(values([12 => 1]), [0, 0, 1, 1]),
            "Fwd/Op Area" => row_data(values([8 => 1, 10 => 1, 11 => 1]), [0, 0, 3, 3]),
            "Posting Out" => row_data(values([10 => 1]), [0, 0, 1, 1]),
            "Total Absent" => row_data(values([8 => 6, 10 => 8, 11 => 6, 12 => 3]), [0, 0, 23, 23]),
            "Total Present" => row_data(values([2 => 1, 8 => 9, 10 => 14, 11 => 6, 12 => 12]), [1, 0, 41, 42])
        ])
    ],
    "B" => [
        "title" => "B COY",
        "date" => "03-05-2026",
        "color" => "#164e63",
        "leave" => ["ON LEAVE" => 8, "LEAVE DUE" => 58, "TOTAL STR" => 66, "LEAVE %" => 12],
        "rows" => company_rows([
            "POSTED" => row_data(values([4 => 1, 8 => 10, 10 => 14, 11 => 19, 12 => 22]), [1, 0, 65, 66]),
            "In Unit" => row_data(values([8 => 4, 10 => 6, 11 => 5, 12 => 9]), [0, 0, 24, 24]),
            "AL" => row_data(values([8 => 1, 10 => 2, 11 => 1, 12 => 1]), [0, 0, 5, 5]),
            "CL" => row_data(values([11 => 2, 12 => 1]), [0, 0, 3, 3]),
            "Temp Duty" => row_data(values([11 => 2, 12 => 2]), [0, 0, 4, 4]),
            "Course/Cadre" => row_data(values([11 => 3, 12 => 3]), [0, 0, 6, 6]),
            "Total Absent" => row_data(values([8 => 1, 10 => 2, 11 => 8, 12 => 7]), [0, 0, 18, 18]),
            "Total Present" => row_data(values([4 => 1, 8 => 9, 10 => 12, 11 => 11, 12 => 15]), [1, 0, 47, 48])
        ])
    ],
    "C" => [
        "title" => "C COY",
        "date" => "03-05-2026",
        "color" => "#166534",
        "leave" => ["ON LEAVE" => 7, "LEAVE DUE" => 48, "TOTAL STR" => 55, "LEAVE %" => 13],
        "rows" => company_rows([
            "POSTED" => row_data(values([4 => 3, 8 => 18, 10 => 7, 11 => 9, 12 => 18]), [3, 0, 52, 55]),
            "In Unit" => row_data(values([8 => 8, 10 => 6, 11 => 4, 12 => 4]), [0, 0, 22, 22]),
            "AL" => row_data(values([8 => 1, 11 => 1, 12 => 2]), [0, 0, 4, 4]),
            "CL" => row_data(values([8 => 1, 12 => 2]), [0, 0, 3, 3]),
            "Temp Duty" => row_data(values([12 => 1]), [0, 0, 1, 1]),
            "Local Att" => row_data(values([4 => 1, 8 => 2, 10 => 1, 11 => 1, 12 => 2]), [1, 0, 6, 7]),
            "Att Duty" => row_data(values([11 => 2, 12 => 1]), [0, 0, 3, 3]),
            "Course/Cadre" => row_data(values([12 => 1]), [0, 0, 1, 1]),
            "Fwd/Op Area" => row_data(values([4 => 2, 11 => 1]), [2, 0, 1, 3]),
            "Bde/Div" => row_data(values([12 => 1]), [0, 0, 1, 1]),
            "Pension Drill" => row_data(values([8 => 4, 12 => 4]), [0, 0, 8, 8]),
            "Total Absent" => row_data(values([4 => 3, 8 => 8, 10 => 1, 11 => 5, 12 => 14]), [3, 0, 28, 31]),
            "Total Present" => row_data(values([8 => 10, 10 => 6, 11 => 4, 12 => 4]), [0, 0, 24, 24])
        ])
    ],
    "D" => [
        "title" => "D COY",
        "date" => "03-05-2026",
        "color" => "#854d0e",
        "leave" => ["ON LEAVE" => 3, "LEAVE DUE" => 42, "TOTAL STR" => 45, "LEAVE %" => 7],
        "rows" => company_rows([
            "POSTED" => row_data(values([8 => 13, 10 => 13, 11 => 15, 12 => 4]), [0, 0, 45, 45]),
            "In Unit" => row_data(values([8 => 10, 10 => 9, 11 => 12, 12 => 3]), [0, 0, 34, 34]),
            "AL" => row_data(values([8 => 1, 11 => 2]), [0, 0, 3, 3]),
            "Att Duty" => row_data(values([8 => 1, 10 => 2, 11 => 1]), [0, 0, 4, 4]),
            "Fwd/Op Area" => row_data(values([8 => 1, 10 => 1, 12 => 1]), [0, 0, 3, 3]),
            "Pension Drill" => row_data(values([10 => 1]), [0, 0, 1, 1]),
            "Total Absent" => row_data(values([8 => 3, 10 => 4, 11 => 3, 12 => 1]), [0, 0, 11, 11]),
            "Total Present" => row_data(values([8 => 10, 10 => 9, 11 => 12, 12 => 3]), [0, 0, 34, 34])
        ])
    ],
    "SP" => [
        "title" => "SP COY",
        "date" => "03-05-2026",
        "color" => "#7f3f46",
        "leave" => ["ON LEAVE" => 6, "LEAVE DUE" => 83, "TOTAL STR" => 89, "LEAVE %" => 7],
        "rows" => company_rows([
            "POSTED" => row_data(values([2 => 3, 3 => 3, 8 => 21, 10 => 16, 11 => 23, 12 => 23]), [6, 0, 83, 89]),
            "In Unit" => row_data(values([2 => 2, 8 => 13, 10 => 7, 11 => 15, 12 => 14]), [2, 0, 49, 51]),
            "AL" => row_data(values([3 => 1, 12 => 1]), [1, 0, 1, 2]),
            "CL" => row_data(values([3 => 1, 8 => 1, 10 => 1, 12 => 1]), [1, 0, 3, 4]),
            "Temp Duty" => row_data(values([3 => 1]), [1, 0, 0, 1]),
            "Local Att" => row_data(values([8 => 1]), [0, 0, 1, 1]),
            "Att Duty" => row_data(values([8 => 1, 10 => 3]), [0, 0, 4, 4]),
            "Course/Cadre" => row_data(values([12 => 1]), [0, 0, 1, 1]),
            "Fwd/Op Area" => row_data(values([10 => 1, 11 => 1]), [0, 0, 2, 2]),
            "Bde/Div" => row_data(values([10 => 1, 11 => 1]), [0, 0, 2, 2]),
            "Pension Drill" => row_data(values([8 => 2, 10 => 1, 11 => 3, 12 => 2]), [0, 0, 8, 8]),
            "Posting Out" => row_data(values([8 => 1, 12 => 1]), [0, 0, 2, 2]),
            "Total Absent" => row_data(values([3 => 3, 8 => 6, 10 => 7, 11 => 5, 12 => 6]), [3, 0, 24, 27]),
            "Total Present" => row_data(values([2 => 3, 8 => 15, 10 => 9, 11 => 18, 12 => 17]), [3, 0, 59, 62])
        ])
    ],
    "HQ" => [
        "title" => "HQ COY",
        "date" => "03-05-2026",
        "color" => "#374151",
        "totalColor" => "#3f6212",
        "leave" => ["ON LEAVE" => 9, "LEAVE DUE" => 91, "TOTAL STR" => 100, "LEAVE %" => 9],
        "rows" => company_rows([
            "POSTED" => row_data(values([3 => 2, 8 => 24, 10 => 29, 11 => 24, 12 => 21]), [2, 0, 98, 100]),
            "In Unit" => row_data(values([8 => 20, 10 => 24, 11 => 18, 12 => 19]), [0, 0, 81, 81]),
            "AL" => row_data(values([3 => 2, 8 => 2, 10 => 2, 11 => 2, 12 => 1]), [2, 0, 7, 9]),
            "Att Duty" => row_data(values([10 => 1, 11 => 1, 12 => 1]), [0, 0, 3, 3]),
            "Total Absent" => row_data(values([3 => 2, 8 => 2, 10 => 3, 11 => 3, 12 => 2]), [2, 0, 10, 12]),
            "Total Present" => row_data(values([8 => 22, 10 => 26, 11 => 21, 12 => 19]), [0, 0, 88, 88])
        ])
    ]
];

$selectedCompany = $_GET["company"] ?? "all";
$reports = $selectedCompany !== "all" && isset($companies[$selectedCompany]) ? [$selectedCompany => $companies[$selectedCompany]] : $companies;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Daily Parade State</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container wide-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="#">Reports</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Daily Parade State</span>
    </nav>
    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Company-wise Daily Parade State</h2>
                <p class="muted">Parade state and leave percentage by company.</p>
            </div>
            <form method="GET" class="inline-filter">
                <label>Company</label>
                <select name="company" onchange="this.form.submit()">
                    <option value="all" <?php echo $selectedCompany === "all" ? "selected" : ""; ?>>All Companies</option>
                    <?php foreach ($companies as $key => $company): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $selectedCompany === $key ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($company["title"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php foreach ($reports as $report): ?>
        <?php $totalColor = $report["totalColor"] ?? "#0f766e"; ?>
        <div class="company-report">
            <div class="company-title-row">
                <div class="company-title" style="background: <?php echo htmlspecialchars($report["color"]); ?>;">
                    DAILY PARADE STATE OF XYZ BN AS ON : <?php echo htmlspecialchars($report["title"]); ?>
                </div>
                <div class="company-date" style="background: <?php echo htmlspecialchars($report["dateColor"] ?? "#122944"); ?>;">
                    DATE: <span><?php echo htmlspecialchars($report["date"]); ?></span>
                </div>
            </div>

            <div class="company-report-grid">
                <div class="table-wrap">
                    <table class="parade-state-table company-state-table" style="--total-color: <?php echo htmlspecialchars($totalColor); ?>;">
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
                                <?php foreach ($rankColumns as $column): ?>
                                    <?php if ($column !== "RT JCO"): ?>
                                        <th><?php echo htmlspecialchars($column); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php foreach ($totalColumns as $column): ?>
                                    <th><?php echo htmlspecialchars($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report["rows"] as $label => $row): ?>
                                <tr class="<?php echo htmlspecialchars($row["class"] ?? ""); ?>">
                                    <th class="row-label"><?php echo htmlspecialchars($label); ?></th>
                                    <?php foreach ($row["values"] as $value): ?>
                                        <td><?php echo htmlspecialchars((string) $value); ?></td>
                                    <?php endforeach; ?>
                                    <?php foreach ($row["totals"] as $value): ?>
                                        <td class="total-cell"><?php echo htmlspecialchars((string) $value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <table class="leave-table">
                    <thead>
                        <tr><th colspan="2"><?php echo htmlspecialchars($report["title"]); ?> LEAVE %</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["leave"] as $label => $value): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($label); ?></th>
                                <td><?php echo htmlspecialchars((string) $value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</main>
</body>
</html>
