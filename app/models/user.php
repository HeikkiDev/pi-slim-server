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

		$dbquery = $connection->prepare("SELECT * FROM Configuration WHERE Configuration_userId = ?");
		$dbquery->bindParam(1, $email);
		$dbquery->execute();
		$data_aux = $dbquery->fetchObject();
		$connection = null;

		if($number > 0){
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("Login completed");
			$result->setData($data);
			$result->setData_Aux($data_aux);
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

	$result = userFacebookLogin($user->email, $user->first, $user->last, $user->city, $user->image);
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function userFacebookLogin($email, $first, $last, $city, $image){
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM User".' WHERE User_email=?');
		$dbquery->bindParam(1, $email);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$data = $dbquery->fetchObject();
		if($number > 0){
			$dbquery = $connection->prepare("SELECT * FROM Configuration WHERE Configuration_userId = ?");
			$dbquery->bindParam(1, $email);
			$dbquery->execute();
			$data_aux = $dbquery->fetchObject();

			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("Login completed");
			$result->setData($data);
			$result->setData_Aux($data_aux);
		}
		else{
			// Si no existe el usuario que ha hecho Login con Facebook, se inserta
			$dbquery = $connection->prepare("INSERT INTO User (User_email, User_firstname, User_lastname, User_image, User_city, User_apiKey) VALUES(?, ?, ?, ?, ?, ?)");
			$dbquery->bindParam(1, $email);
			$dbquery->bindParam(2, $first);
			$dbquery->bindParam(3, $last);
			$dbquery->bindParam(4, $image);
			$dbquery->bindParam(5, $city);
			$dbquery->bindParam(6, generarApiKey($email)); // Genera Api Key para el usuario
			$dbquery->execute();
			$number = $dbquery->rowCount();
			if ($number > 0) {
				// Se devuelven los datos en $data del usuario insertado
				$dbquery = $connection->prepare("SELECT * FROM User".' WHERE User_email=?');
				$dbquery->bindParam(1, $email);
				$dbquery->execute();
				$number = $dbquery->rowCount();
				$data = $dbquery->fetchObject();

				$dbquery = $connection->prepare("SELECT * FROM Configuration WHERE Configuration_userId = ?");
				$dbquery->bindParam(1, $email);
				$dbquery->execute();
				$data_aux = $dbquery->fetchObject();
				$connection = null;
				if($number > 0){
					$result->setCode(TRUE);
					$result->setStatus(OK);
					$result->setMessage("Login completed");
					$result->setData($data);
					$result->setData_Aux($data_aux);
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

$app->get("/api/users/name-image/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUserNameImage($user_id); // Obtener el nombre e imagen de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUserNameImage($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_firstname,User_lastname, User_image FROM User WHERE User_email = ?");
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

$app->get("/api/users/search/:city/:name/:email(/:apikey)", function($city, $name, $user_email, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUsersByName($name, $city, $user_email); // Buscar usuarios (prioridad en tu ciudad)
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUsersByName($name, $city, $user_email) {
	$result = new Result();
	try {
		if($city == "null")
			$city = "";
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_lastname,User_city FROM User WHERE concat_ws(' ', User_firstname, User_lastname) LIKE '%".$name."%' ORDER BY FIELD(User_city,'".$city."') DESC, User_city");
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		$dbquery = $connection->prepare("SELECT User_email FROM User WHERE User_email IN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ? ) ORDER BY User_firstname");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data_aux = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		$connection = null;

		if ($data != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setData($data);
			$result->setData_Aux($data_aux);
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

$app->get("/api/users/geosearch/:email/:units/:sport(/:apikey)", function($user_email, $units, $sport, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUsersByProximity($user_email, $units, $sport); // Buscar usuarios por proximidad
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUsersByProximity($user_email, $units, $sport) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("CALL `GeoSearch` (?, ?, ?)");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $units);
		$dbquery->bindParam(3, $sport);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		$dbquery = $connection->prepare("SELECT User_email FROM User WHERE User_email IN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ? ) ORDER BY User_firstname");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data_aux = $dbquery->fetchAll(PDO::FETCH_ASSOC);
		$connection = null;

		if ($data != null) {
			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setData($data);
			$result->setData_Aux($data_aux);
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
		$result = putUser($user->email, $user->firstname, $user->lastname, $user->age, $user->city, $user->weight, $user->height); // Modificar un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putUser($email, $first, $last, $age, $city, $weight, $height) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE User SET User_firstname = ?, User_lastname = ?, User_age = ?, User_city = ?, User_weight = ?, User_height = ? WHERE User_email = ?");
		$dbquery->bindParam(1, $first);
		$dbquery->bindParam(2, $last);
		$dbquery->bindParam(3, $age);
		$dbquery->bindParam(4, $city);
		$dbquery->bindParam(5, $weight);
		$dbquery->bindParam(6, $height);
		$dbquery->bindParam(7, $email);
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