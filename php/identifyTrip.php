<?php
include "db_utilities.php";

class IdentifyTrip{

	private $cuid_start;
	private $cuid_end;
	private $table;
	private $tripid;
	private $threshold;
	private $occup;
	private $conn;

	public function __construct($cuid_start, $cuid_end, $table, $tripid, $threshold, $occup){
		$this->cuid_start = $cuid_start;
		$this->cuid_end = $cuid_end;
		$this->table = $table;
		$this->tripid = $tripid;
		$this->threshold = $threshold;
		$this->occup = $occup;
		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	public function startIdentifyTrip(){
		$cols = array('DataUnitID', 'UnixEpoch');
		for($cuid = $this->cuid_start; $cuid != $this->cuid_end; ++$cuid){
			$cond = "CUID = {$cuid} and Occupied = {$this->occup}";
			$res = db_select($this->conn, $this->table, $cols, $cond);
			if(count($res) > 0){
				$this->splitTrip($res, $cols);
			}
		}
	}

	public function splitTrip($res, $cols){
		$last = $curr = $res[0][$cols[1]];
		foreach ($res as $item) {
			$curr = $item[$cols[1]];
			if($curr - $last > $this->threshold){
				++$this->tripid;
			}
			$values = array('TripID' => $this->tripid);
			$cond = "{$cols[0]} = {$item[$cols[0]]}";
			$succ = db_update($this->conn, $this->table, $values, $cond);
			$last = $curr;
		}
		++$this->tripid;
	}
}


set_time_limit(0);
ini_set('memory_limit','2048M');

$identifyTrip = new IdentifyTrip($_POST['start'], $_POST['end'], $_POST['table'], 
	$_POST['tripid'], $_POST['threshold'], $_POST['occup']);
$identifyTrip->startIdentifyTrip();

?>