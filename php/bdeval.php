<?php
include "db_utilities.php";

class MedianFinder{
	private $data;

	public function __construct($data){
		$this->data = $data;
	}

	public function findMedian(){
		$size = count($this->data);
		$hi_median = $this->findKth(0, $size - 1, $size / 2);
		if($size % 2 == 1){
			return $hi_median;
		}
		$low_median = $this->findKth(0, $size - 1, $size / 2 - 1);
		return ($hi_median + $low_median) / 2;
	}

	private function findKth($low, $high, $k){
		$q = $this->partition($low, $high);
		if($q == $k){
			return $this->data[$q];
		}else if($q > $k){
			return $this->findKth($low, $q - 1, $k);
		}
		return $this->findKth($q + 1, $high, $k);
	}

	private function swap(&$x, &$y){
		$tmp = $y;
		$y = $x;
		$x = $tmp;
	}

	private function partition($p, $r){
		$pivot = $this->data[$r];
		$j = $p;
		$i = $j - 1;

		while($j < $r){
			if($this->data[$j] <= $pivot){
				++$i;
				$this->swap($this->data[$i], $this->data[$j]);
			}
			++$j;
		}
		$this->swap($this->data[$i + 1], $this->data[$r]);
		return $i + 1;
	}
}

class BDEvaluator{
	private $edge_start;
	private $edge_end;
	private $sample_limit;
	private $edge_count_table;
	private $dist_table;
	private $opti_index;
	private $ldmkgraph;
	private $gps_table;
	private $res_table;


	private $conn;
	private $curl;

	public function __construct($edge_start, $edge_end, $sample_limit, $edge_count_table, 
		$ldmkgraph, $gps_table, $dist_table, $opti_index, $res_table){
		$this->edge_start = $edge_start;
		$this->edge_end = $edge_end;
		$this->sample_limit = $sample_limit;
		$this->edge_count_table = $edge_count_table;
		$this->ldmkgraph = $ldmkgraph;
		$this->gps_table = $gps_table;
		$this->dist_table = $dist_table;
		$this->opti_index = $opti_index;
		$this->res_table = $res_table;

		$this->conn = connect_db();
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
	}

	public function __destruct(){
		disconnect_db($this->conn);
		curl_close($this->curl);
	}

	private function fetchSamplePoints(&$trips, $ldmk){
		$gps_cols = array('BD09_LONG', 'BD09_LAT');
		$coords = array();

		foreach ($trips as $tripid) {
			$gps_cond = "TripID = {$tripid} and Street = '$ldmk' limit 1";
			$res = db_select($this->conn, $this->gps_table, $gps_cols, $gps_cond);
			$coords[] = array(round($res[0][$gps_cols[0]], 6), round($res[0][$gps_cols[1]], 6));
		}

		return $coords;
	}

