<?php 

$app->get("/api/activity/id/:id(/:apikey)", function($activity_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getActivity($activity_id); // Obtener todos los datos de una Acttivity
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getActivity($activity_id) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Activity WHERE Activity_id = ?");
		$dbquery->bindParam(1, $activity_id);
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
			$result->setMessage("Does the Activity exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/activity/:email/:page(/:apikey)", function($user_email, $page, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUserActivities($user_email, $page); // Obtener todos las Activities de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUserActivities($user_email, $page) {
	$limit = $page * 10;
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Activity WHERE Activity_userEmail = ? ORDER BY Activity_date DESC LIMIT ".$limit.", 10");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		$dbquery = $connection->prepare("SELECT CEILING(count(*)/10) AS TotalPages FROM Activity WHERE Activity_userEmail = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data_aux = $dbquery->fetchObject();
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
			$result->setMessage("Does the User Activities exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/activity/friends/:email/:page(/:apikey)", function($user_email, $page, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getFriendsActivities($user_email, $page); // Obtener todos las Activities de los amigos de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getFriendsActivities($user_email, $page) {
	$limit = $page * 10;
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Activity INNER JOIN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ?) F ON Activity_userEmail = F.Friend_friendId  ORDER BY Activity_date DESC LIMIT ".$limit.", 10");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		$dbquery = $connection->prepare("SELECT CEILING(count(*)/10) AS TotalPages FROM Activity WHERE Activity_userEmail IN (SELECT Friend_friendId FROM Friend WHERE Friend_userId = ?)");
		$dbquery->bindParam(1, $user_email);
		$dbquery->execute();
		$data_aux = $dbquery->fetchObject();
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
			$result->setMessage("Does the Friends Activities exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->post("/api/activity/new(/:apikey)", function($apikey=null) use($app) {
	//get params
	$json = $app->request->post('sport_data');
	$activity = json_decode($json);

	$result = postActivity($activity->email, $activity->sportType, $activity->typeName, $activity->distanceUnits, $activity->speedUnits, $activity->avgSpeed, $activity->distance, $activity->duration, $activity->calories, $activity->geo_points); // Añadir una nueva Acticity
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function postActivity($email, $type, $typeName, $distanceUnits, $speedUnits, $avgSpeed, $distance, $duration, $calories, $geo_points) {
	$result = new Result();
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("INSERT INTO Activity (Activity_userEmail, Activity_name, Activity_date, Activity_avSpeed, Activity_calories, Activity_duration, Activity_distance, Activity_sportType, Activity_distanceUnits, Activity_speedUnits, Activity_geoPoints) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$dbquery->bindParam(1, $email);
		$date = new DateTime();
		$name = $typeName.';'.$date->format('H:i');
		$dbquery->bindParam(2, $name);
		$dbquery->bindParam(3, $date->format('Y-m-d H:i:s'));
		$dbquery->bindParam(4, $avgSpeed);
		$dbquery->bindParam(5, $calories);
		$dbquery->bindParam(6, $duration);
		$dbquery->bindParam(7, $distance);
		$dbquery->bindParam(8, $type);
		$dbquery->bindParam(9, $distanceUnits);
		$dbquery->bindParam(10, $speedUnits);
		$dbquery->bindParam(11, $geo_points);
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

$app->put("/api/activity/name/:id/:name(/:apikey)", function($id,$name,$apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = putActivityName($id,$name); // Modificar el nombre de un Activity
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function putActivityName($id,$name) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("UPDATE Activity SET Activity_name = ? WHERE activity_id = ?");
		$dbquery->bindParam(1, $name);
		$dbquery->bindParam(2, $id);
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

$app->delete("/api/activity/:id(/:apikey)", function($activity_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = deleteActivity($activity_id); // Borrar un Activity
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function deleteActivity($activity_id) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM Activity WHERE Activity_id = ?");
		$dbquery->bindParam(1, $activity_id);
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