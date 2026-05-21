<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}

include("config/db.php");
include("includes/csrf.php");

$userId = (int) $_SESSION["user_id"];
$message      = "";
$messageClass = "message";
$securityQuestions = [
    "What was the name of your first school?",
    "What is your mother's maiden name?",
    "What was your childhood nickname?",
    "What is the name of your first pet?",
    "In which city were you born?",
    "What is your favorite book?",
    "What is your favorite food?",
    "What was the model of your first phone?",
    "What is the name of your best childhood friend?",
    "What is your favorite sports team?"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $action = $_POST["action"] ?? "";

    if ($action === "security_question") {
        $question = trim($_POST["security_question"] ?? "");
        $answer   = trim($_POST["security_answer"] ?? "");

        if ($question === "" || $answer === "") {
            $message = "Enter both security question and answer.";
        } elseif (!in_array($question, $securityQuestions, true)) {
            $message = "Select a valid security question.";
        } else {
            $answerPlain = strtolower($answer);
            $stmt = mysqli_prepare($conn,
                "UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssi", $question, $answerPlain, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message      = "Security question updated.";
            $messageClass = "message success";
        }
    }

    if ($action === "change_password") {
        $currentPassword = $_POST["current_password"] ?? "";
        $securityAnswer  = trim($_POST["security_answer"] ?? "");
        $newPassword     = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $stmt = mysqli_prepare($conn,
            "SELECT password, security_question, security_answer FROM users WHERE id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $validCurrent        = $user && $currentPassword === $user["password"];
        $sameAsNew           = $user && $newPassword === $user["password"];
        $validSecurityAnswer = $user && $user["security_answer"]
            && strtolower($securityAnswer) === $user["security_answer"];

        if (!$validCurrent) {
            $message = "Current password is incorrect.";
        } elseif (!$user["security_question"] || !$user["security_answer"]) {
            $message = "Set your security question before changing password.";
        } elseif (!$validSecurityAnswer) {
            $message = "Security answer is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $message = "New password must be at least 6 characters.";
        } elseif ($sameAsNew) {
            $message = "New password cannot be the same as current password.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New password and confirmation do not match.";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $newPassword, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message      = "Password changed successfully.";
            $messageClass = "message success";
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT security_question FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: ["security_question" => ""];
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("includes/head_meta.php"); ?>
    <title>Profile | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php $_tp = ''; include("includes/topbar.php"); ?>
<main id="main-content" class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="dashboard.php"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span class="breadcrumb-current">Profile</span>
    </nav>
    <div class="panel">
        <h1>Profile</h1>
        <?php if ($message): ?>
            <div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <table>
            <tr><th>Name</th><td><?php echo htmlspecialchars($_SESSION["name"], ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Username</th><td><?php echo htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Role</th><td><?php echo htmlspecialchars($_SESSION["role"], ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Recovery Question</th><td><?php echo $profile["security_question"] ? htmlspecialchars($profile["security_question"], ENT_QUOTES, 'UTF-8') : "Not set"; ?></td></tr>
        </table>
    </div>

    <div class="grid two-column">
        <div class="panel">
            <h2>Change Password</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">

                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>

                <label>Security Question</label>
                <input type="text" value="<?php echo $profile["security_question"] ? htmlspecialchars($profile["security_question"], ENT_QUOTES, 'UTF-8') : 'Set a security question first'; ?>" disabled>

                <label for="cp_security_answer">Security Answer</label>
                <input type="password" id="cp_security_answer" name="security_answer" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6" autocomplete="new-password" required>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" autocomplete="new-password" required>

                <button class="btn" type="submit">Update Password</button>
            </form>
        </div>

        <div class="panel">
            <h2>Security Question</h2>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="security_question">

                <label for="security_question">Question</label>
                <select id="security_question" name="security_question" required>
                    <option value="">Select security question</option>
                    <?php foreach ($securityQuestions as $q): ?>
                        <option value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($profile["security_question"] ?? "") === $q ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="sq_answer">Answer</label>
                <input type="password" id="sq_answer" name="security_answer" required>

                <button class="btn" type="submit">Save Question</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
