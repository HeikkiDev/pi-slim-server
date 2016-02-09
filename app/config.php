<?php
if (!defined("SPECIALCONSTANT"))
	die("Acceso denegado");
define('HOST', "localhost");
define('USER', "alumno");
define('PASSWORD', "ies29700412");
define('DATABASE', "sitesManager");
define('TABLE', "sites");
define('OK', 200);
define('NOT_COMPLETED', 202);
define('CONFLICT', 409);

class Result {
	var $code;
	var $status;
	var $message;
	var $sites;
	function setCode($c) {$this->code = $c;}
	function getCode() {return $this->code;}
	function setStatus($s) {$this->status = $s;}
	function getStatus() {return $this->status;}
	function setMessage($m) {$this->message = $m;}
	function getMessage() {return $this->message;}
	function setSites($s) {$this->sites = $s;}
	function getSites() {return $this->sites;}
}

class Site {
	var $id;
	var $name;
	var $link;
	var $email;
}

class Response{
	var $code;
	var $message;
	var $sites;
	function setCode($c){
		$this->code = $c;
	}
	function getCode(){
		return $this->code;
	}
	function setMessage($m){
		$this->message = $m;
	}
	function getMessage(){
		return $this->message;
	}
	function setSites($s){
		$this->sites = $s;
	}
	function getSites(){
		return $this->sites;
	}
}
?>
