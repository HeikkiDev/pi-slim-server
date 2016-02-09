<?php 
define("SPECIALCONSTANT", true);
require 'app/config.php';
require 'app/connect.php';
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
require 'app/routes.php';
$app->contentType('application/json');
$app->run();
 ?>