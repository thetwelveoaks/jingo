<?php
include "db_utilities.php";

$conn = connect_db();

$cond = "DataUnitID = {$_POST['id']}";
$succ = db_update($conn, $_POST['table'], array('Street' => $_POST['street']), $cond);
disconnect_db($conn);
?>