<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}

include("config/db.php");

$userId = (int) $_SESSION["user_id"];
$message = "";
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
    $action = $_POST["action"] ?? "";

    if ($action === "security_question") {
        $question = trim($_POST["security_question"] ?? "");
        $answer = trim($_POST["security_answer"] ?? "");

        if ($question === "" || $answer === "") {
            $message = "Enter both security question and answer.";
        } elseif (!in_array($question, $securityQuestions, true)) {
            $message = "Select a valid security question.";
        } else {
            $questionEscaped = mysqli_real_escape_string($conn, $question);
            $answerHash = password_hash(strtolower($answer), PASSWORD_DEFAULT);
            $answerEscaped = mysqli_real_escape_string($conn, $answerHash);

            mysqli_query($conn, "UPDATE users SET security_question='$questionEscaped', security_answer='$answerEscaped' WHERE id=$userId");
            $message = "Security question updated.";
            $messageClass = "message success";
        }
    }

    if ($action === "change_password") {
        $currentPassword = $_POST["current_password"] ?? "";
        $newPassword = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";

        $query = mysqli_query($conn, "SELECT password FROM users WHERE id=$userId LIMIT 1");
        $user = $query ? mysqli_fetch_assoc($query) : null;
        $validCurrent = $user && (password_verify($currentPassword, $user["password"]) || $currentPassword === $user["password"]);

        if (!$validCurrent) {
            $message = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 6) {
            $message = "New password must be at least 6 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "New password and confirmation do not match.";
        } else {
            $passwordHash = mysqli_real_escape_string($conn, password_hash($newPassword, PASSWORD_DEFAULT));
            mysqli_query($conn, "UPDATE users SET password='$passwordHash' WHERE id=$userId");
            $message = "Password changed successfully.";
            $messageClass = "message success";
        }
    }
}

$profileQuery = mysqli_query($conn, "SELECT security_question FROM users WHERE id=$userId LIMIT 1");
$profile = $profileQuery ? mysqli_fetch_assoc($profileQuery) : ["security_question" => ""];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">BN Parade State Portal</div>
    <a class="btn secondary" href="dashboard.php">Dashboard</a>
</div>
<div class="container">
    <div class="panel">
        <h2>Profile</h2>
        <?php if ($message): ?>
            <div class="<?php echo $messageClass; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <table>
            <tr><th>Name</th><td><?php echo htmlspecialchars($_SESSION["name"]); ?></td></tr>
            <tr><th>Username</th><td><?php echo htmlspecialchars($_SESSION["username"]); ?></td></tr>
            <tr><th>Role</th><td><?php echo htmlspecialchars($_SESSION["role"]); ?></td></tr>
            <tr><th>Recovery Question</th><td><?php echo $profile["security_question"] ? htmlspecialchars($profile["security_question"]) : "Not set"; ?></td></tr>
        </table>
    </div>

    <div class="grid two-column">
        <div class="panel">
            <h2>Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <label>Current Password</label>
                <input type="password" name="current_password" required>

                <label>New Password</label>
                <input type="password" name="new_password" minlength="6" required>

                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="6" required>

                <button class="btn" type="submit">Update Password</button>
            </form>
        </div>

        <div class="panel">
            <h2>Security Question</h2>
            <form method="POST">
                <input type="hidden" name="action" value="security_question">

                <label>Question</label>
                <select name="security_question" required>
                    <option value="">Select security question</option>
                    <?php foreach ($securityQuestions as $securityQuestion): ?>
                        <option value="<?php echo htmlspecialchars($securityQuestion); ?>" <?php echo ($profile["security_question"] ?? "") === $securityQuestion ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($securityQuestion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Answer</label>
                <input type="password" name="security_answer" required>

                <button class="btn" type="submit">Save Question</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
