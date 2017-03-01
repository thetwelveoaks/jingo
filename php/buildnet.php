<?php 
include "db_utilities.php";

class NetBuilder{

	const RMIN = 0;
	const RMAX = 1;

	private $ranktable;
	private $rankstart;
	private $rankend;

	private $ldmktable;
	private $trvltimelimit;
	private $path;

	private $conn;

	public function __construct($ldmktable, $triltimelimit, $rktable, $rkstart, $rkend, $path){
		$this->ldmktable = $ldmktable;
		$this->trvltimelimit = $triltimelimit;
		$this->ranktable = $rktable;
		$this->rankstart = $rkstart;
		$this->rankend = $rkend;
		$this->path = $path;
		$this->conn = connect_db();
	}

	public function __destruct(){
		disconnect_db($this->conn);
	}

	private function fetchLdmkPairs(){
		$cols = array('LandmarkU', 'LandmarkV');
		$cond = "EdgeID >= {$this->rankstart} and EdgeID < {$this->rankend}";
		$res = db_select($this->conn, $this->ranktable, $cols, $cond);
		$ldmkpairs = array();
		foreach ($res as $item) {
			$ldmkpairs[] = array($item[$cols[0]], $item[$cols[1]]);
		}
		return $ldmkpairs;
	}

	private function fetchArvlDur($ldmkU, $ldmkV){
		$cols = array('ArrivalTime', 'Duration');
		$cond = "LandmarkU = '{$ldmkU}' and LandmarkV = '{$ldmkV}'";
		$res = db_select($this->conn, $this->ldmktable, $cols, $cond);
		$arvl = $dur = array();
		foreach ($res as $item) {
			$trvltime = $item[$cols[1]];
			if($trvltime > 0 && $trvltime <= $this->trvltimelimit){
				$arvl[] = $this->timeSinceMidngt($item[$cols[0]]);
				$dur[] = $trvltime;
			}
		}
		return array($arvl, $dur);
	}

	private function timeSinceMidngt($timestamp){
		$date = new DateTime();
		$date->setTimestamp(intval($timestamp));
		$hour = intval($date->format('G'));
		$mint = intval($date->format('i'));
		// $secd = intval($date->format('s'));

		return $hour * 60 + $mint;
		// return $hour * 60 * 60 + $mint * 60 + $secd;
	}

	// private function normalise($data, $rmin, $rmax){
	// 	$dmin = min($data);
	// 	$dmax = max($data);

	// 	foreach ($data as &$val) {
	// 		$val = $rmin + ($rmax - $rmin) * ($val - $dmin) / ($dmax - $dmin);
	// 	}

	// 	return $data;
	// }

	// private function standardise($data){
	// 	$n = count($data);
	// 	$mean = array_sum($data) / $n;
	// 	$sum = 0;
	// 	foreach ($data as $val) {
	// 		$sum += pow($val - $mean, 2);
	// 	}
	// 	$sdv = sqrt($sum / $n);
	// 	foreach ($data as &$val) {
	// 		$val = ($val - $mean) / $sdv;
	// 	}
	// 	return $data;
	// }

	public function buildnet(){
		$ldmkpairs = $this->fetchLdmkPairs();
		$idx = $this->rankstart;
		foreach ($ldmkpairs as $item) {
			$arvldurpair = $this->fetchArvlDur($item[0], $item[1]);
			$arvl = $arvldurpair[0];
			$dur = $arvldurpair[1];
			
			$file_name = "{$this->path}{$idx}.csv";
			// file_put_contents("input/{$ldmkU}_{$ldmkV}.csv", implode(',', $arvl) . "\n", FILE_APPEND);
			// file_put_contents("input/{$ldmkU}_{$ldmkV}.csv", implode(',', $dur) . "\n", FILE_APPEND);

			for($i = 0; $i < count($arvl); ++$i){
				file_put_contents($file_name, "{$arvl[$i]},{$dur[$i]}\n", FILE_APPEND | LOCK_EX);
				// echo "{$arvl[$i]},{$dur[$i]}\n";
			}

			++$idx;

			// for($i = 0; $i < count($ipt); ++$i){
			// 	echo "{$ipt[$i]},";
			// }
			// echo "\n";

			// for($i = 0; $i < count($opt); ++$i){
			// 	echo "{$ipt[$i]},";
			// }

			// $ipt = $this->normalise(array_keys($iopairs), self::RMIN, self::RMAX);
			// $opt = $this->normalise(array_values($iopairs), self::RMIN, self::RMAX);
			// $iopairs = array_combine($ipt, $opt);

			// $ipt = $this->standardise(array_keys($iopairs));
			// $opt = $this->standardise(array_values($iopairs));
			// $iopairs = array_combine($ipt, $opt);

			// foreach ($iopairs as $pair) {
			// 	echo "{$pair[0]},{$pair[1]}\n";
			// }
		}
	}
}

set_time_limit(0);
ini_set('memory_limit','2048M');
date_default_timezone_set("Asia/Singapore");

$netBuilder = new NetBuilder($_POST['ldmktable'], $_POST['trvltimelimit'], 
	$_POST['rktable'], $_POST['rkstart'], $_POST['rkend'], $_POST['path']);
$netBuilder->buildnet();

?>