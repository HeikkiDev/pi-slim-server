<?php
if (!defined("SPECIALCONSTANT"))
	die("Acceso denegado");
define('HOST', "localhost");
define('USER', "root");
define('PASSWORD', "usuario");
define('DATABASE', "osporthello");
define('OK', 200);
define('NOT_COMPLETED', 202);
define('CONFLICT', 409);

class Result {
	var $code;
	var $status;
	var $message;
	var $data;
	function setCode($c) {$this->code = $c;}
	function getCode() {return $this->code;}
	function setStatus($s) {$this->status = $s;}
	function getStatus() {return $this->status;}
	function setMessage($m) {$this->message = $m;}
	function getMessage() {return $this->message;}
	function setData($s) {$this->data = $s;}
	function getData() {return $this->data;}
}
?>
