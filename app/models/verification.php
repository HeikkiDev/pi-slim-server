<?php 

// New user verification

$app->get("/api/verification/:email/:code", function($email, $code) use($app) {
	$result = checkVerification($email, $code);
	$show = "No se ha podido validar tu registro";

	if($result->getCode() == TRUE){
		// Al verificar paso los datos de la tabla auxuliar Verification a la tabla User, con lo que el usuario ya podrá hacer Login
		$user = $result->getData();
		$inserted = postUser($email, $user->User_password, $user->User_firstname, $user->User_alternativeEmail);
		if($inserted->getCode() == TRUE){
			deleteVerificationUser($email); // Borramos de la tabla auxiliar Verification
			$show = "Validación completada. Ahora puede hacer login en la app OSport Hello!";
		}
	}

	$app->response->headers->set("Content-type", "text/html; charset=utf-8");
	$app->response->status(OK);
	$app->response->body('<h2>'.$show.'</h2>');
});

function checkVerification($email, $code){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_password, User_alternativeEmail FROM Verification WHERE User_email = ? AND Verification_code = ?");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $code);
		$dbquery->execute();
		$data = $dbquery->fetchObject();
		$connection = null;

		if ($data != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setData($data);
		}	
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("Does the Verification exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

function postVerificationUser($email, $password, $first, $alternative, $code) {
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM User WHERE User_email ='".$email."'");
		$dbquery->execute();
		$number = $dbquery->rowCount();
		if ($number > 0) // Ya existe el usuario, no se le permite registro ni verificación
			return 2;
		$connection = null;

		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO Verification (User_email, User_password, User_firstname, User_alternativeEmail, Verification_code, Verification_creationTime) VALUES(?, sha1(?), ?, ?, ?, ?)");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $password);
		$dbquery->bindParam(3, $first);
		$dbquery->bindParam(4, $alternative);
		$dbquery->bindParam(5, $code); // código de verificación
		$dbquery->bindParam(6, date("Y-m-d H:i:s"));
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if ($number > 0) {
			return 1;
		}	
		else {	
			return 3;
		}
	} catch (PDOException $e) {
		return 3;
	}
}

function deleteVerificationUser($email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM Verification WHERE User_email = ?");
		$dbquery->bindParam(1, $email);
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


// Restore password verification

$app->get("/api/verification/restore-password/:email/:code", function($email, $code) use($app) {
	$result = new Result();
	$checked = checkCodePassword($email, $code);

	$password = '';
	$password1 = '';
	$password2 = '';
	if(isset($_GET['password1']))
		$password1 = $_GET['password1'];
	if(isset($_GET['password2']))
		$password2 = $_GET['password2'];

	if($password1 == $password2){
		$result->setCode(TRUE);
		$password = $password1;
	}
	else{
		$result->setCode(FALSE);
		$show = "Las contraseñas no coinciden. Vuelve al formulario desde el botón del mail.";
	}

	if(strlen($password) < 8){
		$result->setCode(FALSE);
		$show = "La longitud mínima es de 8 caracteres.";
	}

	if($result->getCode() == TRUE && $checked->getCode() == TRUE){
		updateVerifiedPassword($email, $password);
		$show = "Cambio de contraseña completado";
		deleteVerificationPassword($email, $code);
	}

	if($checked->getCode() == FALSE){
		$show = "No se ha podido comprobar tu petición de cambio de contraseña";
	}

	$app->response->headers->set("Content-type", "text/html; charset=utf-8");
	$app->response->status(OK);
	$app->response->body('<h2>'.$show.'</h2>');
});

function checkCodePassword($email, $code){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Verification WHERE User_email = ? AND Verification_code = ?");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $code);
		$dbquery->execute();
		$data = $dbquery->fetchObject();
		$connection = null;

		if ($data != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setData($data);
		}	
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("Does the Verification exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

function insertVerificationPassword($email, $code) {
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO Verification (User_email, Verification_code, Verification_creationTime) VALUES(?, ?, ?)");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $code); // código de verificación
		$dbquery->bindParam(3, date("Y-m-d H:i:s"));
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if ($number > 0) {
			return TRUE;
		}	
		else {	
			return FALSE;
		}
	} catch (PDOException $e) {
		return FALSE;
	}
}

function updateVerifiedPassword($email, $password){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE User SET User_password = sha1(?) WHERE User_email = ?");
		$dbquery->bindParam(1, $password);
		$dbquery->bindParam(2, $email);
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

function deleteVerificationPassword($email, $code) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM Verification WHERE User_email = ? AND Verification_code = ?");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $code);
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

 ?>