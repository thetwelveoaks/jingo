<?php
include "db_utilities.php";
$succ = write_street($_POST['id'], $_POST['street']);
echo $succ;
?>