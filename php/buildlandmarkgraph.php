<?php
include "db_utilities.php";

class GraphBuilder{
	private $start;
	private $end;
	private $ldmktable;
	private $triptable;
	private $ldmklimit;
	private $holi_table;
	private $wrkd_table;

	private $conn;
	private $landmarks;

	public function __construct($start, $end, $ldmktable, $triptable, $ldmklimit, $holi_table, $wrkd_table){
		$this->start = $start;
		$this->end = $end;
		$this->ldmktable = $ldmktable;
		$this->triptable = $triptable;
		$this->ldmklimit = $ldmklimit;
		$this->holi_table = $holi_table;
		$this->wrkd_table = $wrkd_table;

		$this->conn = connect_db();
		$this->landmarks = $this->fetchldmk();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function fetchldmk(){
		$cols = array('LandmarkName', 'LandmarkID');
		$cond = "{$cols[1]} <= {$this->ldmklimit}";
		$res = db_select($this->conn, $this->ldmktable, $cols, $cond);
		$ldmks = array();
		foreach ($res as $item) {
			$ldmks[$item[$cols[0]]] = $item[$cols[1]];
		}
		return $ldmks;
	}

	private function isLandmark($street){
		return array_key_exists($street, $this->landmarks);
	}

	private function isHoliday($atime){
		$date = date('d', $atime);
		$day = date('w', $atime);

		if($date == '01' || $date == '28' || $date == '29'){
			return true;
		}else if($date == '31'){
			return false;
		}else if($day == 0 || $day == 6){
			return true;
		}
		return false;
	}

	public function buildGraph(){
		$street_cols = array("Street");
		$utc_cols = array("UnixEpoch");
		
		for($tripid = $this->start; $tripid != $this->end; ++$tripid){
			$street_utc = array();
			$street_cond = "TripID = {$tripid}";
			$streets = db_select($this->conn, $this->triptable, $street_cols, $street_cond, "DISTINCT");
			foreach ($streets as $item) {
				$street = $item[$street_cols[0]];
				$utc_cond = "{$street_cols[0]} = '{$street}' AND {$street_cond} LIMIT 1";
				$ret = db_select($this->conn, $this->triptable, $utc_cols, $utc_cond);
				if(count($ret) > 0){
					$street_utc[$street] = $ret[0][$utc_cols[0]];
				}
			}
			$this->addEdge($street_utc, $tripid);
		}
	}


	private function addEdge($street_utc, $tripid){
		$cols = array("LandmarkU", "Intermediate", 
			"LandmarkV", "ArrivalTime", "LeavingTime", "Duration", "TripID");

		$streets = array_keys($street_utc);
		$size = count($streets);
		$low = 0;

		while($low < $size && !$this->isLandmark($streets[$low])){
			++$low;
		}
		$landmarkU = $landmarkV = $low < $size ? $streets[$low] : NULL;
		$atime = $ltime = $low < $size ? $street_utc[$landmarkV] : NULL;
		++$low;

		while($low < $size){
			$inbetween = "";
			while($low < $size && !$this->isLandmark($streets[$low])){
				$inbetween .= "{$streets[$low]}-";
				++$low;
			}
			$inbetween = rtrim($inbetween, "-");
			$landmarkV = $low < $size ? $streets[$low] : NULL;
			$ltime = $low < $size ? $street_utc[$landmarkV] : NULL;

			if(!is_null($landmarkU) && !is_null($landmarkV)){
				// echo "{$landmarkU}-{$inbetween}-{$landmarkV} ({$atime} => {$ltime}) $tripid\n";
				// echo $this->isHoliday($atime) ? "YES\n" : "NO\n";

				$vals = array($landmarkU, $inbetween, $landmarkV, $atime, $ltime, $ltime - $atime, $tripid);
				if($this->isHoliday($atime)){
					db_insert($this->conn, $this->holi_table, array_combine($cols, $vals));
				}else{
					db_insert($this->conn, $this->wrkd_table, array_combine($cols, $vals));
				}
				
			}

			$landmarkU = $landmarkV;
			$atime = $ltime;
			++$low;
		}
	}

}

set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$graphBuilder = new GraphBuilder($_POST['tripid_start'], $_POST['tripid_end'], 
	$_POST['ldmktable'], $_POST['triptable'], $_POST['ldmklimit'], $_POST['holi_table'], $_POST['wrkd_table']);

$graphBuilder->buildGraph();

?>