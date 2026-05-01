<?php
session_start();
if (!isset($_SESSION["role"])) {
    die("Access Denied");
}

header("Content-Type: text/plain");
echo "Excel download module placeholder.";
?>
