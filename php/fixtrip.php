<?php
include "db_utilities.php";

class FixTrip{
	private $cuid_start;
	private $cuid_end;
	private $table;
	private $occup;
	private $tripid;

	private $conn;

	public function __construct($cuid_start, $cuid_end, $table, $occup, $tripid){
		$this->cuid_start = $cuid_start;
		$this->cuid_end = $cuid_end;
		$this->table = $table;
		$this->occup = $occup;
		$this->tripid = $tripid;
		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	public function startFixTrip(){
		$cols = array('DataUnitID', 'TripID');
		for($cuid = $this->cuid_start; $cuid != $this->cuid_end; ++$cuid){
			$cond = "CUID = {$cuid} AND OCCUPIED = {$this->occup}";
			$res = db_select($this->conn, $this->table, $cols, $cond);
			if(count($res) != 0){
				$this->fixTripID($res, $cols);
			}
		}
		echo "{$this->tripid}\n";
	}

	private function fixTripID($res, $cols){
		$last = $curr = $res[0][$cols[1]];
		foreach ($res as $item) {
			$curr = $item[$cols[1]];
			if($last != $curr){
				++$this->tripid;
			}
			// echo "{$item[$cols[0]]},{$item[$cols[1]]},{$this->tripid}\n";
			$values = array($cols[1] => $this->tripid);
			$cond = "{$cols[0]} = {$item[$cols[0]]}";
			$succ = db_update($this->conn, $this->table, $values, $cond);
			$last = $curr;
		}
		++$this->tripid;
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');

$fixtrip = new FixTrip($_POST['start'], $_POST['end'], 
	$_POST['table'], $_POST['occup'], $_POST['tripid']);

$fixtrip->startFixTrip();

?>