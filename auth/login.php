<?php
session_start();
include("../config/db.php");
include("../includes/csrf.php");

define('MAX_ATTEMPTS', 5);
define('LOCKOUT_SECS', 900); // 15 minutes

$message = "";

// Simple file-based rate limiter (works on XAMPP without extra extensions)
function rate_key(string $ip, string $user): string {
    return sys_get_temp_dir() . '/bnps_login_' . md5($ip . '|' . strtolower($user)) . '.json';
}

function is_locked(string $ip, string $user): int {
    $f = rate_key($ip, $user);
    if (!file_exists($f)) return 0;
    $d = json_decode(file_get_contents($f), true);
    if (($d['locked_until'] ?? 0) > time()) {
        return (int) ceil(($d['locked_until'] - time()) / 60);
    }
    return 0;
}

function record_failure(string $ip, string $user): void {
    $f = rate_key($ip, $user);
    $d = file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    if (($d['locked_until'] ?? 0) < time()) {
        $d['attempts'] = ($d['attempts'] ?? 0) + 1;
    }
    if (($d['attempts'] ?? 0) >= MAX_ATTEMPTS) {
        $d['locked_until'] = time() + LOCKOUT_SECS;
    }
    file_put_contents($f, json_encode($d), LOCK_EX);
}

function clear_failures(string $ip, string $user): void {
    $f = rate_key($ip, $user);
    if (file_exists($f)) @unlink($f);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_verify();

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $ip       = $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";

    $wait = is_locked($ip, $username);
    if ($wait > 0) {
        $message = "Too many failed attempts. Try again in {$wait} minute(s).";
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id, name, username, role, company_id, password
             FROM users WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && $password === $user["password"]) {
            clear_failures($ip, $username);
            session_regenerate_id(true);

            $_SESSION["user_id"]    = $user["id"];
            $_SESSION["name"]       = $user["name"];
            $_SESSION["username"]   = $user["username"];
            $_SESSION["role"]       = $user["role"];
            $_SESSION["company_id"] = $user["company_id"];

            header("Location: ../dashboard.php");
            exit;
        }

        record_failure($ip, $username);
        $message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../includes/head_meta.php"); ?>
    <title>Login | BN Parade State Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="login-emblem">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M30 3L6 15V33C6 45.7 16.8 56.4 30 58.5C43.2 56.4 54 45.7 54 33V15L30 3Z" fill="rgba(201,162,39,0.12)" stroke="#c9a227" stroke-width="2"/>
                <path d="M30 12L14 20V31C14 39.8 21.2 47.5 30 49.2C38.8 47.5 46 39.8 46 31V20L30 12Z" fill="rgba(201,162,39,0.08)" stroke="#c9a227" stroke-width="1.5"/>
                <circle cx="30" cy="30" r="8" fill="rgba(201,162,39,0.2)" stroke="#c9a227" stroke-width="1.5"/>
                <path d="M30 22V38M22 30H38" stroke="#c9a227" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <h2>BN Parade State Portal</h2>
        <p class="muted">The Infantry School · Mhow · Authorized Access Only</p>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required placeholder="Enter your username">

            <label for="password">Password</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" autocomplete="current-password" required placeholder="Enter your password" style="padding-right:42px;width:100%;box-sizing:border-box;">
                <button type="button" onclick="var f=document.getElementById('password');var e=document.getElementById('eye-icon');if(f.type==='password'){f.type='text';e.innerHTML='&#128065;&#65038;';}else{f.type='password';e.innerHTML='&#128065;';}" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:#8fa8c6;padding:0;line-height:1;">
                    <span id="eye-icon">&#128065;</span>
                </button>
            </div>

            <button class="btn" type="submit" style="width:100%;justify-content:center;padding:12px;margin-top:12px;">Sign In</button>
        </form>
        <p class="muted" style="text-align:center;margin-top:16px;"><a href="reset_password.php" style="color:var(--gold-muted)">Forgot password?</a></p>
        <div class="login-footer-note">RESTRICTED SYSTEM — Authorised Personnel Only</div>
    </div>
</div>
</body>
</html>
