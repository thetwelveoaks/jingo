<?php
include "db_utilities.php";


function send_request($coords_str){

	$baidu_geoconv = "http://api.map.baidu.com/geoconv/v1/?";
	$api_key = "ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$url = "{$baidu_geoconv}{$api_key}{$coords_str}";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL, $url);

	$attemps = 10;
	$resp_array = array('status' => -1);
	do{
		$resp = curl_exec($curl);
		if($errno = curl_errno($curl)) {
	    	$error_message = curl_strerror($errno);
	    	echo "cURL error ({$errno}):\n {$error_message}" . "<br>";
	    	--$attemps;
	    	continue;
		}
		$resp_array = json_decode($resp, true);
		--$attemps;
	}while($resp_array['status'] != "0" && $attemps > 0);

	curl_close($curl);
	return $resp_array;
}

function convert_coords($conn, $start, $end, $table){
	$limit_per_req = 100;
	$in_cols = array("GPS_LONG", "GPS_LAT");
	$out_cols = array("BD09_LONG", "BD09_LAT");
	
	while($start < $end){
		$cond = "DataUnitID >= {$start} and DataUnitID < {$end} limit {$limit_per_req}";
		$res = db_select($conn, $table, $in_cols, $cond);
		$coords_str = "&coords=";
		foreach ($res as $coord) {
			foreach ($in_cols as $col) {
				$coords_str .= "{$coord[$col]},";
			}
			$coords_str = rtrim($coords_str, ",");
			$coords_str .= ";";
		}
		$coords_str = rtrim($coords_str, ";");
		$resp_array = send_request($coords_str);
		if($resp_array['status'] != "0"){
			echo "Conversion Failed at {$start} (Error: {$resp_array['status']})\n";
			break;
		}
		foreach ($resp_array['result'] as $item) {
			$values = array();
			$count = 0;
			foreach ($item as $key => $value) {
				$values[$out_cols[$count]] = $value;
				++$count;
			}
			$cond = "DataUnitID = {$start}";
			$succ = db_update($conn, $table, $values, $cond);
			++$start;
		}
	}
}

$conn = connect_db();

set_time_limit(0);

convert_coords($conn, $_POST['start'], $_POST['end'], $_POST['table']);

disconnect_db($conn);
?>