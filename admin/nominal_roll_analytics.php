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

function build_strength_pdf($rows, $total) {
    $lines = [];
    $lines[] = "BT /F1 16 Tf 40 550 Td (Nominal Roll Analytics Dashboard) Tj ET";
    $lines[] = "BT /F1 10 Tf 40 528 Td (Company Strength Summary) Tj ET";
    $lines[] = "BT /F1 9 Tf 40 506 Td (Company                         Short Name     Strength     Share) Tj ET";
    $lines[] = "BT /F1 9 Tf 40 494 Td (---------------------------------------------------------------) Tj ET";
    $y = 480;

    foreach ($rows as $row) {
        $share = $total ? round(($row["total"] / $total) * 100, 1) . "%" : "0%";
        $line = sprintf(
            "%-31s %-14s %-12s %s",
            substr($row["company_name"], 0, 31),
            substr($row["short_name"], 0, 14),
            $row["total"],
            $share
        );
        $lines[] = "BT /F1 9 Tf 40 $y Td (" . pdf_text($line) . ") Tj ET";
        $y -= 16;
    }

    $lines[] = "BT /F1 10 Tf 40 " . ($y - 10) . " Td (Total Personnel: $total) Tj ET";
    $content = implode("\n", $lines);
    $objects = [];
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
    $objects[] = "<< /Type /Page /Parent 4 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents 1 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Type /Pages /Kids [2 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Catalog /Pages 4 0 R >>";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $number = $index + 1;
        $pdf .= "$number 0 obj\n$object\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 5 0 R >>\nstartxref\n$xref\n%%EOF";

    return $pdf;
}

$result = mysqli_query($conn, "SELECT c.company_name, c.short_name, COUNT(p.id) AS total
    FROM companies c
    LEFT JOIN personnel p ON p.company_id = c.id
    GROUP BY c.id, c.company_name, c.short_name
    ORDER BY c.id");
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
$totalStrength = array_sum(array_map(function ($row) {
    return (int) $row["total"];
}, $rows));
$export = $_GET["export"] ?? "";

if ($export === "excel") {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=nominal_roll_analytics.xls");
    echo "<table border=\"1\">";
    echo "<tr><th>Company</th><th>Short Name</th><th>Strength</th><th>Share</th></tr>";
    foreach ($rows as $row) {
        $share = $totalStrength ? round(($row["total"] / $totalStrength) * 100, 1) . "%" : "0%";
        echo "<tr><td>" . h($row["company_name"]) . "</td><td>" . h($row["short_name"]) . "</td><td>" . h($row["total"]) . "</td><td>" . h($share) . "</td></tr>";
    }
    echo "<tr><th>Total</th><th></th><th>" . h($totalStrength) . "</th><th>100%</th></tr>";
    echo "</table>";
    exit;
}

if ($export === "pdf") {
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=nominal_roll_analytics.pdf");
    echo build_strength_pdf($rows, $totalStrength);
    exit;
}

$labels = array_map(function ($row) {
    return $row["short_name"];
}, $rows);
$values = array_map(function ($row) {
    return (int) $row["total"];
}, $rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Nominal Roll Analytics | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="manage_soldiers.php">Nominal Roll</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Analytics</span>
    </nav>
    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Analytics Dashboard</h2>
                <p class="muted">Nominal roll strength by company</p>
            </div>
            <div class="nav-actions">
                <span class="badge"><?php echo h($totalStrength); ?> Personnel</span>
                <a class="btn secondary" href="nominal_roll_analytics.php?export=pdf">Download PDF</a>
                <a class="btn secondary" href="nominal_roll_analytics.php?export=excel">Download Excel</a>
            </div>
        </div>
        <div class="analytics-grid">
            <canvas id="strengthChart" width="280" height="280" aria-label="Company strength donut chart"></canvas>
            <div class="chart-legend" id="strengthLegend"></div>
        </div>
    </div>

    <div class="panel">
        <h2>Company Strength</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Short Name</th>
                        <th>Strength</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $share = $totalStrength ? round(($row["total"] / $totalStrength) * 100, 1) . "%" : "0%"; ?>
                        <tr>
                            <td><?php echo h($row["company_name"]); ?></td>
                            <td><?php echo h($row["short_name"]); ?></td>
                            <td><?php echo h($row["total"]); ?></td>
                            <td><?php echo h($share); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const strengthLabels = <?php echo json_encode($labels); ?>;
const strengthValues = <?php echo json_encode($values); ?>;
const chartColors = ["#d4af37", "#3fb8af", "#ff7f50", "#7d9cff", "#8ad06f", "#e070a8"];
const canvas = document.getElementById("strengthChart");
const legend = document.getElementById("strengthLegend");
const ctx = canvas.getContext("2d");
const total = strengthValues.reduce((sum, value) => sum + value, 0);
let start = -Math.PI / 2;

ctx.clearRect(0, 0, canvas.width, canvas.height);
strengthValues.forEach((value, index) => {
    const slice = total ? (value / total) * Math.PI * 2 : 0;
    ctx.beginPath();
    ctx.moveTo(140, 140);
    ctx.arc(140, 140, 112, start, start + slice);
    ctx.closePath();
    ctx.fillStyle = chartColors[index % chartColors.length];
    ctx.fill();
    start += slice;
});

ctx.globalCompositeOperation = "destination-out";
ctx.beginPath();
ctx.arc(140, 140, 62, 0, Math.PI * 2);
ctx.fill();
ctx.globalCompositeOperation = "source-over";
ctx.fillStyle = "#f5f7fa";
ctx.font = "700 24px Segoe UI, Arial";
ctx.textAlign = "center";
ctx.fillText(total, 140, 134);
ctx.font = "12px Segoe UI, Arial";
ctx.fillText("Total", 140, 154);

legend.innerHTML = strengthLabels.map((label, index) => {
    const value = strengthValues[index];
    return `<div class="legend-item"><span class="legend-swatch" style="background:${chartColors[index % chartColors.length]}"></span><span>${label}</span><strong>${value}</strong></div>`;
}).join("");
</script>
</main>
</body>
</html>
