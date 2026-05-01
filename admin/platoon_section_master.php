<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Platoon / Section Master</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Platoon / Section Master</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Platoon / Section Master</h2><p class="muted">Configure platoons and sections for each company.</p></div></div>
</body></html>
