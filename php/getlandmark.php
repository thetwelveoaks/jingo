<?php
include "db_utilities.php";

function insertLandmark($conn, $table, $landmark){
	$values = array("LandmarkName" => $landmark, "LandmarkCount" => 1);
	$cond = "ON DUPLICATE KEY UPDATE LandmarkCount = LandmarkCount + 1";
	$succ = db_insert($conn, $table, $values, $cond);
}

function fetchLandmark($conn, $table, $tripid){
	$cols = array("Street");
	$cond = "TripID = {$tripid} group by Street";
	$res = db_select($conn, $table, $cols, $cond);
	$streets = array();
	foreach ($res as $item) {
		foreach ($item as $key => $value) {
			$streets[] = $value;
		}
	}
	return $streets;
}

set_time_limit(0);
ini_set('memory_limit','1024M');

$conn = connect_db();

$start = $_POST['start'];
$end = $_POST['end'];
$data_table = $_POST['dtable'];
$landmark_table = $_POST['ltable'];

for($tripid = $start; $tripid != $end; ++$tripid){
	$landmarks = fetchLandmark($conn, $data_table, $tripid);
	foreach ($landmarks as $ldmk) {
    	$succ = insertLandmark($conn, $landmark_table, $ldmk);
	}
}

disconnect_db($conn);

?>