<?php
session_start();
include("../config/db.php");

$step = "lookup";
$message = "";
$messageClass = "message";
$username = "";
$question = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "lookup";
    $username = trim($_POST["username"] ?? "");
    $usernameEscaped = mysqli_real_escape_string($conn, $username);

    if ($action === "lookup") {
        $query = mysqli_query($conn, "SELECT security_question FROM users WHERE username='$usernameEscaped' AND is_active=1 LIMIT 1");
        $user = $query ? mysqli_fetch_assoc($query) : null;

        if (!$user) {
            $message = "No active user found for that username.";
        } elseif (!$user["security_question"]) {
            $message = "Security question is not set for this user. Login and set it from Profile, or contact admin.";
        } else {
            $step = "reset";
            $question = $user["security_question"];
        }
    }

    if ($action === "reset") {
        $answer = trim($_POST["security_answer"] ?? "");
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $query = mysqli_query($conn, "SELECT security_question, security_answer FROM users WHERE username='$usernameEscaped' AND is_active=1 LIMIT 1");
        $user = $query ? mysqli_fetch_assoc($query) : null;

        if (!$user || !$user["security_answer"]) {
            $message = "Password reset is not available for this user.";
        } elseif (!password_verify(strtolower($answer), $user["security_answer"])) {
            $step = "reset";
            $question = $user["security_question"];
            $message = "Security answer is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $step = "reset";
            $question = $user["security_question"];
            $message = "New password must be at least 6 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $step = "reset";
            $question = $user["security_question"];
            $message = "New password and confirmation do not match.";
        } else {
            $passwordHash = mysqli_real_escape_string($conn, password_hash($newPassword, PASSWORD_DEFAULT));
            mysqli_query($conn, "UPDATE users SET password='$passwordHash' WHERE username='$usernameEscaped'");
            $message = "Password reset successfully. You can now login.";
            $messageClass = "message success";
            $step = "lookup";
            $username = "";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">BN Parade State Portal</div>
    <a class="btn secondary" href="login.php">Login</a>
</div>
<div class="container">
    <div class="panel">
        <h2>Password Reset</h2>
        <?php if ($message): ?>
            <div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($step === "lookup"): ?>
            <form method="POST">
                <input type="hidden" name="action" value="lookup">

                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

                <button class="btn" type="submit">Continue</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled>

                <label>Security Question</label>
                <input type="text" value="<?php echo htmlspecialchars($question); ?>" disabled>

                <label>Security Answer</label>
                <input type="password" name="security_answer" required>

                <label>New Password</label>
                <input type="password" name="new_password" minlength="6" required>

                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="6" required>

                <button class="btn" type="submit">Reset Password</button>
                <a class="btn secondary" href="reset_password.php">Start Over</a>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
