<?php session_start(); if (!isset($_SESSION["role"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Coy Strength Summary</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Coy Strength Summary</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Coy-wise Strength Summary</h2><p class="muted">Company-wise posted, present and available strength.</p></div></div>
</body></html>
