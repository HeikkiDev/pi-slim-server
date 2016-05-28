<?php 

$app->get("/api/statistics/sports-percentage/:id/:year(/:apikey)", function($user_id, $year, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getSportsPercentageYear($user_id, $year); 
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getSportsPercentageYear($user_email, $year) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT COUNT(*) FROM Activity WHERE Activity_userEmail = ? AND YEAR(Activity_date) = ? INTO @SportCount;SELECT Activity_sportType AS SportType, (COUNT(*)/(@SportCount))*100 AS Percentage FROM Activity WHERE Activity_userEmail = ? AND YEAR(Activity_date) = ? GROUP BY Activity_sportType");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $year);
		$dbquery->bindParam(3, $user_email);
		$dbquery->bindParam(4, $year);
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
			$result->setMessage("Does the Statistics exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/statistics/sports-percentage/:id/:year/:month(/:apikey)", function($user_id, $year, $month, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getSportsPercentageMonth($user_id, $year, $month); 
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getSportsPercentageMonth($user_email, $year, $month) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT COUNT(*) FROM Activity WHERE Activity_userEmail = ? AND YEAR(Activity_date) = ? AND MONTH(Activity_date) = ? INTO @SportCount;SELECT Activity_sportType AS SportType, (COUNT(*)/(@SportCount))*100 AS Percentage FROM Activity WHERE Activity_userEmail = ? AND YEAR(Activity_date) = ? AND MONTH(Activity_date) = ? GROUP BY Activity_sportType");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $year);
		$dbquery->bindParam(3, $month);
		$dbquery->bindParam(4, $user_email);
		$dbquery->bindParam(5, $year);
		$dbquery->bindParam(6, $month);
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
			$result->setMessage("Does the Statistics exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/statistics/:id/:year(/:apikey)", function($user_id, $year, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getStatisticsYear($user_id, $year); // Obtener todos las Estadísticas para un Usuario y año concretos
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getStatisticsYear($user_email, $year) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT Statistics_month, Statistics_kms, Statistics_miles FROM Statistics WHERE Statistics_userEmail = ? AND Statistics_year = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $year);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		// Total de Calorias, Duración y Distancia de un año concreto
		$dbquery = $connection->prepare("SELECT sum(Statistics_kms) AS KmsTotal, sum(Statistics_miles) AS MilesTotal, sum(Statistics_totalTime) AS TimeTotal, sum(Statistics_calories) AS CaloriesTotal FROM Statistics WHERE Statistics_userEmail = ? AND Statistics_year =?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $year);
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
			$result->setMessage("Does the Statistics exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->get("/api/statistics/:id/:year/:month(/:apikey)", function($user_id, $year, $month, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getStatisticsMonth($user_id, $year); // Obtener todos las Estadísticas para un Usuario, mes y año concretos
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getStatisticsMonth($user_email, $year, $month) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT DAY(A.Activity_date) as Date, sum(A.Calories) as Calories, sum(A.Duration) as Duration, sum(A.DistanceKms) as DistanceKms FROM (SELECT Activity_date, Activity_calories AS Calories, Activity_duration AS Duration, CASE Activity_distanceUnits WHEN 0 THEN Activity_distance ELSE Activity_distance*0.621371 END AS DistanceKms FROM Activity WHERE YEAR(Activity_date)=? AND MONTH(Activity_date)=? AND Activity_userEmail=?) AS A GROUP BY DAY(Activity_date)");
		$dbquery->bindParam(1, $year);
		$dbquery->bindParam(2, $month);
		$dbquery->bindParam(3, $user_email);
		$dbquery->execute();
		$data = $dbquery->fetchAll(PDO::FETCH_ASSOC);

		// Total de Calorias, Duración y Distancia de un mes concreto
		$dbquery = $connection->prepare("SELECT sum(A.Calories) AS CaloriesTotal, sum(A.Duration) AS DurationTotal, sum(A.Distance) AS DistanceKmsTotal FROM (SELECT Activity_calories AS Calories, Activity_duration AS Duration, CASE Activity_distanceUnits WHEN 0 THEN Activity_distance ELSE Activity_distance*0.621371 END AS Distance FROM Activity WHERE MONTH(Activity_date)=? and YEAR(Activity_date)=? and Activity_userEmail=?) as A");
		$dbquery->bindParam(1, $month);
		$dbquery->bindParam(2, $year);
		$dbquery->bindParam(3, $user_email);
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
			$result->setMessage("Does the Statistics exist?");
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

 ?>