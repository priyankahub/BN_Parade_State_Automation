<?php
session_start();
if (!isset($_SESSION["role"])) {
    header("Location: ../auth/login.php"); exit;
}

echo "PDF download module placeholder.";
?>
