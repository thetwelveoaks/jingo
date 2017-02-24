<?php
include "db_utilities.php";

set_time_limit(0);
ini_set('memory_limit','2048M');

$conn = connect_db();

$street_cols = array('LandmarkName');
$streets = array();
$cond = "true order by LandmarkCount DESC LIMIT {$_POST['num']}";
$ret = db_select($conn, 'landmark', $street_cols, $cond);

$bd_cols = array('BD09_LONG', 'BD09_LAT');
$coords = array();

foreach ($ret as $item) {
	$bd_cond = "Street = '{$item[$street_cols[0]]}' LIMIT 1";
	$res = db_select($conn, 'bjtaxigps', $bd_cols, $bd_cond);
	$coords[] = array('x' => $res[0][$bd_cols[0]], 'y' => $res[0][$bd_cols[1]]);
	// echo "{$res[0][$bd_cols[0]]},{$res[0][$bd_cols[1]]}\n";
	// echo "{$item[$street_cols[0]]}\n";
}

echo json_encode($coords);



disconnect_db($conn);


?>