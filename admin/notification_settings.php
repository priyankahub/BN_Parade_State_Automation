<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") {
    header("Location: ../auth/login.php"); exit;
}
include("../config/db.php");
include("../includes/csrf.php");

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$message = "";
$messageClass = "message";

// Notification settings are stored in a simple key-value config table.
// If the table doesn't exist yet, we create a sensible in-memory default.
$settings = [
    'notify_pending_approvals' => '1',
    'notify_manpower_shortage'  => '1',
    'notify_leave_approval'     => '1',
    'shortage_threshold'        => '10',
    'notify_email'              => '',
];

// Try to load from config table if it exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'notification_config'");
$tableExists = $tableCheck && mysqli_num_rows($tableCheck) > 0;

if ($tableExists) {
    $rows = mysqli_fetch_all(mysqli_query($conn, "SELECT cfg_key, cfg_value FROM notification_config"), MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $settings[$row['cfg_key']] = $row['cfg_value'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    if (!$tableExists) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notification_config (
            cfg_key   VARCHAR(80) PRIMARY KEY,
            cfg_value TEXT NOT NULL DEFAULT ''
        )");
        $tableExists = true;
    }

    $fields = ['notify_pending_approvals','notify_manpower_shortage','notify_leave_approval','shortage_threshold','notify_email'];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        if (in_array($f, ['notify_pending_approvals','notify_manpower_shortage','notify_leave_approval'])) {
            $val = isset($_POST[$f]) ? '1' : '0';
        }
        $settings[$f] = $val;
        $stmt = mysqli_prepare($conn, "INSERT INTO notification_config (cfg_key, cfg_value) VALUES (?,?) ON DUPLICATE KEY UPDATE cfg_value=VALUES(cfg_value)");
        mysqli_stmt_bind_param($stmt, "ss", $f, $val);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $message = "Notification settings saved.";
    $messageClass = "message success";
}

$_tp = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Notification Settings | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php include("../includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="../dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Notification Settings</span>
    </nav>

    <div class="panel">
        <div class="page-header" style="margin-bottom:0;">
            <h1 style="font-size:22px;">Notification Settings</h1>
            <p class="page-sub">Configure system alerts for approvals and manpower shortages.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageClass; ?>"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="grid two-column">
        <div class="panel">
            <h2>Alert Triggers</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <fieldset style="border:none;padding:0;margin:0;">
                    <legend style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Enable in-portal alerts for:</legend>

                    <label style="display:flex;align-items:center;gap:10px;min-height:auto;margin-bottom:14px;cursor:pointer;">
                        <input type="checkbox" name="notify_pending_approvals" value="1"
                               <?php echo $settings['notify_pending_approvals'] === '1' ? 'checked' : ''; ?>
                               style="width:18px;height:18px;min-height:auto;margin:0;accent-color:var(--gold);">
                        <span>Pending attendance approvals</span>
                    </label>

                    <label style="display:flex;align-items:center;gap:10px;min-height:auto;margin-bottom:14px;cursor:pointer;">
                        <input type="checkbox" name="notify_manpower_shortage" value="1"
                               <?php echo $settings['notify_manpower_shortage'] === '1' ? 'checked' : ''; ?>
                               style="width:18px;height:18px;min-height:auto;margin:0;accent-color:var(--gold);">
                        <span>Manpower shortage alerts</span>
                    </label>

                    <label style="display:flex;align-items:center;gap:10px;min-height:auto;margin-bottom:20px;cursor:pointer;">
                        <input type="checkbox" name="notify_leave_approval" value="1"
                               <?php echo $settings['notify_leave_approval'] === '1' ? 'checked' : ''; ?>
                               style="width:18px;height:18px;min-height:auto;margin:0;accent-color:var(--gold);">
                        <span>Leave approval requests</span>
                    </label>

                    <label for="shortage_threshold">Shortage alert threshold (personnel below strength)</label>
                    <input type="number" id="shortage_threshold" name="shortage_threshold" min="1" max="500"
                           value="<?php echo h($settings['shortage_threshold']); ?>">

                    <label for="notify_email">Alert email address (optional)</label>
                    <input type="email" id="notify_email" name="notify_email"
                           value="<?php echo h($settings['notify_email']); ?>"
                           placeholder="e.g. adjutant@unit.mil.in">
                </fieldset>

                <button class="btn" type="submit">Save Settings</button>
            </form>
        </div>

        <div class="panel">
            <h2>Current Configuration</h2>
            <table>
                <tr>
                    <th>Pending Approvals Alert</th>
                    <td><?php echo $settings['notify_pending_approvals'] === '1'
                        ? '<span class="status-badge status-present">Enabled</span>'
                        : '<span class="status-badge status-absent">Disabled</span>'; ?></td>
                </tr>
                <tr>
                    <th>Shortage Alert</th>
                    <td><?php echo $settings['notify_manpower_shortage'] === '1'
                        ? '<span class="status-badge status-present">Enabled</span>'
                        : '<span class="status-badge status-absent">Disabled</span>'; ?></td>
                </tr>
                <tr>
                    <th>Leave Approval Alert</th>
                    <td><?php echo $settings['notify_leave_approval'] === '1'
                        ? '<span class="status-badge status-present">Enabled</span>'
                        : '<span class="status-badge status-absent">Disabled</span>'; ?></td>
                </tr>
                <tr>
                    <th>Shortage Threshold</th>
                    <td><?php echo h($settings['shortage_threshold']); ?> personnel</td>
                </tr>
                <tr>
                    <th>Alert Email</th>
                    <td><?php echo $settings['notify_email'] ? h($settings['notify_email']) : '<span class="text-muted">Not set</span>'; ?></td>
                </tr>
            </table>
            <p class="muted" style="margin-top:16px;font-size:12px;">
                In-portal alerts appear on the dashboard pending approvals counter. Email delivery requires SMTP configuration in <code>config/mail.php</code>.
            </p>
        </div>
    </div>
</main>
</body>
</html>
