<?php
include "db_utilities.php";

function send_request($location){
	$curl = curl_init();
	$baidu_street = "http://api.map.baidu.com/geocoder/v2/?output=json";
	$api_key = "&ak=57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
	$req_url = "{$baidu_street}{$api_key}{$location}";
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL, $req_url);

	$attemps = 10;
	$resp_array = array('status' => -1);
	do{
		$resp = curl_exec($curl);
		if($errno = curl_errno($curl)) {
	    	$error_message = curl_strerror($errno);
	    	echo "cURL error ({$errno}):\n {$error_message}\n";
	    	--$attemps;
	    	continue;
		}
		$resp_array = json_decode($resp, true);
		--$attemps;
	}while($resp_array['status'] != "0" && $attemps > 0);

	curl_close($curl);
	return $resp_array;
}

function get_street($conn, $start, $end){
	$limit_per_req = 1;
	$out_cols = array("Street");
	$in_cols = array("BD09_LAT", "BD09_LONG");
	$table = "evaluation";

	while($start < $end){
		$cond = "DataUnitID >= {$start} AND DataUnitID < {$end} limit {$limit_per_req}";
		$res = db_select($conn, $in_cols, $table, $cond);
		$loc_str = "&location=";
		foreach ($res as $loc) {
			foreach ($in_cols as $col) {
				$loc_str .= "{$loc[$col]},";
			}
			$loc_str = rtrim($loc_str, ",");
		}
		$resp_array = send_request($loc_str);
		if($resp_array['status'] != "0"){
			echo "Request Failed at {$index} (Error: {$resp_array['status']})\n";
			break;
		}
		$street = $resp_array["result"]["addressComponent"]["street"];
		$values = array($out_cols[0] => $street);
		$cond = "DataUnitID = {$start}";
		$succ = db_update($conn, $table, $values, $cond);
		++$start;
	}
	
}

$conn = connect_db();
$conn->set_charset("utf8");

set_time_limit(0);

get_street($conn, $_POST['start'], $_POST['end']);

disconnect_db($conn);
?>