<?php 

$app->get("/api/chats/:id(/:apikey)", function($user_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = getUserChats($user_id); // Obtener todos los Chats de un User
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function getUserChats($user_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT Chat_id, Chat_receiver, User_image, concat_ws(' ',User_firstname, User_lastname) as Username from User inner join Chat on User_email = Chat_receiver where Chat_me = ?");
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

$app->post("/api/chats/:id/:receiverId(/:apikey)", function($user_id, $receiver_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = newChat($user_id, $receiver_id); // Inserta un nuevo Chat
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function newChat($user_email, $receiver_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM Chat WHERE Chat_me = ? AND Chat_receiver = ?");
		$dbquery->bindParam(1, $user_email);
		$dbquery->bindParam(2, $receiver_email);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		if ($number < 1) {
			// Aún no existe este Chat
			$dbquery = $connection->prepare("INSERT INTO Chat (Chat_me, Chat_receiver) values(?,?)");
			$dbquery->bindParam(1, $user_email);
			$dbquery->bindParam(2, $receiver_email);
			$dbquery->execute();
			$number = $dbquery->rowCount();
			if ($number > 0) {
				// Recupero el Id autoincremental que se la ha asignado
				$dbquery = $connection->prepare("SELECT Chat_id FROM Chat WHERE Chat_me = ? AND Chat_receiver = ?");
				$dbquery->bindParam(1, $user_email);
				$dbquery->bindParam(2, $receiver_email);
				$dbquery->execute();
				$chat_id = $dbquery->fetchColumn();

				// Inserto el mismo chat de forma inversa para que el otro usuario pueda verlo
				$dbquery = $connection->prepare("INSERT INTO Chat (Chat_id, Chat_me, Chat_receiver) values(?,?,?)");
				$dbquery->bindParam(1, $chat_id);
				$dbquery->bindParam(2, $receiver_email);
				$dbquery->bindParam(3, $user_email);
				$dbquery->execute();
				$number = $dbquery->rowCount();
				$connection = null;
				if ($number > 0) {
					$result->setCode(TRUE);
					$result->setStatus(OK);
					$result->setData($chat_id);
				}	
				else {	
					$result->setCode(FALSE);
					$result->setStatus(NOT_COMPLETED);
					$result->setMessage("NOT INSERTED");
				}
			}	
			else {	
				$result->setCode(FALSE);
				$result->setStatus(NOT_COMPLETED);
				$result->setMessage("NOT INSERTED");
			}
		}
		else{
			// Recupero el Id
			$dbquery = $connection->prepare("SELECT Chat_id FROM Chat WHERE Chat_me = ? AND Chat_receiver = ?");
			$dbquery->bindParam(1, $user_email);
			$dbquery->bindParam(2, $receiver_email);
			$dbquery->execute();
			$chat_id = $dbquery->fetchColumn();

			$result->setCode(TRUE);
			$result->setStatus(OK);
			$result->setMessage("ALREADY EXISTS");
			$result->setData($chat_id);
		}
	} catch (PDOException $e) {
		$result->setCode(FALSE);
		$result->setStatus(CONFLICT);
		$result->setMessage("Error: " . $e->getMessage());
	}
	return $result;
}

$app->delete("/api/chats/:id/:receiverId(/:apikey)", function($user_id, $receiver_id, $apikey=null) use($app) {
	$result = new Result();
	$result->setCode(FALSE);
	$result->setStatus(CONFLICT);
	$result->setMessage("Invalid Api Key!!");
	if(comprobarApiKey($apikey))
		$result = deleteChat($user_id, $receiver_id); // Borrar un Friend
	$app->response->status($result->getStatus());
	$app->response->body(json_encode($result));
});

function deleteChat($user_email, $receiver_email) {
	$result = new Result();
	try {
		$connection = getConnection();
		$dbquery = $connection->prepare("DELETE FROM Chat WHERE Chat_me = ? AND Chat_receiver = ?");
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