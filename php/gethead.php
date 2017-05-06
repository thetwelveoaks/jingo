<?php
include "db_utilities.php";

set_time_limit(0);
ini_set('memory_limit','4096M');

$conn = connect_db();

$ldmk_start = $_POST['ldmk_start'];
$ldmk_end = $_POST['ldmk_end'];
$ldmk_table = $_POST['ldmk_table'];
$head_table = $_POST['head_table'];
$path = $_POST['path'];

$ldmk_cols = array('LandmarkName');
$head_cols = array('BD09_LONG', 'BD09_LAT');

for($ldmk = $ldmk_start; $ldmk != $ldmk_end; ++$ldmk){
	$ldmk_cond = "LandmarkID = {$ldmk}";
	$ret = db_select($conn, $ldmk_table, $ldmk_cols, $ldmk_cond);
	$ldmk_name = $ret[0][$ldmk_cols[0]];
	$head_cond = "Street = '{$ldmk_name}'";
	$ret = db_select($conn, $head_table, $head_cols, $head_cond);
	$long_lat_filename = "{$path}{$ldmk}.csv";
	foreach ($ret as $item) {
		file_put_contents($long_lat_filename, "{$item[$head_cols[0]]},{$item[$head_cols[1]]}\n", FILE_APPEND | LOCK_EX);
	}
}

disconnect_db($conn)

?>