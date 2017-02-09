<?php
include "db_utilities.php";
function geocode_street($location){
	// $pattern = '/"address":({[^}]*})/';
	$pattern = '/"address1":"([^"]*)"/';
	$curl = curl_init();
	$req_url = "https://www.mapquest.com/latlng/" . $location;
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL, $req_url);

	$resp_array = array("status" => -1, "street" => "");
	$attemps = 10;
	do{
		$resp = curl_exec($curl);
		if($errno = curl_errno($curl)) {
	    	$error_message = curl_strerror($errno);
	    	echo "cURL error ({$errno}):\n {$error_message}" . "<br>";
	    	--$attemps;
	    	continue;
		}
		$info = curl_getinfo($curl);
		$resp_array['status'] = $info["http_code"];
		// echo $info["http_code"] . "<br>";
		preg_match($pattern, $resp, $matches, PREG_OFFSET_CAPTURE);
		// $resp_array = json_decode($matches[1][0], true);
		// echo $resp_array['address1'];
		$resp_array['street'] = $matches[1][0];
		// $resp_array = json_decode($resp, true);
		--$attemps;
	}while($resp_array['status'] != "200" && $attemps > 0);

	return $resp_array;
}

function get_street($start, $end){
	$conn = connect_db();
	$pool_size = 10000;
	$file = fopen("mapquest.txt", "a");

	while($start < $end){
		$sql_select = "SELECT GPS_LONG, GPS_LAT FROM BJTaxiGPS WHERE DataUnitID >= " 
		. $start . " AND DataUnitID < " . min($start + $pool_size, $end);
		$result = $conn->query($sql_select);
		if($result->num_rows > 0){
			$index = $start;
			while($row = $result->fetch_assoc()){
				$location = $row["GPS_LAT"] . "," . $row["GPS_LONG"];
				$resp_array = geocode_street($location);

				if($resp_array['status'] != "200"){
					echo "Request Failed at " . $index . " (Error: " . $resp_array['status'] . ")<br>";
					disconnect_db($conn);
					return;
				}
				
				$street = $resp_array["street"];
				fwrite($file, $index . ":" . $street . "\n");
				// if(!($succ = write_street($index, $street))){
				// 	echo "Update Failed at " . $index . "<br>";
				// 	disconnect_db($conn);
				// 	return;
				// }
				++$index;
			}
		}
		$start += $pool_size;
	}
	disconnect_db($conn);
}


	set_time_limit(0);

	ini_set('memory_limit','2048M');

	$start = microtime(true);

	get_street(1, 100);

	$time_elapsed_secs = microtime(true) - $start;

	echo $time_elapsed_secs . "<br>";
	// geocode_street("40.003770,116.397090");
?>