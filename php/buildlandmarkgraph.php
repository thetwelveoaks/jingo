<?php
include "db_utilities.php";

function removeDupStreet($trip, $tripcols){
	$street_time = array();
	foreach ($trip as $val) {
		$street = $val[$tripcols[0]];
		$time = $val[$tripcols[1]];
		if(!array_key_exists($street, $street_time)){
			$street_time[$street] = $time;
		}
	}
	return $street_time;
}

function createEdge($conn, $street_time, $landmarks, $tripid){
	$cols = array("LandmarkU", "Intermediate", 
		"LandmarkV", "ArrivalTime", "LeavingTime", "TripID");
	$table = "landmarkgraph";

	$streets = array_keys($street_time);
	$size = count($streets);
	$low = 0;

	while($low < $size && !array_key_exists($streets[$low], $landmarks)){
		++$low;
	}
	$landmarkU = $landmarkV = $low < $size ? $streets[$low] : NULL;
	$atime = $ltime = $low < $size ? $street_time[$landmarkV] : NULL;
	++$low;

	while($low < $size){
		$inbetween = "";
		while($low < $size && !array_key_exists($streets[$low], $landmarks)){
			$inbetween .= "{$streets[$low]}-";
			++$low;
		}
		$inbetween = rtrim($inbetween, "-");
		$landmarkV = $low < $size ? $streets[$low] : NULL;
		$ltime = $low < $size ? $street_time[$landmarkV] : NULL;

		if(!is_null($landmarkU) && !is_null($landmarkV)){
			echo "{$landmarkU}-{$inbetween}-{$landmarkV} ({$atime} => {$ltime}) $tripid\n";
			$vals = array($landmarkU, $inbetween, $landmarkV, $atime, $ltime, $tripid);
			db_insert($conn, $table, array_combine($cols, $vals));
		}

		$landmarkU = $landmarkV;
		$atime = $ltime;
		++$low;
	}
	
}

function buildGraph($conn, $start, $end, $landmarks){
	$triptable = "bjtaxigps";
	$tripcols = array("Street", "UTC");
	$street_time = array();

	for($tripid = $start; $tripid != $end; ++$tripid){
		$cond = "TripID = {$tripid}";
		$trip = db_select($conn, $tripcols, $triptable, $cond);
		$street_time = removeDupStreet($trip, $tripcols);
		createEdge($conn, $street_time, $landmarks, $tripid);
	}
}

function fetchLandmarks($conn){
	$lmcols = array("LandmarkName");
	$lmtable = "landmark";
	$lmlimit = 500;
	$cond = "LandmarkID <= {$lmlimit}";
	$landmarks = db_select($conn, $lmcols, $lmtable, $cond);
	$lms = array();
	foreach ($landmarks as $lm) {
		foreach ($lmcols as $col) {
			$lms[$lm[$col]] = 1;
		}
	}
	return $lms;
}

set_time_limit(0);
ini_set('memory_limit','2048M');

$conn = connect_db();
$conn->set_charset("utf8");

$landmarks = fetchLandmarks($conn);

buildGraph($conn, $_POST['start'], $_POST['end'], $landmarks);

disconnect_db($conn);


?>