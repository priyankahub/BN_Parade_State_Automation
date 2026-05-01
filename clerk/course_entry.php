<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Course Entry</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Course Entry</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Training / Course Attendance</h2><p class="muted">Record course name, location, dates and detailed personnel.</p></div></div>
</body></html>
