<?php
include "db_utilities.php";

function convert_coord($coords_str){

	$baidu_geoconv = "http://api.map.baidu.com/geoconv/v1/?";
	$api_key = "ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$url = $baidu_geoconv . $api_key . rtrim($coords_str, ";");

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

function write_conv_coord($resp_array, $start){
	$conn = connect_db();
	$succ = true;
	foreach($resp_array['result'] as $coord){
		$sql_update = "UPDATE JingoDB.BJTaxiGPS SET BD09_LONG = " . $coord['x'] . ", BD09_LAT = " 
			. $coord['y'] . " WHERE DataUnitID = " . $start;
		$attemps = 10;
		while($attemps > 0 && !($succ = $conn->query($sql_update))){
			echo "(" . $conn->errno . ")" . $conn->error . "<br>";
			--$attemps;
		}
		if(!$succ){
			break;
		}
		++$start;
	}
	disconnect_db($conn);
	return array('succ' => $succ, 'index' => $start);
}

function get_BD_coord($start, $end){

	$pool_size = 500000;
	$conn = connect_db();

	$limit_per_req = 100;
	
	while($start < $end){
		$sql_select = "SELECT GPS_LONG, GPS_LAT FROM JingoDB.BJTaxiGPS WHERE DataUnitID >= " 
		. $start . " AND DataUnitID < " . min($start + $pool_size, $end);
		$result = $conn->query($sql_select);
		if($result->num_rows > 0){
			$count = 0;
			$coords_str = "&coords=";
			while($row = $result->fetch_assoc()){
				++$count;
				$coords_str = $coords_str . $row["GPS_LONG"] . "," . $row["GPS_LAT"] . ";";
				if($count % $limit_per_req == 0){
					$resp_array = convert_coord($coords_str);
					if($resp_array['status'] != "0"){
						echo "Conversion Failed at " . ($start + $count - $limit_per_req) . " (Error: " . $resp_array['status'] . ")<br>";
						disconnect_db($conn);
						return;
					}
					$write_res = write_conv_coord($resp_array, $start + $count - $limit_per_req);
					if(!$write_res['succ']){
						echo "Update Failed at " . $write_res['index'] . "<br>";
						disconnect_db($conn);
						return;
					}
					$coords_str = "&coords=";
				}
			}
			if($count % $limit_per_req != 0){
				$resp_array = convert_coord($coords_str);
				if($resp_array['status'] != "0"){
					echo "Conversion Failed at " . ($start + $count - $count % $limit_per_req) . " (Error: " . $resp_array['status'] . ")<br>";
					break;
				}
				$write_res = write_conv_coord($resp_array, $start + $count - $count % $limit_per_req);
				if(!$write_res['succ']){
					echo "Update Failed at " . $write_res['index'] . "<br>";
					break;
				}
			}
		}
		$start += $pool_size;
	}
	disconnect_db($conn);
}

set_time_limit(0);

ini_set('memory_limit','2048M');

$start = microtime(true);

get_BD_coord(80000000, 82678936);

$time_elapsed_secs = microtime(true) - $start;

echo $time_elapsed_secs . "<br>";

?>
