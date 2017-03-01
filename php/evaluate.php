<?php
include "db_utilities.php";

class Evaluator{
	private $edge_start;
	private $edge_end;
	private $dist_table;
	private $eval_table;
	private $edge_table;
	private $opti_index;

	private $conn;
	private $count;
	private $sq_error;

	public function __construct($edge_start, $edge_end, $dist_table, $eval_table, $edge_table, $opti_index){
		$this->edge_start = $edge_start;
		$this->edge_end = $edge_end;
		$this->dist_table = $dist_table;
		$this->eval_table = $eval_table;
		$this->edge_table = $edge_table;
		$this->opti_index = $opti_index;

		$this->conn = connect_db();
		$this->count = 0;
		$this->sq_error = 0;
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function timeSinceMidngt($timestamp){
		$date = new DateTime();
		$date->setTimestamp(intval($timestamp));
		$hour = intval($date->format('G'));
		$mint = intval($date->format('i'));
		return $hour * 60 + $mint;
	}

	private function estimate($alpha, $beta){
		return pow(-log(1 - floatval($this->opti_index)), 1 / $beta) * $alpha;
	}

	private function evaluate($edgeid, $ldmkU, $ldmkV){
		$time_cols = array('ArrivalTime', 'Duration');
		$dist_cols = array('Alpha', 'Beta');

		$time_cond = "LandmarkU = '{$ldmkU}' and LandmarkV = '{$ldmkV}'";
		$res = db_select($this->conn, $this->eval_table, $time_cols, $time_cond);
		foreach ($res as $item) {
			$arvl = $this->timeSinceMidngt($item[$time_cols[0]]);
			$durt = $item[$time_cols[1]];
			$dist_cond = "EdgeID = {$edgeid} and Since <= {$arvl} and {$arvl} < Until";
			$ret = db_select($this->conn, $this->dist_table, $dist_cols, $dist_cond);
			if(count($ret) > 0){
				$esti = $this->estimate(floatval($ret[0][$dist_cols[0]]), floatval($ret[0][$dist_cols[1]]));
				$error = $durt - $esti;
				echo "{$edgeid}: {$arvl}, {$durt}, {$esti}, {$error}\n";
				++$this->count;
				$this->sq_error += pow($error, 2);
			}
		}
	}

	public function startEvaluation(){
		$edge_cols = array('LandmarkU', 'LandmarkV');
		for($edgeid = $this->edge_start; $edgeid != $this->edge_end; ++$edgeid){
			$cond = "EdgeID = {$edgeid}";
			$res = db_select($this->conn, $this->edge_table, $edge_cols, $cond);
			if(count($res) > 0){
				$this->evaluate($edgeid, $res[0][$edge_cols[0]], $res[0][$edge_cols[1]]);
			}
		}

		$mse = $this->sq_error / $this->count;
		$rmse = sqrt($mse);
		echo "Count = {$this->count}\nMSE = {$mse}\nRMSE = {$rmse}\n";
	}

}


set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$evaluator = new Evaluator($_POST['edge_start'], $_POST['edge_end'], $_POST['dist_table'], 
	$_POST['eval_table'], $_POST['edge_table'], $_POST['opti_index']);

$evaluator->startEvaluation();


?>