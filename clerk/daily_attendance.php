<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "CHM_CLERK") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Daily Attendance</title><link rel="stylesheet" href="../assets/css/style.css"><script src="../assets/js/script.js" defer></script></head><body>
<div class="topbar"><div class="brand">Daily Attendance</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Daily Attendance Entry</h2><div class="form-grid"><div><label>Date</label><input type="date" data-default-today></div><div><label>Status</label><select><option>Present</option><option>Absent</option><option>Leave</option><option>Course</option><option>Sick Report</option><option>MH</option><option>TD</option><option>Duty</option></select></div></div></div></div>
</body></html>
