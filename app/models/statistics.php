<?php 

$app->get("/api/statistics/:id/:year/:month(/:apikey)", function($user_id, $year, $month, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getStatistics($user_id, $year, $month); // Obtener todos las Estadísticas para un Usuario, mes y año concretos
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getStatistics($user_email, $year, $month) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT Statistics_userEmail,Statistics_year,Statistics_month,Statistics_kms, Statistics_miles, Statistics_totalTime, Statistics_calories FROM Statistics WHERE Statistics_userEmail = ? AND Statistics_year = ? AND Statistics_month = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $year);
		$dbquery->bindParam(3, $month);
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