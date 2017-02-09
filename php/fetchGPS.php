<?php
include "db_utilities.php";

function fetch_GPS($id){
	$conn = connect_db();
	$sql_select = "SELECT GPS_LAT, GPS_LONG FROM BJTaxiGPS WHERE DataUnitID = $id;";
	$result = $conn->query($sql_select);
	$coords = '';
	if($result->num_rows > 0){
		$row = $result->fetch_assoc();
		$coords = "{$row['GPS_LAT']},{$row['GPS_LONG']}";
	}
	disconnect_db($conn);
	return $coords;
}


$res = fetch_GPS($_POST['id']);
echo $res;

?>