<?php
include "db_utilities.php";

function convert_coord($coords, $start){

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$baidu_geoconv = "http://api.map.baidu.com/geoconv/v1/?";
	$api_key = "ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$coords_str = "&coords=";

	$limit_per_req = 50;
	$count = 0;
	foreach($coords as $coord){
		++$count;
		$coords_str = $coords_str . $coord['x'] . "," . $coord['y'] . ";";
		if($count == $limit_per_req){
			$url = $baidu_geoconv . $api_key . rtrim($coords_str, ";");
			curl_setopt($curl, CURLOPT_URL, $url); 
			$resp = curl_exec($curl);
			if($errno = curl_errno($curl)) {
		    	$error_message = curl_strerror($errno);
		    	echo "cURL error ({$errno}):\n {$error_message}" . "<br>";
		    	curl_close($curl);
		    	return;
			}
			$resp_array = json_decode($resp, true);
			if($resp_array['status'] != "0"){
				echo "Error: " . $resp_array['status'] . "<br>";
				curl_close($curl);
		    	return;
			}
			if(!($succ = write_conv_coord($resp_array, $start))){
				curl_close($curl);
				return;
			}
			$count = 0;
			$coords_str = "&coords=";
			$start += $limit_per_req;
		}
	}
	if($count != 0){
		$url = $baidu_geoconv . $api_key . rtrim($coords_str, ";");
		curl_setopt($curl, CURLOPT_URL, $url); 
		$resp = curl_exec($curl);
		if($errno = curl_errno($curl)) {
	    	$error_message = curl_strerror($errno);
	    	echo "cURL error ({$errno}):\n {$error_message}\n";
		}else{
			$resp_array = json_decode($resp, true);
			if($resp_array['status'] != "0"){
				echo "Error: " . $resp_array['status'] . "<br>";
			}else{
				$succ = write_conv_coord($resp_array, $start);
			}
		}
	}
	curl_close($curl);
}

function write_conv_coord($resp_array, $start){
	$conn = connect_db();
	$log_file = 'coords_log.txt';
	foreach($resp_array['result'] as $coord){
		$sql_update = "UPDATE JingoDB.BJTaxiGPS SET BD09_LONG = " . $coord['x'] . ", BD09_LAT = " 
			. $coord['y'] . " WHERE DataUnitID = " . $start;
		if(!($succ = $conn->query($sql_update))){
			echo "(" . $conn->errno . ")" . $conn->error . "<br>";
			disconnect_db($conn);
			return false;
		}
		$log_cont = $start . ": " . $coord['x'] . ", " . $coord['y'] . "\n";
		file_put_contents($log_file, $log_cont, FILE_APPEND | LOCK_EX);
		++$start;
	}
	disconnect_db($conn);
	return true;
}

function get_BD_coord($start, $end){

	$pool_size = 500000;
	$conn = connect_db();

	while($start < $end){
		$sql_select = "SELECT GPS_LONG, GPS_LAT FROM JingoDB.BJTaxiGPS WHERE DataUnitID >= " 
		. $start . " AND DataUnitID < " . min($start + $pool_size, $end);
		$result = $conn->query($sql_select);
		if($result->num_rows > 0){
			$coords = array();
			while($row = $result->fetch_assoc()){
				$coords[] = array("x" => $row["GPS_LONG"], "y" => $row["GPS_LAT"]);
			}
		}
		convert_coord($coords, $start);
		$start += $pool_size;
	}

	disconnect_db($conn);
}

set_time_limit(0);

ini_set('memory_limit','2048M');

$start = microtime(true);

get_BD_coord(42000000, 42100000);

$time_elapsed_secs = microtime(true) - $start;

echo $time_elapsed_secs . "<br>";

?>
