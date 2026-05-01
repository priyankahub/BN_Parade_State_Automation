<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Notification Settings</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Notification Settings</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Notification Settings</h2><p class="muted">Configure alerts for approvals and manpower shortages.</p></div></div>
</body></html>
