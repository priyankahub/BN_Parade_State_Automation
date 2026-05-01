<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Management | BN Parade State Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar">
    <div class="brand">BN Parade State Portal</div>
    <a class="btn secondary" href="dashboard.php">Dashboard</a>
</div>
<div class="container">
    <div class="panel">
        <h2>Password Management</h2>
        <p class="muted">Change password workflow will be implemented here.</p>
    </div>
</div>
</body>
</html>
