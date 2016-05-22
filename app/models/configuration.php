<?php 

$app->get("/api/configuration/:id/(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUserConfiguration($user_id); // Obtener la configuración de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUserConfiguration($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Configuration WHERE Configuration_userId = ?");
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
			$result->setMessage("Does the Configuration exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->post("/api/configuration(/:apikey)", function($apikey=null) use($app) {
	//get params
	$json = $app->request->post('userconf');
	$userconf = json_decode($json);

	if(comprobarApiKey($apikey))
		$result = postUserConfiguration($userconf->email, $userconf->geosearch, $userconf->privacity, $userconf->geolat, $userconf->geolon, $userconf->sporttype, $userconf->privacitylat, $userconf->privacitylon, $userconf->chatnotifications, $userconf->friendsnotification); // Añadir una Configuración para un Usuario
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function postUserConfiguration($email, $geosearch, $geolat, $geolon, $sporttype, $privacitylat, $privacitylon, $chatnotifications, $friendsnotification) {
	$result = new Result();
	try {	
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Configuration WHERE Configuration_userId = ?");
		$dbquery->bindParam(1, $email);
		$dbquery->execute();
		$number = $dbquery->rowCount();

		if ($number > 0) {
			$dbquery = $connection->prepare("UPDATE Configuration SET Configuration_geoSearch = ?, Configuration_geoLat = ?, Configuration_geoLon = ?, Configuration_sportType = ?, Configuration_privacityLat = ?, Configuration_privacityLon = ?, Configuration_chatNotifications = ?, Configuration_friendsNotifications = ? WHERE Configuration_userId = ?");
			$dbquery->bindParam(1, $geosearch);
			$dbquery->bindParam(2, $geolat);
			$dbquery->bindParam(3, $geolon);
			$dbquery->bindParam(4, $sporttype);
			$dbquery->bindParam(5, $privacitylat);
			$dbquery->bindParam(6, $privacitylon);
			$dbquery->bindParam(7, $chatnotifications);
			$dbquery->bindParam(8, $friendsnotification);
			$dbquery->bindParam(9, $email);
			$dbquery->execute();
			$number = $dbquery->rowCount();
		}
		else{
			$dbquery = $connection->prepare("INSERT INTO Configuration (Configuration_userId, Configuration_geoSearch, Configuration_geoLat, Configuration_geoLon, Configuration_sportType, Configuration_privacityLat, Configuration_privacityLon, Configuration_chatNotifications, Configuration_friendsNotifications) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$dbquery->bindParam(1, $email);
			$dbquery->bindParam(2, $geosearch);
			$dbquery->bindParam(3, $geolat);
			$dbquery->bindParam(4, $geolon);
			$dbquery->bindParam(5, $sporttype);
			$dbquery->bindParam(6, $privacitylat);
			$dbquery->bindParam(7, $privacitylon);
			$dbquery->bindParam(8, $chatnotifications);
			$dbquery->bindParam(9, $friendsnotification);
			$dbquery->execute();
			$number = $dbquery->rowCount();
		}

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

 ?>