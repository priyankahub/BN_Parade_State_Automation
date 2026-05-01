<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Duty Roster</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Duty Roster</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Duty Roster</h2><p class="muted">Record guards, sentries, QRT, office duties and special tasks.</p></div></div>
</body></html>
