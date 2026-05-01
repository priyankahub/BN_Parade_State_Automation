<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "USER") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>View Reports</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">View Reports</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>View Reports</h2><p class="muted">Read-only reports area for authorized staff.</p></div></div>
</body></html>
