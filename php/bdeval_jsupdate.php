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

class BDUpdator{
	private $durations;
	private $distances;
	private $jingoeval;
	private $edgeid;
	private $res_table;
	private $conn;

	public function __construct($edgeid, $durations, $distances, $jingoeval, $res_table){
		$this->edgeid = $edgeid;
		$this->durations = $durations;
		$this->distances = $distances;
		$this->jingoeval = $jingoeval;
		$this->res_table = $res_table;
		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	public function startUpdate(){
		$medFd_dur = new MedianFinder($this->durations);
		$medFd_dist = new MedianFinder($this->distances);

		$median_dur = $medFd_dur->findMedian();
		$median_dist = $medFd_dist->findMedian();
		$mean = array_sum($this->durations)/ count($this->durations);

		$content = array('EdgeID' => $this->edgeid, 'TimeOfDay' => $this->jingoeval[0], 
			'BD_Median' => $median_dur, 'BD_Mean' => $mean, 
			'Median_Dist' => $median_dist, 'Jingo_Est' => $this->jingoeval[1]);
		db_insert($this->conn, $this->res_table, $content, "", "ignore");
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$bdUpdator = new BDUpdator($_POST['edgeid'], $_POST['durations'], $_POST['distances'],
	$_POST['jingoeval'], $_POST['res_table']);
$bdUpdator->startUpdate();

?>