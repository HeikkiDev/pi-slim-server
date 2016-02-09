<?php
if (!defined("SPECIALCONSTANT"))
	die("Acceso denegado");

$app->get("/", function() use($app) {
	$app->response->headers->set("Content-type", "text/html; charset=utf-8");
	$app->response->status(OK);
	$app->response->body(
	"<h1>RESTful API Slim</h1>
	<a href ='doc/index.html'>API Documentation</a>");
});

$app->get("/api/v1/sites", function() use($app) {
	//$app->response->headers->set("Content-type", "application/json"); // Esto ya está definido en index.php
	$result = getSites(); // Obtener todos los Sites
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getSites() {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM " . TABLE);
		$dbquery->execute();
		$sites = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		$connection = null;
		$result->setCode(TRUE);
		$result->setStatus(OK);
		$result->setSites($sites);
	} 
	catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/v1/sites/:id", function($id) use($app) { // :id indica que el parámetro id es opcional, puede venir o no
	$result = getSite($id); // Obtener unSite
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getSite($id) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM " . TABLE . " WHERE id = ?");
		$dbquery->bindParam(1, $id);
		$dbquery->execute();
		$site = $dbquery->fetchObject();
		$connection = null;

		if ($site != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setSites($site);
		}	
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("Does the site exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->post("/api/v1/sites", function() use($app) {
	//get params
	//$name = $app->request->post('name');
	//$link = $app->request->post('link');
	//$email = $app->request->post('email');
	$json = $app->request->post('site');
	$site = json_decode($json);

	$result = postSite($site->name, $site->link, $site->email); // Añadir un Site
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function postSite($name, $link, $email) {
	$result = new Result();
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO " . TABLE . " (name, link, email) VALUES(?, ?, ?)");
		$dbquery->bindParam(1, $name);
		$dbquery->bindParam(2, $link);
		$dbquery->bindParam(3, $email);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if ($number > 0) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
		}	
		else {	
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("NOT INSERTED");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->put("/api/v1/sites", function() use($app) {
	//get params
	$id = $app->request->put('id');
	$name = $app->request->put('name');
	$link = $app->request->put('link');
	$email = $app->request->put('email');

	$result = putSite($id, $name, $link, $email); // Modificar un Site
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putSite($id, $name, $link, $email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE " . TABLE . " SET name = ?, link = ?, email = ? WHERE id = ?");
		$dbquery->bindParam(1, $name);
		$dbquery->bindParam(2, $link);
		$dbquery->bindParam(3, $email);
		$dbquery->bindParam(4, $id);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if ($number > 0) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
		}
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("NOT UPDATED");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->delete("/api/v1/sites/:id", function($id) use($app) {
	//
	$result = deleteSite($id); // Borrar unSite
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function deleteSite($id) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM " . TABLE . " WHERE id = ?");
		$dbquery->bindParam(1, $id);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if ($number > 0) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
		}
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("NOT DELETED");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/v1/sites/search/:text", function($text) use($app) {
	$result = findByName($text); // Buscar unSite
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function findByName($text) {
	$result = new Result();
	try{
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM " . TABLE . " WHERE LOWER(name) LIKE ? ORDER BY name");
		$dbquery->bindParam(1, $text);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$sites = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		$connection = null;
		if ($number > 0) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setSites($sites);
		}
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("NOTHING FOUND");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->post("/api/v1/sites/email", function() use($app) {
	//get params
	$json = $app->request->post('email');
	$site = json_decode($json);

	$result = emailSite($site->from, $site->passwd, $site->to, $site->subject, $site->message); // Enviar mail
	$app->response->status($result->getCode());
	$app->response->body(json_encode($result));
});

function emailSite($from, $password, $to, $subject, $message){
	header('Content-type: application/json;charset=utf8');
	require_once('phpmailer524/class.phpmailer.php');
	//include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded
	require_once "config.php";
	$response = new Response();

	$mail = new PHPMailer(true);
	// the true param means it will throw exceptions on errors, which we need to catch
	$mail->IsSMTP(); // telling the class to use SMTP
	try {
	$mail->SMTPDebug = 2;
	// enables SMTP debug information (for testing)
	//$mail->SMTPDebug = 0;
	$mail->SMTPAuth = true;
	// enable SMTP authentication
	$mail->SMTPSecure = "tls";
	// sets the prefix to the server
	$mail->Host = "smtp.gmail.com";
	// sets GMAIL as the SMTP server
	//$mail->Host = "smtp.openmailbox.org";
	$mail->Port = 587;
	// set the SMTP port for the GMAIL server
	$mail->Username = $from;
	// GMAIL username
	$mail->Password = $password;
	// GMAIL password
	$mail->AddAddress($to);
	// Receiver email
	$mail->SetFrom($from, 'paco g.');
	// email sender
	$mail->AddReplyTo($from, 'paco g');
	// email to reply
	$mail->Subject = $subject;
	// subject of the message
	$mail->AltBody = 'Message in plain text';
	// optional - MsgHTML will create an alternate automatically
	$mail->MsgHTML($message);
	// message in the email
	//$mail->AddAttachment('images/phpmailer.gif');
	// attachment
	$mail->Send();
	$response->setCode(TRUE);
	$response->setMessage("Message Sent OK to " . $to);
	echo json_encode($response);
	} catch (phpmailerException $e) {
	//echo $e->errorMessage(); //Pretty error messages from PHPMailer
	$response->setCode(FALSE);
	$response->setMessage("Error: " . $e->errorMessage());
	echo json_encode($response);
	} catch (Exception $e) {
	//echo $e->getMessage(); //Boring error messages from anything else!
	$response->setCode(FALSE);
	$response->setMessage("Error: " . $e->getMessage());
	echo json_encode($response);
	}

	return $response;
}

 ?>