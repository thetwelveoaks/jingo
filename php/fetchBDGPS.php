<?php
include "db_utilities.php";

$conn = connect_db();
$cols = array("DataUnitID", "BD09_LONG", "BD09_LAT");
$res = db_select($conn, $cols, $_POST['table'], $_POST['cond']);

echo json_encode($res);

disconnect_db($conn);
?>