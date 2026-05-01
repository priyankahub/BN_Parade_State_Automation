<?php session_start(); if (!isset($_SESSION["role"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Attendance Analytics</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Attendance Analytics</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Attendance Analytics</h2><p class="muted">Attendance trends, company comparisons and shortage patterns.</p></div></div>
</body></html>
