<?php
include "db_utilities.php";

function fetch_Occupied($conn, $curr){
	$sql_select = "SELECT Occupied FROM BJTaxiGPS WHERE DataUnitID = $curr;";
	$result = $conn->query($sql_select);
	$ret = "";
	if($result->num_rows > 0){
		$ret = $result->fetch_assoc()["Occupied"];
	}
	return $ret;
}

function update_TripID($conn, $curr, $tripid){
	$sql_update = "UPDATE BJTaxiGPS SET TripID = $tripid WHERE DataUnitID = $curr;";
	$attemps = 10;
	while(!($ret = $conn->query($sql_update)) && $attemps > 0){
		echo "(" . $conn->errno . ")" . $conn->error . "<br>";
		--$attemps;
	}
	return $ret;
}

set_time_limit(0);
$start = microtime(true);

$conn = connect_db();

$ret = "0";
$curr = 39288816;
$tripid = 2389144;

// $count = 100;

do{
	while($ret == "0"){
		++$curr;
		$ret = fetch_Occupied($conn, $curr);
		// --$count;
	}
	$succ = true;
	while($succ && $ret == "1"){
		$succ = update_TripID($conn, $curr, $tripid);
		++$curr;
		$ret = fetch_Occupied($conn, $curr);
		// --$count;
	}
	++$tripid;
}while($ret != "");

disconnect_db($conn);

$time_elapsed_secs = microtime(true) - $start;

echo $time_elapsed_secs . "<br>";

?>