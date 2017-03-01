<?php
include "db_utilities.php";

set_time_limit(0);
ini_set('memory_limit','2048M');

$dir = $_POST['dir'];
$f_start = $_POST['f_start'];
$f_end = $_POST['f_end'];
$old_table = $_POST['old_table'];
$new_table = $_POST['new_table'];

$conn = connect_db();

$cols = array('DataUnitID', 'CUID', 'UTC', 'UnixEpoch', 'GPS_LONG', 
	'GPS_LAT', 'BD09_LONG', 'BD09_LAT', 'Street', 'Head', 'Speed', 'Occupied', 'TripID');

for($file = $f_start; $file != $f_end; ++$file){
	if(($handle = fopen("{$dir}{$file}.csv", 'r')) !== FALSE){
		while(($line = fgetcsv($handle, 0, ',')) !== FALSE){
			$dataunitid = $line[0];
			$cond = "DataUnitID = {$dataunitid}";
			$ret = db_select($conn, $old_table, $cols, $cond);
			if(count($ret) > 0){
				db_insert($conn, $new_table, $ret[0]);
			}
		}
	}
}

disconnect_db($conn);

?>