<?php
include "db_utilities.php";

function fetch_next($conn, $cols, $table, $id){
	$cond = "DataUnitID = {$id}";
	$res = db_select($conn, $table, $cols, $cond);
	return count($res) > 0 ? $res[0] : null;
}

function update_tripid($conn, $table, $id, $tripid){
	$values = array("TripID" => $tripid);
	$cond = "DataUnitID = {$id}";
	$succ = db_update($conn, $table, $values, $cond);
}

function isSameTrip($last, $curr, $threshold){
	if(is_null($last) || is_null($curr)){
		return false;
	}
	return $curr['UnixEpoch'] - $last['UnixEpoch'] <= $threshold;
}

set_time_limit(0);
$conn = connect_db();

$start = $_POST['start'];
$end = $_POST['end'];
$table = $_POST['table'];
$cols = explode(",", $_POST['cols']);
$tripid = $_POST['tripid'];
$threshold = $_POST['threshold'];

$last = $curr = fetch_next($conn, $cols, $table, $start);

while ($start < $end) {
	while ($start < $end && isSameTrip($last, $curr, $threshold)) {
		update_tripid($conn, $table, $start, $tripid);
		$last = $curr;
		$curr = fetch_next($conn, $cols, $table, ++$start);
	}
	++$tripid;
	$last = $curr;
}

echo "{$tripid}\n";

disconnect_db($conn);

?>