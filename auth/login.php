<?php
session_start();
include("../config/db.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = $_POST["password"];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND is_active=1 LIMIT 1");

    if ($query && mysqli_num_rows($query) === 1) {
        $user = mysqli_fetch_assoc($query);

        $validPassword = password_verify($password, $user["password"]) || $password === $user["password"];

        if ($validPassword) {
            if (!password_get_info($user["password"])["algo"]) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $userId = (int) $user["id"];
                mysqli_query($conn, "UPDATE users SET password='$newHash' WHERE id=$userId");
            }

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["company_id"] = $user["company_id"];

            header("Location: ../dashboard.php");
            exit;
        }
    }

    $message = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <h2>BN Parade State Portal</h2>
        <p class="muted">Infantry Battalion parade state automation</p>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button class="btn" type="submit">Login</button>
        </form>
        <p class="muted"><a href="reset_password.php">Forgot password?</a></p>
    </div>
</div>
</body>
</html>
