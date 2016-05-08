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
		$dbquery = $connection->prepare("SELECT * FROM User".' WHERE User_email=? AND User_password=sha1(?)');
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

$app->post("/api/users/facebook", function() use($app) {
	$json = $app->request->post('user');
	$user = json_decode($json);

	$result = userFacebookLogin($user->email, $user->first, $user->last, $user->sex, $user->city, $user->image);
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function userFacebookLogin($email, $first, $last, $sex, $city, $image){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM User".' WHERE User_email=?');
		$dbquery->bindParam(1, $email);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$data = $dbquery->fetchObject();
		if($number > 0){
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("Login completed");
			$result->setData($data);
		}
		else{
			// Si no existe el usuario que ha hecho Login con Facebook, se inserta
			$dbquery = $connection->prepare("INSERT INTO User (User_email, User_firstname, User_lastname, User_image, User_sex, User_city, User_apiKey) VALUES(?, ?, ?, ?, ?, ?, ?)");
			$dbquery->bindParam(1, $email);
			$dbquery->bindParam(2, $first);
			$dbquery->bindParam(3, $last);
			$dbquery->bindParam(4, $image);
			$dbquery->bindParam(5, $sex);
			$dbquery->bindParam(6, $city);
			$dbquery->bindParam(7, generarApiKey($email)); // Genera Api Key para el usuario
			$dbquery->execute();
			$number = $dbquery->rowCount();
			if ($number > 0) {
				// Se devuelven los datos en $data del usuario insertado
				$dbquery = $connection->prepare("SELECT * FROM User".' WHERE User_email=?');
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

$app->get("/api/users/name/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUserName($user_id); // Obtener el nombre de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUserName($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_firstname,User_lastname FROM User WHERE User_email = ?");
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

$app->get("/api/users/search/:city/:name(/:apikey)", function($city, $name, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUsersByName($name, $city); // Buscar usuarios (prioridad en tu ciudad)
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUsersByName($name, $city) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM User WHERE concat_ws(' ', User_firstname, User_lastname) LIKE '%".$name."%' ORDER BY FIELD(User_city,'".$city."'), User_city");
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

	$result = postUser($user->email, $user->password, $user->firstname, $user->alternative_email); // Añadir un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function postUser($email, $password, $first, $alternative) {
	$result = new Result();
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO User (User_email, User_password, User_firstname, User_alternativeEmail, User_apiKey) VALUES(?, ?, ?, ?, ?)");
		$dbquery->bindParam(1, $email);
		$dbquery->bindParam(2, $password);
		$dbquery->bindParam(3, $first);
		$dbquery->bindParam(4, $alternative);
		$dbquery->bindParam(5, generarApiKey($email)); // Genera Api Key para el usuario
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
		$result = putUser($user->email, $user->firstname, $user->lastname, $user->sex, $user->age, $user->city, $user->weight, $user->height); // Modificar un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putUser($email, $first, $last, $sex, $age, $city, $weight, $height) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE User SET User_firstname = ?, User_lastname = ?, User_sex = ?, User_age = ?, User_city = ?, User_weight = ?, User_height = ? WHERE User_email = ?");
		$dbquery->bindParam(1, $first);
		$dbquery->bindParam(2, $last);
		$dbquery->bindParam(3, $sex);
		$dbquery->bindParam(4, $age);
		$dbquery->bindParam(5, $city);
		$dbquery->bindParam(6, $weight);
		$dbquery->bindParam(7, $height);
		$dbquery->bindParam(8, $email);
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

$app->put("/api/users/image(/:apikey)", function($apikey=null) use($app) {
	//get params
	$json = $app->request->put('user');
	$user = json_decode($json);

	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = putUserImage($user->email, $user->image); // Modificar un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putUserImage($email, $image) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE User SET User_image = ? WHERE User_email = ?");
		$dbquery->bindParam(1, $image);
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