<?php session_start(); if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "ADMIN") die("Access Denied"); ?>
<!DOCTYPE html><html><head><title>Create User</title><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<div class="topbar"><div class="brand">Create User</div><a class="btn secondary" href="../dashboard.php">Dashboard</a></div>
<div class="container"><div class="panel"><h2>Create User</h2><p class="muted">Admin user creation form will be implemented here.</p></div></div>
</body></html>
