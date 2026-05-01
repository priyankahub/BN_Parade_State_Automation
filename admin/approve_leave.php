<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Approve Leave</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Approve Leave</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Approve Leave</h2><p class="muted">Approve or reject leave records.</p></div></div>
</body></html>
