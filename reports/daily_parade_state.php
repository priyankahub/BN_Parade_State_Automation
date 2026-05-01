<?php session_start(); if (!isset($_SESSION["role"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Daily Parade State</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Daily Parade State</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Daily Parade State Report</h2><p class="muted">Date-wise battalion parade state report.</p></div></div>
</body></html>
