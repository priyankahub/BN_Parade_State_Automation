<?php session_start(); if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["ADJT_SA","ADMIN"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Manpower Shortages</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Manpower Shortages</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Priority Manpower Shortages</h2><p class="muted">Urgent shortage marking and monitoring will be implemented here.</p></div></div>
</body></html>
