<?php session_start(); if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["ADJT_SA","ADMIN"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Duty Overview</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Duty Overview</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Duty Overview</h2><p class="muted">View guards, sentries, QRT, office duties and special tasks.</p></div></div>
</body></html>
