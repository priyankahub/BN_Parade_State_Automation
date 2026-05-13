<?php
$conn = new mysqli('localhost','root','','bn_parade_state_db');
$res  = $conn->query('SELECT id, army_no FROM personnel ORDER BY id');
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . '|' . $row['army_no'] . "\n";
}
