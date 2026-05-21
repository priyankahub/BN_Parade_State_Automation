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
$old          = [];

$companiesList = mysqli_fetch_all(
    mysqli_query($conn, "SELECT id, company_name FROM companies WHERE is_active = 1 ORDER BY id"),
    MYSQLI_ASSOC
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $old = [
        'name'       => trim($_POST["name"] ?? ""),
        'username'   => trim($_POST["username"] ?? ""),
        'role'       => $_POST["role"] ?? "",
        'company_id' => (int)($_POST["company_id"] ?? 0),
        'army_no'    => trim($_POST["army_no"] ?? ""),
    ];
    $password   = $_POST["password"] ?? "";
    $validRoles = ["ADMIN","ADJT_SA","CHM_CLERK","USER"];

    if ($old['name'] === "" || $old['username'] === "" || $password === "" || $old['role'] === "") {
        $message = "Name, username, password and role are required.";
    } elseif (!in_array($old['role'], $validRoles, true)) {
        $message = "Invalid role selected.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $old['username']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $message = "Username '{$old['username']}' is already taken.";
        } else {
            $cid    = $old['company_id'] > 0 ? $old['company_id'] : null;
            $armyNo = $old['army_no'] !== "" ? $old['army_no'] : null;

            $stmt = mysqli_prepare($conn,
                "INSERT INTO users (name, username, password, role, company_id, army_no, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            mysqli_stmt_bind_param($stmt, "ssssis",
                $old['name'], $old['username'], $password, $old['role'], $cid, $armyNo);

            if (mysqli_stmt_execute($stmt)) {
                $message      = "User '{$old['username']}' created successfully.";
                $messageClass = "message success";
                $old          = [];
            } else {
                $message = "Failed to create user. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Create User | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = '../'; include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <a href="manage_users.php">Manage Users</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Create User</span>
    </nav>
    <div class="panel">
        <h2>Create User</h2>
        <p class="muted">Add a new user and assign their role and company access.</p>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="panel">
        <form method="POST" autocomplete="off">
            <?php echo csrf_field(); ?>
            <div class="form-grid">
                <div>
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo h($old['name'] ?? ''); ?>"
                           placeholder="e.g. Hav Ram Kumar">
                </div>
                <div>
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo h($old['username'] ?? ''); ?>"
                           placeholder="Login username" autocomplete="new-password">
                </div>
                <div>
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password"
                           minlength="6" required autocomplete="new-password"
                           placeholder="Min 6 characters">
                </div>
                <div>
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="">— Select role —</option>
                        <?php foreach (["ADMIN","ADJT_SA","CHM_CLERK","USER"] as $r): ?>
                            <option value="<?php echo $r; ?>"
                                <?php echo ($old['role'] ?? '') === $r ? 'selected' : ''; ?>>
                                <?php echo $r; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="company_id">Company</label>
                    <select id="company_id" name="company_id">
                        <option value="0">— All / No specific company —</option>
                        <?php foreach ($companiesList as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>"
                                <?php echo ($old['company_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo h($c['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="army_no">Army No</label>
                    <input type="text" id="army_no" name="army_no"
                           value="<?php echo h($old['army_no'] ?? ''); ?>"
                           placeholder="Optional">
                </div>
            </div>
            <button class="btn" type="submit">Create User</button>
        </form>
    </div>
</main>
</body>
</html>
