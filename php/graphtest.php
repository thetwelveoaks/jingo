<?php
include "db_utilities.php";

class GraphTester{
	private $conn;

	private $ldmk_table;
	private $edge_count_table;
	private $dist_table;

	private $ldmk_limit;
	private $edge_limit;

	private $dept_time;

	public function __construct($ldmk_table, $edge_count_table, $dist_table, 
		$ldmk_limit, $edge_limit, $dept_time){

		$this->conn = connect_db();
		$this->ldmk_table = $ldmk_table;
		$this->edge_count_table = $edge_count_table;
		$this->dist_table = $dist_table;
		$this->ldmk_limit = $ldmk_limit;
		$this->edge_limit = $edge_limit;
		$this->dept_time = intval($dept_time);
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function fetchldmk(){
		$cols = array('LandmarkName', 'LandmarkID');
		$cond = "{$cols[1]} <= {$this->ldmk_limit}";
		$res = db_select($this->conn, $this->ldmk_table, $cols, $cond);
		$ldmks = array();
		foreach ($res as $item) {
			$ldmks[$item[$cols[0]]] = array('cost' => INF, 'visited' => false);
		}
		return $ldmks;
	}

	private function fetchNeighbours($ldmkU){
		$cols = array('LandmarkV');
		$cond = "LandmarkU = '{$ldmkU}' and EdgeID <= {$this->edge_limit}";
		$res = db_select($this->conn, $this->edge_count_table, $cols, $cond);
		$neighbours = array();
		foreach ($res as $item) {
			$neighbours[] = $item[$cols[0]];
		}
		return $neighbours;
	}

	private function inverseWeibull($alpha, $beta, $opti_index = 0.5){
		return pow(-log(1 - floatval($opti_index)), 1 / $beta) * $alpha;
	}

	private function fetchDuration($ldmkU, $ldmkV, $tod = 600){
		$count_cols = array('EdgeID');
		$count_cond = "LandmarkU = '{$ldmkU}' and LandmarkV = '{$ldmkV}'";
		$ret = db_select($this->conn, $this->edge_count_table, $count_cols, $count_cond);
		$edgeid = $ret[0][$count_cols[0]];

		$dist_cols = array('Alpha', 'Beta');
		$dist_cond = "EdgeID = {$edgeid} and Since <= {$tod} and {$tod} < Until";
		$ret = db_select($this->conn, $this->dist_table, $dist_cols, $dist_cond);
		$esti = INF;
		if(count($ret) > 0){
			$esti = $this->inverseWeibull(floatval($ret[0][$dist_cols[0]]), floatval($ret[0][$dist_cols[1]]));
		}
		return $esti / 60;
	}

	private function printPath($pred_graph, $dst){
		$pred = $pred_graph[$dst];
		if (is_null($pred)) {
			echo "{$dst}";
			return;
		}
		$this->printPath($pred_graph, $pred);
		echo "-{$dst}";
	}

	private function getMinLdmk(&$ldmks){
		$min_ldmk = '';
		$min = INF;
		foreach ($ldmks as $ldmk => $value) {
			if (!$value['visited'] && $value['cost'] < $min) {
				$min = $value['cost'];
				$min_ldmk = $ldmk;
			}
		}
		return array($min < INF, $min_ldmk);
	}

	private function timeOfDay(){
		$date = new DateTime();
		$hour = intval($date->format('G'));
		$mint = intval($date->format('i'));
		return $hour * 60 + $mint;
	}

	public function findShortestPath($ldmkU, $ldmkV){
		$pred_graph = array($ldmkU => null);
		$ldmks = $this->fetchldmk();
		// $ldmks[$ldmkU]['cost'] = $this->dept_time;
		$ldmks[$ldmkU]['cost'] = $this->timeOfDay();

		$ret = $this->getMinLdmk($ldmks);

		while ($ret[0]) {
			$curr = $ret[1];
			$ldmks[$curr]['visited'] = true;
			$neighbours = $this->fetchNeighbours($curr);
			foreach ($neighbours as $nbr) {
				$estimate = $this->fetchDuration($curr, $nbr, $ldmks[$curr]['cost']);
				if ($ldmks[$curr]['cost'] + $estimate < $ldmks[$nbr]['cost']) {
					$ldmks[$nbr]['cost'] = $ldmks[$curr]['cost'] + $estimate;
					$pred_graph[$nbr] = $curr;
				}
			}
			$ret = $this->getMinLdmk($ldmks);
		}
		
		$this->printPath($pred_graph, $ldmkV);

		$duration = $ldmks[$ldmkV]['cost'] - $ldmks[$ldmkU]['cost'];
		echo "\n{$duration}\n";
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$graphTester = new GraphTester($_POST['ldmk_table'], $_POST['edge_count_table'], 
	$_POST['dist_table'], $_POST['ldmk_limit'], $_POST['edge_limit'], $_POST['dept_time']);
$graphTester->findShortestPath($_POST['ldmkU'], $_POST['ldmkV']);

?>