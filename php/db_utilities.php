<?php
include "../inc/jingodbinfo.inc";
function connect_db(){
	$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

	if($conn->connect_error){
		die("Connection failed: " . $conn->connect_error);
	}

	$conn->select_db(DB_DATABASE);
	return $conn;
}

function disconnect_db($conn){
	$conn->close();
}

function fetch_BDGPS($condition){
	$conn = connect_db();
	$sql_select = "SELECT DataUnitID, BD09_LONG, BD09_LAT FROM BJTaxiGPS WHERE " . $condition . ";";
	// $conn -> query("set names utf8;");
	// $sql_select = "SELECT BD09_LONG, BD09_LAT FROM JingoDB.BJTaxiGPS where DataUnitID >= 5795 and DataUnitID <= 5805" ;
	$result = $conn->query($sql_select);
	$coords = array();
	if($result->num_rows > 0){
		while($row = $result->fetch_assoc()){
			$coords[] = array("id"=> $row["DataUnitID"], "x" => $row["BD09_LONG"], "y" => $row["BD09_LAT"]);
		}
	}
	disconnect_db($conn);
	return $coords;
}

function write_street($id, $street){
	$conn = connect_db();
	$conn -> query("set names utf8;");
	$succ = true;
	$sql_update = "UPDATE BJTaxiGPS SET Street = '" . $street . "' WHERE DataUnitID = " . $id;
	$attemps = 10;
	while(!($succ = $conn->query($sql_update)) && $attemps > 0){
		echo "(" . $conn->errno . ")" . $conn->error . "<br>";
		--$attemps;
	}
	disconnect_db($conn);
	return $succ;
}

?>