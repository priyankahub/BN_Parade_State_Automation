<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$message      = "";
$messageClass = "message";
$filterCoy    = (int)($_GET["company_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $companyId   = (int)($_POST["company_id"] ?? 0);
        $platoonName = trim($_POST["platoon_name"] ?? "");
        $sectionName = trim($_POST["section_name"] ?? "") ?: null;

        if ($companyId === 0 || $platoonName === "") {
            $message = "Company and platoon name are required.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO platoons (company_id, platoon_name, section_name) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iss", $companyId, $platoonName, $sectionName);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Platoon '$platoonName' added.";
                $messageClass = "message success";
            } else {
                $message = "Failed to add platoon.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === "toggle" && ($id = (int)($_POST["id"] ?? 0)) > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE platoons SET is_active = 1 - is_active WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Platoon status updated.";
        $messageClass = "message success";
    }
}

$companies = mysqli_fetch_all(
    mysqli_query($conn, "SELECT id, company_name FROM companies WHERE is_active = 1 ORDER BY id"),
    MYSQLI_ASSOC
);

$platoonQuery = $filterCoy > 0
    ? mysqli_prepare($conn,
        "SELECT p.id, p.platoon_name, p.section_name, p.is_active, c.company_name
         FROM platoons p JOIN companies c ON c.id = p.company_id
         WHERE p.company_id = ? ORDER BY p.company_id, p.platoon_name")
    : mysqli_prepare($conn,
        "SELECT p.id, p.platoon_name, p.section_name, p.is_active, c.company_name
         FROM platoons p JOIN companies c ON c.id = p.company_id
         ORDER BY p.company_id, p.platoon_name");

if ($filterCoy > 0) {
    mysqli_stmt_bind_param($platoonQuery, "i", $filterCoy);
}
mysqli_stmt_execute($platoonQuery);
$platoons = mysqli_fetch_all(mysqli_stmt_get_result($platoonQuery), MYSQLI_ASSOC);
mysqli_stmt_close($platoonQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Platoon / Section Master | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Platoon / Section Master</span>
    </nav>
    <div class="panel">
        <h2>Platoon / Section Master</h2>
        <p class="muted">Configure platoons and sections for each company.</p>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="grid two-column">
        <div class="panel">
            <h2>Add Platoon / Section</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <label for="company_id">Company *</label>
                <select id="company_id" name="company_id" required>
                    <option value="0">— Select company —</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>">
                            <?php echo h($c['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="platoon_name">Platoon Name *</label>
                <input type="text" id="platoon_name" name="platoon_name" required
                       placeholder="e.g. 1 Platoon">
                <label for="section_name">Section Name</label>
                <input type="text" id="section_name" name="section_name"
                       placeholder="e.g. A Section (optional)">
                <button class="btn" type="submit">Add Platoon</button>
            </form>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <h2>Platoons</h2>
                <form method="GET" style="display:flex;gap:8px;align-items:center;">
                    <select name="company_id" style="padding:6px 10px;font-size:13px;background:var(--bg-input);color:var(--text-primary);border:1px solid var(--border-mid);border-radius:4px;">
                        <option value="0">All Companies</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo $filterCoy === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo h($c['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn secondary" type="submit" style="padding:6px 12px;font-size:12px;">Filter</button>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Platoon</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($platoons)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No platoons found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($platoons as $p): ?>
                            <tr>
                                <td><?php echo h($p['company_name']); ?></td>
                                <td><?php echo h($p['platoon_name']); ?></td>
                                <td><?php echo h($p['section_name'] ?? '—'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $p['is_active'] ? 'status-present' : 'status-absent'; ?>">
                                        <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button class="btn secondary" type="submit" style="padding:5px 10px;font-size:12px;">
                                            <?php echo $p['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
