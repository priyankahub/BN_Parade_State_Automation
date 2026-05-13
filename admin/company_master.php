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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $name  = trim($_POST["company_name"] ?? "");
        $short = strtoupper(trim($_POST["short_name"] ?? ""));

        if ($name === "" || $short === "") {
            $message = "Both company name and short name are required.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO companies (company_name, short_name) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ss", $name, $short);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Company '$name' added.";
                $messageClass = "message success";
            } else {
                $message = "Failed to add company.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === "toggle" && ($id = (int)($_POST["id"] ?? 0)) > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE companies SET is_active = 1 - is_active WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Company status updated.";
        $messageClass = "message success";
    }
}

$companies = mysqli_fetch_all(
    mysqli_query($conn, "SELECT id, company_name, short_name, is_active FROM companies ORDER BY id"),
    MYSQLI_ASSOC
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Company Master | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Company Master</span>
    </nav>
    <div class="panel">
        <h2>Company Master</h2>
        <p class="muted">Create and manage battalion companies.</p>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="grid two-column">
        <div class="panel">
            <h2>Add Company</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <label for="company_name">Company Name *</label>
                <input type="text" id="company_name" name="company_name" required
                       placeholder="e.g. Alpha Company">
                <label for="short_name">Short Name *</label>
                <input type="text" id="short_name" name="short_name" required
                       placeholder="e.g. A COY" maxlength="20">
                <button class="btn" type="submit">Add Company</button>
            </form>
        </div>

        <div class="panel">
            <h2>Existing Companies</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>Short Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($companies as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['id']; ?></td>
                            <td><?php echo h($c['company_name']); ?></td>
                            <td><span class="badge"><?php echo h($c['short_name']); ?></span></td>
                            <td>
                                <span class="status-badge <?php echo $c['is_active'] ? 'status-present' : 'status-absent'; ?>">
                                    <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                    <button class="btn secondary" type="submit" style="padding:5px 10px;font-size:12px;">
                                        <?php echo $c['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
