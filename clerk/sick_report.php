<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Sick Report / MH</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Sick Report / MH</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Sick Report / MH Entry</h2><p class="muted">Record sick report, MH admission, OPD, rest advised and expected return.</p></div></div>
</body></html>
