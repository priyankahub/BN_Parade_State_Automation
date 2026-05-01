<?php session_start(); if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["ADJT_SA","ADMIN"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Approve Parade State</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Approve Parade State</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Approve Parade State</h2><p class="muted">Adjt / SA approval authority screen.</p></div></div>
</body></html>
