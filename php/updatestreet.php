<?php
include "db_utilities.php";

$conn = connect_db();
$conn->set_charset("utf8");

$cond = "DataUnitID = {$_POST['id']}";
$succ = db_update($conn, $_POST['table'], array('Street' => $_POST['street']), $cond);
disconnect_db($conn);
?>