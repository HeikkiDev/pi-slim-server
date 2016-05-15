<?php 

$app->get("/api/friends/following/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
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
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_lastname,User_city FROM User WHERE User_email IN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ? ) ORDER BY User_firstname");
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

$app->get("/api/friends/followers/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getFollowers($user_id); // Obtener los followers de un usuario
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getFollowers($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT User_email,User_firstname,User_lastname,User_city FROM User WHERE User_email IN (SELECT Friend_userId FROM Friend WHERE Friend_friendId = ? ) ORDER BY User_firstname");
		$dbquery->bindParam(1, $user_email);
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

$app->get("/api/friends/follows_counter/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getFollowsCounter($user_id); // Obtener el número de followers/following
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getFollowsCounter($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT count(*) AS Counter FROM Friend WHERE Friend_userId = ? UNION ALL SELECT count(*) AS Counter FROM Friend WHERE Friend_friendId = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $user_email);
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

$app->post("/api/friends/:id/:friendId(/:apikey)", function($user_id, $friend_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = newFriend($user_id, $friend_id); // Inserta un nuevo amigo
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function newFriend($user_email, $friend_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO Friend values(?,?)");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $friend_email);
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

$app->delete("/api/friends/:id/:friendId(/:apikey)", function($user_id, $friend_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = deleteFriend($user_id, $friend_id); // Borrar un Friend
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function deleteFriend($user_email, $friend_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM Friend WHERE Friend_userId = ? AND Friend_friendId = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $friend_email);
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