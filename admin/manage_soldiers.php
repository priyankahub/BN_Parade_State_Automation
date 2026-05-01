<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Soldier Master</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Soldier Master</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Soldier Master</h2><p class="muted">Register and manage battalion personnel records.</p></div></div>
</body></html>
