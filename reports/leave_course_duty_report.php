<?php session_start(); if (!isset($_SESSION["role"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Leave / Course / Duty Report</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Leave / Course / Duty Report</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Leave / Course / Duty Details</h2><p class="muted">Combined report for personnel unavailable due to leave, course and duty.</p></div></div>
</body></html>
