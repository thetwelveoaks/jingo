<?php
include "db_utilities.php";

class BDEvaluator{
	private $edgeid;
	private $sample_perct;
	private $edge_count_table;
	private $dist_table;
	private $opti_index;
	private $ldmkgraph;
	private $gps_table;

	private $conn;

	public function __construct($edgeid, $sample_perct, $edge_count_table, 
		$ldmkgraph, $gps_table, $dist_table, $opti_index){
		$this->edgeid = intval($edgeid);
		$this->sample_perct = floatval($sample_perct);
		$this->edge_count_table = $edge_count_table;
		$this->ldmkgraph = $ldmkgraph;
		$this->gps_table = $gps_table;
		$this->dist_table = $dist_table;
		$this->opti_index = floatval($opti_index);

		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
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

	private function bdevaluate($ldmkU, $ldmkV, $support){
		$trip_cols = array('TripID');
		$num_sample = intval($this->sample_perct * $support);
		$trip_cond = "LandmarkU = '$ldmkU' and LandmarkV = '$ldmkV' 
				order by rand() limit {$num_sample}";
		$res = db_select($this->conn, $this->ldmkgraph, $trip_cols, $trip_cond);
		$trips = array();
		foreach ($res as $item) {
			$trips[] = $item[$trip_cols[0]];
		}
		$coordsU = $this->fetchSamplePoints($trips, $ldmkU);
		$coordsV = $this->fetchSamplePoints($trips, $ldmkV);

		return array($coordsU, $coordsV);

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
		$ldmk_cols = array('LandmarkU', 'LandmarkV', 'Support');
		$cond = "EdgeID = {$this->edgeid}";
		$res = db_select($this->conn, $this->edge_count_table, $ldmk_cols, $cond);
		$ldmkU = $res[0][$ldmk_cols[0]];
		$ldmkV = $res[0][$ldmk_cols[1]];
		$support = $res[0][$ldmk_cols[2]];

		$bd_res = $this->bdevaluate($ldmkU, $ldmkV, $support);
		$jingo_res = $this->jingoEvaluate($this->edgeid);
		
		$res = array('bdeval' => $bd_res, 'jingoeval' => $jingo_res);
		echo json_encode($res);
	}
}


set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$bdEvaluator = new BDEvaluator($_POST['edgeid'], $_POST['sample_perct'], $_POST['edge_count_table'], 
	$_POST['ldmkgraph'], $_POST['gps_table'], $_POST['dist_table'], $_POST['opti_index']);
$bdEvaluator->startEvaluation();
?>