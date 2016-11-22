<?php
include "db_utilities.php";

function convert_coord($coords){

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$baidu_geoconv = "http://api.map.baidu.com/geoconv/v1/?&from=1&to=5";
	$api_key = "&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$coords_str = "&coords=";

	$limit_per_req = 100;
	$count = 0;
	$resp_array = array();
	foreach($coords as $coord){
		++$count;
		$coords_str = $coords_str . $coord['x'] . "," . $coord['y'] . ";";
		if($count == $limit_per_req){
			$url = $baidu_geoconv . $api_key . rtrim($coords_str, ";");
			curl_setopt($curl, CURLOPT_URL, $url); 
			$resp = curl_exec($curl);
			$temp = json_decode($resp, true);
			if($temp['status'] == "0"){
				$resp_array[] = $temp;
			}else{
				echo "Error: " . $temp['status'] . "<br>";
				break;
			}
			$count = 0;
			$coords_str = "&coords=";
		}
	}
	if($count != 0){
		$url = $baidu_geoconv . $api_key . rtrim($coords_str, ";");
		curl_setopt($curl, CURLOPT_URL, $url); 
		$resp = curl_exec($curl);
		$temp = json_decode($resp, true);
		if($temp['status'] == "0"){
			$resp_array[] = $temp;
		}else{
			echo "Error: " . $temp['status'] . "<br>";
		}
	}

	curl_close($curl);
	return $resp_array;
}

function write_conv_coord($resp_array, $pks, $conn){
	$log_file = 'coords_log.txt';
	$index = 0;
	foreach($resp_array as $item){
		foreach($item['result'] as $coord){
			$sql_update = "UPDATE JingoDB.BJTaxiGPS SET BD09_LONG = " . $coord['x'] . ", BD09_LAT = " 
				. $coord['y'] . " WHERE DataUnitID = " . $pks[$index];
			if(!($succ = $conn->query($sql_update))){
				echo "(" . $conn->errno . ")" . $conn->error . "<br>";
				return;
			}
			$log_cont = $pks[$index] . ": " . $coord['x'] . ", " . $coord['y'] . "\n";
			file_put_contents($log_file, $log_cont, FILE_APPEND | LOCK_EX);
			++$index;
		}
	}
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
			$pks = array();
			$index = $start;
			while($row = $result->fetch_assoc()){
				$coords[] = array("x" => $row["GPS_LONG"], "y" => $row["GPS_LAT"]);
				$pks[] = $index;
				++$index;
			}
		}
		$resp_array = convert_coord($coords);
		write_conv_coord($resp_array, $pks, $conn);
		$start += $pool_size;
	}

	disconnect_db($conn);
}

set_time_limit(0);

ini_set('memory_limit','5120M');


$start = microtime(true);

get_BD_coord(17000000, 19000000);

$time_elapsed_secs = microtime(true) - $start;

echo $time_elapsed_secs . "<br>";


?>
