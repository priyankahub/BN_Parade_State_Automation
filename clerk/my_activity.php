<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>My Activity</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">My Activity</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>My Activity</h2><p class="muted">Your entries, updates and approvals trail.</p></div></div>
</body></html>
