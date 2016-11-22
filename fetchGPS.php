<?php
include "db_utilities.php";

function fetch_GPS($cuid, $since, $until){
	$conn = connect_db();
	$sql_select = "SELECT BD09_LONG, BD09_LAT FROM JingoDB.BJTaxiGPS WHERE CUID = " . $cuid 
		. " AND UTC >= '" . $since . "' AND UTC < '" . $until . "';";
	$result = $conn->query($sql_select);
	$coords = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$coords[] = array("x" => $row["BD09_LONG"], "y" => $row["BD09_LAT"]);
		}
	}
	disconnect_db($conn);
	return $coords;
}

$res = fetch_GPS($_POST['CUID'], $_POST['since'], $_POST['until']);
echo json_encode($res);
?>