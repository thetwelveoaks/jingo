<?php
include "db_utilities.php";

class Filter{
	private $ldmk_start;
	private $ldmk_end;
	private $ldmk_table;
	private $data_table;
	private $update_table;
	private $path;
	private $bchmk;

	private $conn;
	private $num_dele;

	public function __construct($ldmk_start, $ldmk_end, $ldmk_table, $data_table, $path, $bchmk, $update_table){
		$this->ldmk_start = $ldmk_start;
		$this->ldmk_end = $ldmk_end;
		$this->ldmk_table = $ldmk_table;
		$this->data_table = $data_table;
		$this->update_table = $update_table;
		$this->path = $path;
		$this->bchmk = $bchmk;
		$this->conn = connect_db();
		$this->num_dele = 0;
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function getCentres($ldmk){
		$filename = "{$this->path}{$ldmk}.csv";
		$centres = array();
		if(($handle = fopen($filename, 'r')) !== FALSE){
			while(($line = fgetcsv($handle, 0, ',')) !== FALSE){
				$centres[] = array($line[0], $line[1]);
			}
		}
		return $centres;
	}

	private function getRecords($ldmk){
		$ldmk_cols = array('LandmarkName');
		$ldmk_cond = "LandmarkID = {$ldmk}";
		$ret = db_select($this->conn, $this->ldmk_table, $ldmk_cols, $ldmk_cond);

		$data_cols = array('DataUnitID', 'BD09_LONG', 'BD09_LAT');
		$data_cond = "Street = '{$ret[0][$ldmk_cols[0]]}'";
		$ret = db_select($this->conn, $this->data_table, $data_cols, $data_cond);
		$records = array();

		foreach ($ret as $item) {
			$records[] = array($item[$data_cols[0]], $item[$data_cols[1]], $item[$data_cols[2]]);
		}

		return $records;
	}

	private function removeRecord($centres, $records){
		// $cols = array('CUID', 'UTC', 'UnixEpoch', 'GPS_LONG', 'GPS_LAT', 'BD09_LONG', 'BD09_LAT', 
			// 'Street', 'Head', 'Speed', 'Occupied', 'TripID');
		foreach ($records as $recd) {
			$min_dist = INF;
			foreach ($centres as $centre) {
				$min_dist = min($min_dist, 
					$this->getHaversineDist($centre[0], $centre[1], $recd[1], $recd[2]));
			}
			if($min_dist > $this->bchmk){
				$cond = "DataUnitID = {$recd[0]}";
				// $res = db_select($this->conn, $this->data_table, $cols, $cond);
				// db_insert($this->conn, $this->update_table, $res[0]);
				// $cond = "DataUnitID = {$recd[0]}";
				db_delete($this->conn, $this->data_table, $cond);
				++$this->num_dele;
			}
			// echo "{$recd[1]},{$recd[2]}: {$min_dist}\n";
		}
	}

	private function toRadian($degree){
		return $degree * M_PI / 180;
	}

	private function getHaversineDist($long1, $lat1, $long2, $lat2){
		$BJ_LAT = $this->toRadian(39);
		$EQ_R = 6378137; // equatorial radius in metres
		$POL_R = 6356752; // polar radius in metres

		$BJ_R = sqrt((pow($EQ_R * $EQ_R * cos($BJ_LAT), 2) + pow($POL_R * $POL_R * sin($BJ_LAT), 2)) 
			/ (pow($EQ_R * cos($BJ_LAT), 2) + pow($EQ_R * sin($BJ_LAT), 2)));

		$long1 = $this->toRadian($long1);
		$lat1 = $this->toRadian($lat1);
		$long2 = $this->toRadian($long2);
		$lat2 = $this->toRadian($lat2);

		$d = 2 * $BJ_R * asin(sqrt(pow(sin(($lat1 - $lat2) / 2), 2) + 
			cos($lat1) * cos($lat2) * pow(sin(($long1 - $long2) / 2), 2)));

		return $d;
	}

	private function getWithin($ldmk, $centres, $records){
		foreach ($records as $recd) {
			$min_dist = INF;
			foreach ($centres as $centre) {
				$min_dist = min($min_dist, 
					$this->getHaversineDist($centre[0], $centre[1], $recd[1], $recd[2]));
			}
			if($min_dist <= $this->bchmk){
				db_insert($this->conn, $this->update_table, 
					array('LandmarkID' => $ldmk, 'BD09_LONG' => $recd[1], 'BD09_LAT' => $recd[2]));
			}
			// echo "{$recd[1]},{$recd[2]}: {$min_dist}\n";
		}
	}

	public function doFiltering(){
		// echo $this->getHaversineDist(116.28108256395,39.95402367770,116.28053866579,39.94292652218);
		for($ldmk = $this->ldmk_start; $ldmk != $this->ldmk_end; ++$ldmk){
			$centres = $this->getCentres($ldmk);
			$records = $this->getRecords($ldmk);
			// $this->removeRecord($centres, $records);
			$this->getWithin($ldmk, $centres, $records);
		}
		// fecho "{$this->num_dele}\n";
	}

}

set_time_limit(0);
ini_set('memory_limit','2048M');

$filter = new Filter($_POST['ldmk_start'], $_POST['ldmk_end'], 
	$_POST['ldmk_table'], $_POST['data_table'], $_POST['path'], 
	$_POST['bchmk'], $_POST['update_table']);

$filter->doFiltering();

?>