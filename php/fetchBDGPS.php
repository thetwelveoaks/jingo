<?php
include "db_utilities.php";

$res = fetch_BDGPS($_POST['condition']);
echo json_encode($res);

?>