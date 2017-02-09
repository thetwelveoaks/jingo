<?php
include "db_utilities.php";

function insertLandmark($conn, $landmark){
	$sql_insert = "INSERT INTO landmark (LandmarkID, LandmarkName, LandmarkCount)
		VALUES (NULL, \"$landmark\", 1) ON DUPLICATE KEY UPDATE LandmarkCount = LandmarkCount + 1;";
	$conn->query($sql_insert);
}

function fetchLandmark($conn, $tripid){
	$sql_select = "SELECT Street from bjtaxigps where TripID = $tripid;";
	$result = $conn->query($sql_select);
	$streets = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$streets[] = $row["Street"];
		}
	}
	return array_count_values($streets);
}

set_time_limit(0);
ini_set('memory_limit','1024M');

$conn = connect_db();
$conn->set_charset("utf8");

$start = $_POST['start'];
$end = $_POST['end'];

for($tripid = $start; $tripid != $end; ++$tripid){
	$ret = fetchLandmark($conn, $tripid);
	foreach ($ret as $key => $value) {
    	insertLandmark($conn, $key);
	}
}

disconnect_db($conn);

?>