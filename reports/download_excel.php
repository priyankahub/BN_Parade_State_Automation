<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}

header("Content-Type: text/plain");
echo "Excel download module placeholder.";
?>
