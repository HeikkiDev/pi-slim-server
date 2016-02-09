<?php 

$app->get("/api/users/login", function() use($app) {
	$email = $app->request->get('email');
	$password = $app->request->get('password');

	$result = userLogin($email, $password);
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function userLogin($email, $password){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email, User_firstname, User_lastname, User_image, User_apiKey FROM User".' WHERE User_email=? AND User_password=sha1(?)');
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $password);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$data = $dbquery->fetchObject();
		$connection = null;
		if($number > 0){
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("Login completed");
			$result->setData($data);
		}
		else{
			$result->setCode(FALSE);
			$result->setStatus(CONFLICT);
			$result->setMessage("Login incorrect");
		}
	} 
	catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/users/facebooklogin", function() use($app) {
	$email = $app->request->get('email');
	$first_name = $app->request->get('first');
	$last_name = $app->request->get('last');

	$result = userFacebookLogin($email, $first_name, $last_name);
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function userFacebookLogin($email, $first, $last){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email, User_firstname, User_lastname, User_image, User_apiKey FROM User".' WHERE User_email=?');
		$dbquery->bindParam(1, $email);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$data = $dbquery->fetchObject();
		$connection = null;
		if($number > 0){
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("Login completed");
			$result->setData($data);
		}
		else{
			// Si no existe el usuario que ha hecho Login con Facebook, se inserta
			$dbquery = $connection->prepare("INSERT INTO User (User_email, User_firstname, User_lastname, User_image, User_apiKey) VALUES(?, ?, ?, ?)");
			$dbquery->bindParam(1, $email);
			$dbquery->bindParam(2, $first);
			$dbquery->bindParam(3, $last);
			$dbquery->bindParam(4, generarApiKey($email)); // Genera Api Key para el usuario
			$dbquery->execute();
			$number = $dbquery->rowCount();
			$connection = null;
			if ($number > 0) {
				// Se devuelven los datos en $data del usuario insertado
				$$dbquery = $connection->prepare("SELECT User_email, User_firstname, User_lastname, User_image, User_apiKey FROM User".' WHERE User_email=?');
				$dbquery->bindParam(1, $email);
				$dbquery->execute();
				$number = $dbquery->rowCount();
				$data = $dbquery->fetchObject();
				$connection = null;
				if($number > 0){
					$result->setCode(TRUE);
					$result->setStatus(OK);
					$result->setMessage("Login completed");
					$result->setData($data);
				}
			}
			else{
				// No se ha podido insertar
				$result->setCode(FALSE);
				$result->setStatus(CONFLICT);
				$result->setMessage("Login incorrect");
			}
		}
	} 
	catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/users/id/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUser($user_id); // Obtener todos los datos de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUser($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_lastname,User_image FROM User WHERE User_email = ?");
		$dbquery->bindParam(1, $user_email);
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
			$result->setMessage("Does the User exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/users/friends/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getFriends($user_id); // Obtener los amigos de un usuario
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getFriends($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_lastname,User_image FROM User WHERE User_email IN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ? )");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		$connection = null;

		if ($data != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setData($data);
		}	
		else {
			$result->setCode(FALSE);
			$result->setStatus(NOT_COMPLETED);
			$result->setMessage("Does the data exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->post("/api/users/register", function() use($app) {
	//get params
	$json = $app->request->post('user');
	$user = json_decode($json);

	$result = postUser($user->email, $user->password, $user->firstname); // Añadir un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function postUser($email, $password, $first) {
	$result = new Result();
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO User (User_email, User_password, User_firstname, User_apiKey) VALUES(?, sha1(?), ?, ?)");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $password);
		$dbquery->bindParam(3, $first);
		$dbquery->bindParam(4, generarApiKey($email)); // Genera Api Key para el usuario
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

$app->put("/api/users(/:apikey)", function($apikey=null) use($app) {
	//get params
	$json = $app->request->put('user');
	$user = json_decode($json);

	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = putUser($user->email, $user->firstname, $user->lastname, $user->image); // Modificar un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putUser($email, $first, $last, $image) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE User SET User_firstname = ?, User_lastname = ?, User_image = ? WHERE User_email = ?");
		$dbquery->bindParam(1, $first);
		$dbquery->bindParam(2, $last);
		$dbquery->bindParam(3, $image);
		$dbquery->bindParam(4, $email);
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

$app->delete("/api/users/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = deleteUSer($user_id); // Borrar un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function deleteUser($email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM User WHERE User_email = ?");
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

 ?>