	private function bdevaluate($ldmkU, $ldmkV){
		$trip_cols = array('TripID');
		$trip_cond = "LandmarkU = '$ldmkU' and LandmarkV = '$ldmkV' 
				order by rand() limit {$this->sample_limit}";
		$res = db_select($this->conn, $this->ldmkgraph, $trip_cols, $trip_cond);
		$trips = array();
		foreach ($res as $item) {
			$trips[] = $item[$trip_cols[0]];
		}
		$coordsU = $this->fetchSamplePoints($trips, $ldmkU);
		$coordsV = $this->fetchSamplePoints($trips, $ldmkV);

		$baidu_routematrix = "http://api.map.baidu.com/routematrix/v2/driving?output=json";
		$api_key = "57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
		$tactics = "12";

		$durations = array();
		$distances = array();
		$sum = 0;
		
		for($i = 0; $i < $this->sample_limit; ++$i){
			$origins = "{$coordsU[$i][1]},{$coordsU[$i][0]}";
			$destis = "{$coordsV[$i][1]},{$coordsV[$i][0]}";
			$url = "{$baidu_routematrix}&origins={$origins}&destinations={$destis}&tactics={$tactics}&ak={$api_key}";
			curl_setopt($this->curl, CURLOPT_URL, $url);

			// echo "{$url}\n";
			$attemps = 10;
			$resp_array = array('status' => -1);
			do{
				$resp = curl_exec($this->curl);
				if($errno = curl_errno($this->curl)) {
			    	$error_message = curl_strerror($errno);
			    	echo "cURL error ({$errno}):\n {$error_message}" . "<br>";
			    	--$attemps;
			    	continue;
				}
				$resp_array = json_decode($resp, true);
				--$attemps;
			}while($resp_array['status'] != "0" && $attemps > 0);

			if($resp_array['status'] == 0){
				// foreach ($resp_array['result'] as $item) {
					// echo "{$item['distance']['value']},{$item['duration']['value']}\n";
				$durations[] = $resp_array['result'][0]['duration']['value'];
				$distances[] = $resp_array['result'][0]['distance']['value'];
				$sum += $resp_array['result'][0]['duration']['value'];
				// }
			}else{
				echo "Status: {$resp_array['status']}\n";
				break;
			}
		}

		$median_dur = new MedianFinder($durations);
		$median_dist = new MedianFinder($distances);
		$mean = $sum / $this->sample_limit;

		return array($median_dur->findMedian(), $mean, $median_dist->findMedian());
		// echo "Median: {$median}\nMean: {$avrg}\n";

	}

	private function timeOfDay(){
		$date = new DateTime();
		$hour = intval($date->format('G'));
		$mint = intval($date->format('i'));
		return $hour * 60 + $mint;
	}

	private function inverseWeibull($alpha, $beta){
		return pow(-log(1 - floatval($this->opti_index)), 1 / $beta) * $alpha;
	}

	private function jingoEvaluate($edgeid){
		$tod = $this->timeOfDay();
		$dist_cols = array('Alpha', 'Beta');
		$dist_cond = "EdgeID = {$edgeid} and Since <= {$tod} and {$tod} < Until";
		$ret = db_select($this->conn, $this->dist_table, $dist_cols, $dist_cond);
		$esti = 0;
		$since = $tod - $tod % 30;
		$until = $since + 30;
		if(count($ret) > 0){
			$esti = $this->inverseWeibull(floatval($ret[0][$dist_cols[0]]), floatval($ret[0][$dist_cols[1]]));
		}
		return array("{$since}-{$until}", $esti);
	}
	
	public function startEvaluation(){
		$ldmk_cols = array('LandmarkU', 'LandmarkV');
		for($edgeid = $this->edge_start; $edgeid != $this->edge_end; ++$edgeid){
			$cond = "EdgeID = {$edgeid}";
			$res = db_select($this->conn, $this->edge_count_table, $ldmk_cols, $cond);
			$ldmkU = $res[0][$ldmk_cols[0]];
			$ldmkV = $res[0][$ldmk_cols[1]];

			$bd_res = $this->bdevaluate($ldmkU, $ldmkV);
			$jingo_res = $this->jingoEvaluate($edgeid);
			$content = array('EdgeID' => $edgeid, 'TimeOfDay' => $jingo_res[0], 
				'BD_Median' => $bd_res[0], 'BD_Mean' => $bd_res[1], 
				'Median_Dist' => $bd_res[2], 'Jingo_Est' => $jingo_res[1]);
			db_insert($this->conn, $this->res_table, $content, "", "ignore");
		}
	}
}


set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$bdEvaluator = new BDEvaluator($_POST['edge_start'], $_POST['edge_end'], 
	$_POST['sample_limit'], $_POST['edge_count_table'], $_POST['ldmkgraph'], 
	$_POST['gps_table'], $_POST['dist_table'], $_POST['opti_index'], $_POST['res_table']);
$bdEvaluator->startEvaluation();
?>