<?php
include "db_utilities.php";

class GraphBuilder{
	var $start;
	var $end;
	var $ldmktable;
	var $triptable;
	var $ldmklimit;

	var $conn;

	function __construct($start, $end, $ldmktable, $triptable, $ldmklimit){
		$this->start = $start;
		$this->end = $end;
		$this->ldmktable = $ldmktable;
		$this->triptable = $triptable;
		$this->ldmklimit = $ldmklimit;

		$this->conn = connect_db();
	}

	function __destruct(){
		disconnect_db($this->conn);
	}

	function isLandmark($street){
		$cond = "LandmarkName = '{$street}' AND LandmarkID <= {$this->ldmklimit}";
		$res = db_select($this->conn, $this->ldmktable, array(), $cond);
		return count($res) > 0;
	}

	function isHoliday($atime){
		date_default_timezone_set("Asia/Singapore");
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

	function buildGraph(){
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
				$street_utc[$street] = $ret[0][$utc_cols[0]];
			}
			$this->addEdge($street_utc, $tripid);
		}
	}


	function addEdge($street_utc, $tripid){
		$cols = array("LandmarkU", "Intermediate", 
			"LandmarkV", "ArrivalTime", "LeavingTime", "Duration", "TripID");
		$holi_table = "holi_ldmkgraph";
		$wrkd_table = "wrkd_ldmkgraph";

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
					db_insert($this->conn, $holi_table, array_combine($cols, $vals));
				}else{
					db_insert($this->conn, $wrkd_table, array_combine($cols, $vals));
				}
				
			}

			$landmarkU = $landmarkV;
			$atime = $ltime;
			++$low;
		}
		
	}

}

set_time_limit(0);
ini_set('memory_limit','1024M');

$graphBuilder = new GraphBuilder($_POST['start'], $_POST['end'], 
	$_POST['ldmktable'], $_POST['triptable'], $_POST['ldmklimit']);

$graphBuilder->buildGraph();

?>