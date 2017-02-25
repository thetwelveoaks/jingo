<?php
include "db_utilities.php";

class GraphTester{
	private $conn;

	private $ldmk_table;

	public function __construct($ldmk_table){
		$this->conn = connect_db();
		$this->ldmk_table = $ldmk_table;
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function printPath($predecessors, $curr){
		if(is_null($curr)){
			return;
		}
		$this->printPath($predecessors, $predecessors[$curr]);
		echo "{$curr}-";
	}

	public function isConnected($ldmkU, $ldmkV){
		$cols = array('LandmarkV');
		$predecessors = array();

		$queue = new SplQueue();
		$predecessors[$ldmkU] = null;
		$queue->enqueue($ldmkU);

		while(!$queue->isEmpty()){
			$head = $queue->dequeue();
			$cond = "LandmarkU = '{$head}' and EdgeID <= 5000";
			$neighbours = db_select($this->conn, $this->ldmk_table, $cols, $cond);

			foreach ($neighbours as $item) {
				$curr = $item[$cols[0]];
				if($curr == $ldmkV){
					$predecessors[$curr] = $head;
					$this->printPath($predecessors, $curr);
					echo "\n";
				}else if(!array_key_exists($curr, $predecessors)){
					$predecessors[$curr] = $head;
					$queue->enqueue($curr);
				}
			}
		}
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');

$graphTester = new GraphTester($_POST['ldmk_table']);
$graphTester->isConnected($_POST['ldmkU'], $_POST['ldmkV']);

?>