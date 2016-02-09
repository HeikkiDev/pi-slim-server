<?php
if (!defined("SPECIALCONSTANT"))
die("Acceso denegado");
function getConnection(){
	try{
		$connection = new PDO('mysql:host=' . HOST .';dbname=' . DATABASE . ';charset=utf8', USER, PASSWORD);
		$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e){
		echo "Error: " . $e->getMessage();
	}
	return $connection;
}
?>