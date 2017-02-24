<?php
include "../inc/jingodbinfo.inc";
function connect_db(){
	$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

	if($conn->connect_error){
		die("Connection failed: " . $conn->connect_error);
	}

	$conn->select_db(DB_DATABASE);
	$conn->set_charset("utf8");
	return $conn;
}

function disconnect_db($conn){
	$conn->close();
}

function db_select($conn, $table, $cols, $cond = "true", $distinct = ""){
	$sql_select = "SELECT {$distinct} ";
	if(count($cols) == 0){
		$sql_select .= "*,";
	}
	foreach ($cols as $col) {
		$sql_select .= "{$col},";
	}
	$sql_select = rtrim($sql_select, ",");
	$sql_select .= " FROM {$table} WHERE {$cond};";
	$ret = $conn->query($sql_select);
	$res = array();
	if($ret->num_rows > 0){
		while($row = $ret->fetch_assoc()){
			$curr = array();
			foreach ($cols as $col) {
				$curr[$col] = $row[$col];
			}
			$res[] = $curr;
		}
	}
	return $res;
}

function db_update($conn, $table, $values, $cond){
	$sql_update = "UPDATE {$table} SET ";
	foreach ($values as $key => $value) {
		if(is_numeric($value)){
			$sql_update .= "{$key} = {$value},";
		}else{
			$sql_update .= "{$key} = '{$value}',";
		}
	}
	$sql_update = rtrim($sql_update, ",");
	$sql_update .= " WHERE {$cond};";
	// echo "{$sql_update}\n";
	// $succ = true;
	$succ = $conn->query($sql_update);
	return $succ;
}

function db_insert($conn, $table, $values, $cond = "", $ignore = ""){
	$sql_insert = "INSERT {$ignore} INTO {$table} ";
	$cols = "";
	$vals = "";
	foreach ($values as $key => $value) {
		$cols .= "{$key},";
		$vals .= (is_numeric($value) ? "{$value}," : "'{$value}',");
	}
	$cols = rtrim($cols, ",");
	$vals = rtrim($vals, ",");
	$sql_insert .= "({$cols}) VALUES ({$vals}) {$cond};";
	// echo "{$sql_insert}\n";
	$conn->query($sql_insert);
}

?>