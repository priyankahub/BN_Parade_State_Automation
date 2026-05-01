<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "USER") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>View Parade State</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">View Parade State</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>View Parade State</h2><p class="muted">Read-only daily parade state view.</p></div></div>
</body></html>
