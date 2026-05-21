<?php
session_start();
include("../config/db.php");
include("../includes/csrf.php");

$step         = "lookup";
$message      = "";
$messageClass = "message";
$username     = "";
$question     = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $action   = $_POST["action"] ?? "lookup";
    $username = trim($_POST["username"] ?? "");

    if ($action === "lookup") {
        $stmt = mysqli_prepare($conn,
            "SELECT security_question FROM users WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$user) {
            $message = "No active user found for that username.";
        } elseif (!$user["security_question"]) {
            $message = "Security question is not set for this user. Login and set it from Profile, or contact admin.";
        } else {
            $step     = "reset";
            $question = $user["security_question"];
        }
    }

    if ($action === "reset") {
        $answer          = trim($_POST["security_answer"] ?? "");
        $newPassword     = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $stmt = mysqli_prepare($conn,
            "SELECT security_question, security_answer FROM users WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$user || !$user["security_answer"]) {
            $message = "Password reset is not available for this user.";
        } elseif (strtolower($answer) !== $user["security_answer"]) {
            $step     = "reset";
            $question = $user["security_question"];
            $message  = "Security answer is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $step     = "reset";
            $question = $user["security_question"];
            $message  = "New password must be at least 6 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $step     = "reset";
            $question = $user["security_question"];
            $message  = "New password and confirmation do not match.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "ss", $newPassword, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message      = "Password reset successfully. You can now login.";
            $messageClass = "message success";
            $step         = "lookup";
            $username     = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Reset Password | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="topbar" role="banner">
    <a href="login.php" class="brand" aria-label="BN Parade State Portal — Back to Login">
        <svg class="brand-emblem" viewBox="0 0 40 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 2L4 9v14c0 10.5 7 19.5 16 23 9-3.5 16-12.5 16-23V9L20 2z" fill="#c9a227" fill-opacity=".18" stroke="#c9a227" stroke-width="1.8"/><path d="M20 10l-10 4.5v8c0 5.5 4 10 10 12 6-2 10-6.5 10-12v-8L20 10z" fill="#c9a227" fill-opacity=".25"/><text x="20" y="28" text-anchor="middle" font-family="Rajdhani,sans-serif" font-size="11" font-weight="700" fill="#c9a227" letter-spacing="1">BN</text></svg>
        <div class="brand-text">
            <span class="brand-title">BN Parade State</span>
            <span class="brand-sub">Infantry School · Mhow</span>
        </div>
    </a>
    <div class="nav-actions">
        <a class="btn secondary" href="login.php">Login</a>
    </div>
</div>
<main id="main-content" class="container">
    <div class="panel">
        <h1>Password Reset</h1>
        <?php if ($message): ?>
            <div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($step === "lookup"): ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="lookup">

                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                       autocomplete="username" required>

                <button class="btn" type="submit">Continue</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">

                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" disabled>

                <label>Security Question</label>
                <input type="text" value="<?php echo htmlspecialchars($question, ENT_QUOTES, 'UTF-8'); ?>" disabled>

                <label for="security_answer">Security Answer</label>
                <input type="password" id="security_answer" name="security_answer" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6" required>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>

                <button class="btn" type="submit">Reset Password</button>
                <a class="btn secondary" href="reset_password.php">Start Over</a>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
