<?php
include "db_utilities.php";

// array(2) { ["status"]=> int(0) ["result"]=> array(8) { ["location"]=> array(2) { ["lng"]=> float(116.40913269046) ["lat"]=> float(39.812350039367) } ["formatted_address"]=> string(33) "北京市丰台区南苑路115号" ["business"]=> string(6) "南苑" ["addressComponent"]=> array(10) { ["country"]=> string(6) "中国" ["country_code"]=> int(0) ["province"]=> string(9) "北京市" ["city"]=> string(9) "北京市" ["district"]=> string(9) "丰台区" ["adcode"]=> string(6) "110106" ["street"]=> string(9) "南苑路" ["street_number"]=> string(6) "115号" ["direction"]=> string(6) "东北" ["distance"]=> string(2) "90" } ["pois"]=> array(0) { } ["poiRegions"]=> array(0) { } ["sematic_description"]=> string(35) "七彩华盛建材超市附近35米" ["cityCode"]=> int(131) } }

function get_street($start, $end){
	$conn = connect_db();
	$conn -> query("set names utf8;");
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$baidu_street = "http://api.map.baidu.com/geocoder/v2/?output=json";
	$api_key = "&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$location = "&location=";
	
	$log_file = "street_log.txt";
	$pool_size = 100000;

	while($start < $end){
		$sql_select = "SELECT BD09_LONG, BD09_LAT FROM JingoDB.BJTaxiGPS WHERE DataUnitID >= " 
		. $start . " AND DataUnitID < " . min($start + $pool_size, $end);
		$result = $conn->query($sql_select);
		if($result->num_rows > 0){
			$index = $start;
			while($row = $result->fetch_assoc()){
				$req_url = $baidu_street . $api_key . $location . $row["BD09_LAT"] . "," . $row["BD09_LONG"];
				curl_setopt($curl, CURLOPT_URL, $req_url);
				$resp_array = json_decode(curl_exec($curl), true);
				if($resp_array['status'] != "0"){
					echo "Error: " . $resp_array['status'] . "<br>";
					return;
				}
				$street = $resp_array["result"]["addressComponent"]["street"];
				$sql_update = "UPDATE JingoDB.BJTaxiGPS SET Street = '" . $street . "' WHERE DataUnitID = " . $index;
				if(!$conn -> query($sql_update)){
					echo "(" . $conn->errno . ")" . $conn->error . "<br>";
					return;
				}
				$log_cont = $index . ": " . $row["BD09_LONG"] . ", " . $row["BD09_LAT"] . " " . $street . "\n";
				file_put_contents($log_file, $log_cont, FILE_APPEND | LOCK_EX);
				++$index;
			}
		}
		$start += $pool_size;
	}

	curl_close($curl);
	disconnect_db($conn);
}

set_time_limit(0);

ini_set('memory_limit','1024M');


$start = microtime(true);

get_street(11, 100);

$time_elapsed_secs = microtime(true) - $start;

echo $time_elapsed_secs . "<br>";

?>