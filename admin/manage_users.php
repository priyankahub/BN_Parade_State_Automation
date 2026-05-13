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
    $uid    = (int)($_POST["user_id"] ?? 0);

    if ($action === "toggle" && $uid > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET is_active = 1 - is_active WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "User status updated.";
        $messageClass = "message success";
    }

    if ($action === "update_role" && $uid > 0) {
        $role = $_POST["role"] ?? "";
        $cid  = (int)($_POST["company_id"] ?? 0);
        $cid  = $cid > 0 ? $cid : null;
        if (in_array($role, ["ADMIN","ADJT_SA","CHM_CLERK","USER"], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET role = ?, company_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sii", $role, $cid, $uid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "User updated.";
            $messageClass = "message success";
        }
    }
}

$stmt = mysqli_prepare($conn,
    "SELECT u.id, u.name, u.username, u.role, u.army_no, u.is_active, c.company_name, u.company_id
     FROM users u
     LEFT JOIN companies c ON c.id = u.company_id
     ORDER BY FIELD(u.role,'ADMIN','ADJT_SA','CHM_CLERK','USER'), u.name"
);
mysqli_stmt_execute($stmt);
$users = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$companies = mysqli_fetch_all(
    mysqli_query($conn, "SELECT id, company_name FROM companies WHERE is_active = 1 ORDER BY id"),
    MYSQLI_ASSOC
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Manage Users | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Manage Users</span>
    </nav>
    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Manage Users</h2>
                <p class="muted">Edit roles, company access, and active status of system users.</p>
            </div>
            <span class="badge"><?php echo count($users); ?> Users</span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Army No</th>
                        <th>Role</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Update Role / Company</th>
                        <th>Toggle</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo h($u['name']); ?></td>
                        <td class="mono"><?php echo h($u['username']); ?></td>
                        <td style="font-size:12px;"><?php echo h($u['army_no'] ?? '—'); ?></td>
                        <td><span class="badge" data-role="<?php echo h($u['role']); ?>"><?php echo h($u['role']); ?></span></td>
                        <td><?php echo h($u['company_name'] ?? '—'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $u['is_active'] ? 'status-present' : 'status-absent'; ?>">
                                <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <select name="role" class="inline-select">
                                    <?php foreach (["ADMIN","ADJT_SA","CHM_CLERK","USER"] as $r): ?>
                                        <option value="<?php echo $r; ?>" <?php echo $u['role'] === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="company_id" class="inline-select">
                                    <option value="0">No Coy</option>
                                    <?php foreach ($companies as $c): ?>
                                        <option value="<?php echo (int)$c['id']; ?>"
                                            <?php echo (int)$u['company_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($c['company_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn" type="submit" style="padding:5px 12px;font-size:12px;">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <button class="btn secondary" type="submit" style="padding:5px 12px;font-size:12px;">
                                    <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
