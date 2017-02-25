<?php
include "db_utilities.php";
class DistCreator{
	private $edge_start;
	private $edge_end;
	private $dir;
	private $dist_table;

	private $conn;

	public function __construct($edge_start, $edge_end, $dir, $dist_table){
		$this->edge_start = $edge_start;
		$this->edge_end = $edge_end;
		$this->dir = $dir;
		$this->dist_table = $dist_table;
		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	public function createDist(){
		$cols = array('EdgeID', 'Since', 'Until', 'Alpha', 'Beta');
		for ($edgeid = $this->edge_start; $edgeid != $this->edge_end; ++$edgeid) { 
			if(($handle = fopen("{$this->dir}{$edgeid}.csv", 'r')) !== FALSE){
				$from = 0;
				while(($line = fgetcsv($handle, 0, ',')) !== FALSE){
					$line[1] = ($line[1] == 'Inf' ? 65535 : $line[1]);
					$line[2] = ($line[2] == 'Inf' ? 65535 : $line[2]);
					$vals = array($edgeid, $from, intval($line[0]), $line[1], $line[2]);
					db_insert($this->conn, $this->dist_table, array_combine($cols, $vals));
					$from = intval($line[0]);
				}
			}
		}
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');

$distCreator = new DistCreator($_POST['edge_start'], $_POST['edge_end'], $_POST['dir'], 
	$_POST['dist_table']);

$distCreator->createDist();

?>