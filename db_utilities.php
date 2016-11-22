<?php
function connect_db(){
	$servername = '127.0.0.1';
	$username = "weiyumou";
	$password = "weiyumou";

	$conn = new mysqli($servername, $username, $password);

	if($conn->connect_error){
		die("Connection failed: " . $conn->connect_error);
	}

	// echo "Connected successfully<br>";

	return $conn;
}

function disconnect_db($conn){
	$conn->close();
}
?>