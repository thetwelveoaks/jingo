<?php
include "db_utilities.php";

class BDEvaluator{
	private $edge_start;
	private $edge_end;
	private $sample_limit;
	private $edge_count_table;
	private $street_count_table;
	private $bdeval_sample_table;
	private $dist_table;
	private $opti_index;
	private $req_limit;


	private $conn;
	private $curl;

	public function __construct($edge_start, $edge_end, $sample_limit, $edge_count_table, 
		$street_count_table, $bdeval_sample_table, $dist_table, $opti_index, $req_limit){
		$this->edge_start = $edge_start;
		$this->edge_end = $edge_end;
		$this->sample_limit = $sample_limit;
		$this->edge_count_table = $edge_count_table;
		$this->street_count_table = $street_count_table;
		$this->bdeval_sample_table = $bdeval_sample_table;
		$this->dist_table = $dist_table;
		$this->opti_index = $opti_index;
		$this->req_limit = $req_limit;

		$this->conn = connect_db();
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
	}

	public function __destruct(){
		disconnect_db($this->conn);
		curl_close($this->curl);
	}

	private function fetchSamplePoints($ldmk){
		$street_cols = array("LandmarkID");
		$street_cond = "LandmarkName = '{$ldmk}'";
		$res = db_select($this->conn, $this->street_count_table, $street_cols, $street_cond);
		$streetid = $res[0][$street_cols[0]];

		$bdeval_cols = array('BD09_LONG', 'BD09_LAT');
		$bdeval_cond = "LandmarkID = {$streetid} order by rand(100) limit {$this->sample_limit}";
		$res = db_select($this->conn, $this->bdeval_sample_table, $bdeval_cols, $bdeval_cond);
		$coords = array();
		foreach ($res as $item) {
			$coords[] = array(round($item[$bdeval_cols[0]], 6), round($item[$bdeval_cols[1]], 6));
		}
		return $coords;
	}

	private function bdevaluate($ldmkU, $ldmkV){
		$coordsU = $this->fetchSamplePoints($ldmkU);
		$coordsV = $this->fetchSamplePoints($ldmkV);

		$sizeU = count($coordsU);
		$sizeV = count($coordsV);

		$baidu_routematrix = "http://api.map.baidu.com/routematrix/v2/driving?output=json";
		$api_key = "57xrHyB1Wj5mLxWgra9GTrBYtoSCvK9i";
		$tactics = "12";

		$data = array();
		
		
		for($i = 0; $i != $sizeU; ++$i){
			$origins = "{$coordsU[$i][1]},{$coordsU[$i][0]}";
			$j = 0;
			while($j < $sizeV){
				$destis = "";
				for($k = $j; $k < min($sizeV, $j + $this->req_limit); ++$k){
					$destis .= "{$coordsV[$k][1]},{$coordsV[$k][0]}|";
				}
				$destis = rtrim($destis, "|");
				$url = "{$baidu_routematrix}&origins={$origins}&destinations={$destis}&tactics={$tactics}&ak={$api_key}";
				curl_setopt($this->curl, CURLOPT_URL, $url);

				echo "{$url}\n";
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
					foreach ($resp_array['result'] as $item) {
						// echo "{$item['distance']['value']},{$item['duration']['value']}\n";
						$data[] = $item['duration']['value'];
					}
				}else{
					echo "Status: {$resp_array['status']}\n";
				}
				$j += $k;
			}
		}

		$median = $this->findMedian($data);
		echo "{$median}\n";
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
		if(count($ret) > 0){
			$esti = $this->inverseWeibull(floatval($ret[0][$dist_cols[0]]), floatval($ret[0][$dist_cols[1]]));
			echo "{$esti}\n";
		}
	}

	private function findMedian(&$data){
		$size = count($data);
		$hi_median = $this->findKth($data, 0, $size - 1, $size / 2);
		if($size % 2 == 1){
			return $hi_median;
		}
		$low_median = $this->findKth($data, 0, $size - 1, $size / 2 - 1);
		return ($hi_median + $low_median) / 2;
	}

	private function findKth(&$data, $low, $high, $k){
		$q = $this->partition($data, $low, $high);
		if($q == $k){
			return $data[$q];
		}else if($q > $k){
			return $this->findKth($data, $low, $q - 1, $k);
		}
		return $this->findKth($data, $q + 1, $high, $k);
	}

	private function swap(&$x, &$y){
		$tmp = $y;
		$y = $x;
		$x = $tmp;
	}

	private function partition(&$data, $p, $r){
		$pivot = $data[$r];
		$j = $p;
		$i = $j - 1;

		while($j < $r){
			if($data[$j] <= $pivot){
				++$i;
				$this->swap($data[$i], $data[$j]);
			}
			++$j;
		}
		$this->swap($data[$i + 1], $data[$r]);
		return $i + 1;
	}

	public function startEvaluation(){
		$ldmk_cols = array('LandmarkU', 'LandmarkV');
		for($edgeid = $this->edge_start; $edgeid != $this->edge_end; ++$edgeid){
			$cond = "EdgeID = {$edgeid}";
			$res = db_select($this->conn, $this->edge_count_table, $ldmk_cols, $cond);
			$ldmkU = $res[0][$ldmk_cols[0]];
			$ldmkV = $res[0][$ldmk_cols[1]];
			$this->bdevaluate($ldmkU, $ldmkV);
			$this->jingoEvaluate($edgeid);
		}
	}
}


set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$bdEvaluator = new BDEvaluator($_POST['edge_start'], $_POST['edge_end'], 
	$_POST['sample_limit'], $_POST['edge_count_table'], $_POST['street_count_table'], 
	$_POST['bdeval_sample_table'], $_POST['dist_table'], $_POST['opti_index'], $_POST['req_limit']);
$bdEvaluator->startEvaluation();
?>