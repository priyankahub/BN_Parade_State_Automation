<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}
$params = $_GET ? '?' . http_build_query($_GET) : '';
header("Location: ../adjt_sa/battalion_parade_state.php$params");
exit;
