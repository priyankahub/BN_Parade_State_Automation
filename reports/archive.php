<?php session_start(); if (!isset($_SESSION["role"])) die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Archive</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Archive</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Past Parade States</h2><p class="muted">Archived parade states for reference.</p></div></div>
</body></html>
