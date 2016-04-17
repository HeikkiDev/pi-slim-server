<?php
if (!defined("SPECIALCONSTANT"))
	die("Acceso denegado");

function generarApiKey($username){
	$apiKey = sha1($username + microtime().rand());
	return $apiKey;
}

function comprobarApiKey($apikey){
	try{
		$connection = getConnection();
		$dbquery = $connection->prepare("SELECT * FROM User WHERE User_apiKey = ?");
		$dbquery->bindParam(1,$apikey);
		$dbquery->execute();
		$number = $dbquery->rowCount();
		$connection = null;
		if($number > 0){
			return TRUE;
		}
		else{
			return FALSE;
		}
	} catch (PDOException $e) {
		return FALSE;
	}
}

$app->get("/", function() use($app) {
	$app->response->headers->set("Content-type", "text/html; charset=utf-8");
	$app->response->status(OK);
	$app->response->body(
	"<h1>RESTful API OSport Hello</h1><h2>Enrique Ramos</h2>
	<a href ='doc/index.html'>API Documentation</a>");
});

$app->get("/restore-password/:email/:code", function($userEmail, $code) use($app) {
	include_once("models/verification.php");
	$checked = checkCodePassword($userEmail, $code);
	if($checked->getCode() == TRUE){
		$app->response->headers->set("Content-type", "text/html; charset=utf-8");
		$app->response->status(OK);
		$app->response->body(
		'<h1>Formulario de cambio de contraseña!!</h1>

		 <form action="http://www.enriqueramos.info/osporthello/api/verification/restore-password/'.$userEmail.'/'.$code.'" method="get">
  			New password:<br>
  			<input type="password" name="password1" value=""><br>
  			Repeat new password:<br>
  			<input type="password" name="password2" value=""><br><br>
  			<input type="submit" value="Change password">
		</form> 
		');
	}
	else{
		$app->response->headers->set("Content-type", "text/html; charset=utf-8");
		$app->response->status(OK);
		$app->response->body('<h2>No se ha podido comprobar tu petición de cambio de contraseña</h2>');
	}
});

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// USER 	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/user.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ACTIVITY /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/activity.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ACTIVITY TROPHIES /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/activitytrophies.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ATTENDANT /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/attendant.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CHAT 	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/chat.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// COMMENT /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/comment.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CONFIGURATION /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/configuration.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// EVENT /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/event.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FRIEND /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/friend.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// PHOTO /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/photo.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// POINT /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/point.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// STATISTICS /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/statistics.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// TROPHIE /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/trophie.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// USER TROPHIES /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/usertrophies.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VERIFICATION /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
include_once("models/verification.php");

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// EMAIL /////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//

$app->get("/api/restore-password/:email", function($userEmail) use($app) {
	$code = generarApiKey($userEmail+"verification_code");

	$inserted = insertVerificationPassword($userEmail, $code); // Añadir un User a Verification

	if($inserted == TRUE){
		$result = sendPasswordEmail($userEmail, $code); // Enviar mail de cambio de password
	}
	else {
		$result = new Result();
		$result->setCode(FALSE);
		$result->setMessage("Error sending restore password email");
	}
	
	$app->response->status($result->getCode());
	$app->response->body(json_encode($result));
});

function sendPasswordEmail($emailAddress, $code){
	header('Content-type: application/json;charset=utf8');
	require_once('phpmailer524/class.phpmailer.php');
	require_once "config.php";
	$response = new Result();

	$mail = new PHPMailer(true); // true param means it will throw exceptions on errors
	$mail->IsSMTP(); // telling the class to use SMTP
	try {
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "tls";
		$mail->Host = "smtp.gmail.com"; // sets GMAIL as the SMTP server
		$mail->Port = 587; // set the SMTP port for the GMAIL server
		$mail->Username = "osporthello@gmail.com"; // GMAIL username
		$mail->Password = "ies29700412"; // GMAIL password
		$mail->AddAddress($emailAddress); // Receiver email
		$mail->SetFrom("osporthello@gmail.com"); // email sender
		$mail->Subject = "Restore your password in OSport Hello"; // subject of the message
		//content in HTML
		$mail->MsgHTML(htmlRestorePasswordButton($emailAddress, $code));
		//Alternative plain text content (if email client blocks html content)
		$mail->AltBody = 'Copy this link in your browser to restore your password: https://enriqueramos.info/osporthello/api/restore-password/'.$emailAddress.'/'.$code;
		// Send email!
		$mail->Send();
		$response->setCode(TRUE);
		$response->setMessage("Message Sent OK to " . $emailAddress);
	}	 catch (phpmailerException $e) {
		$response->setCode(FALSE);
		$response->setMessage("Error: " . $e->errorMessage());
	} catch (Exception $e) {
		$response->setCode(FALSE);
		$response->setMessage("Error: " . $e->getMessage());
	}

	return $response;
}

function htmlRestorePasswordButton($email, $code){
	$htmlC = 
	'Click this button to go to restore password form:
	<table width="100%">
    <tr>
      <td>
        <table border="0" cellpadding="0" cellspacing="0"> 
          <tr>
            <td height="20" width="100%" style="font-size: 20px; line-height: 20px;">
            &nbsp;
            </td>
          </tr>
        </table>
        <table border="0" align="left" cellpadding="0" cellspacing="0">
          <tbody>
          <tr>
            <td align="left">
              <table border="0" cellpadding="0" cellspacing="0" width="150">
                <tr>
                  <td align="center" bgcolor="#43A047" width="150" style="-moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px;">
                    <a href="https://enriqueramos.info/osporthello/restore-password/'.$email.'/'.$code.'" 
                    style="padding: 10px;width:150px;display: block;text-decoration: none;border:0;text-align: center;font-weight: bold;font-size: 15px;font-family: sans-serif;color: #ffffff;background: #43A047;border: 1px solid #43A047;-moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px;line-height:17px;" class="button_link">
                    Go to restore password form
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0"> 
          <tr>
            <td height="20" width="100%" style="font-size: 20px; line-height: 20px;">
            &nbsp;
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>';
  return $htmlC;
}

$app->post("/api/registration", function() use($app) {
	//get params
	$json = $app->request->post('user');
	$user = json_decode($json);
	$code = generarApiKey($user->email+"verification_code");

	$inserted = postVerificationUser($user->email, $user->password, $user->firstname, $user->alternative_email, $code); // Añadir un User a Verification

	if($inserted == 1){
		$result = sendConfirmationEmail($user->email, $code); // Enviar mail de confirmación de registro
	}
	if($inserted == 2){
		$result = new Result();
		$result->setCode(FALSE);
		$result->setMessage("User already exists");
	}
	if($inserted == 3){
		$result = new Result();
		$result->setCode(FALSE);
		$result->setMessage("Error sending confirmation email");
	}
	
	$app->response->status($result->getCode());
	$app->response->body(json_encode($result));
});

function sendConfirmationEmail($emailAddress, $code){
	header('Content-type: application/json;charset=utf8');
	require_once('phpmailer524/class.phpmailer.php');
	require_once "config.php";
	$response = new Result();

	$mail = new PHPMailer(true); // true param means it will throw exceptions on errors
	$mail->IsSMTP(); // telling the class to use SMTP
	try {
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "tls";
		$mail->Host = "smtp.gmail.com"; // sets GMAIL as the SMTP server
		$mail->Port = 587; // set the SMTP port for the GMAIL server
		$mail->Username = "osporthello@gmail.com"; // GMAIL username
		$mail->Password = "ies29700412"; // GMAIL password
		$mail->AddAddress($emailAddress); // Receiver email
		$mail->SetFrom("osporthello@gmail.com"); // email sender
		$mail->Subject = "Confirm registration in OSport Hello"; // subject of the message
		//content in HTML
		$mail->MsgHTML(htmlConfirmButton($emailAddress, $code));
		//Alternative plain text content (if email client blocks html content)
		$mail->AltBody = 'Copy this link in your browser to confirm registratiton: https://enriqueramos.info/osporthello/api/verification/'.$emailAddress.'/'.$code;
		// Send email!
		$mail->Send();
		$response->setCode(TRUE);
		$response->setMessage("Message Sent OK to " . $emailAddress);
	}	 catch (phpmailerException $e) {
		$response->setCode(FALSE);
		$response->setMessage("Error: " . $e->errorMessage());
	} catch (Exception $e) {
		$response->setCode(FALSE);
		$response->setMessage("Error: " . $e->getMessage());
	}

	return $response;
}

function htmlConfirmButton($email, $code){
	$htmlC = 
	'Click this button to confirm your registration:
	<table width="100%">
    <tr>
      <td>
        <table border="0" cellpadding="0" cellspacing="0"> 
          <tr>
            <td height="20" width="100%" style="font-size: 20px; line-height: 20px;">
            &nbsp;
            </td>
          </tr>
        </table>
        <table border="0" align="left" cellpadding="0" cellspacing="0">
          <tbody>
          <tr>
            <td align="left">
              <table border="0" cellpadding="0" cellspacing="0" width="150">
                <tr>
                  <td align="center" bgcolor="#43A047" width="150" style="-moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px;">
                    <a href="https://enriqueramos.info/osporthello/api/verification/'.$email.'/'.$code.'" 
                    style="padding: 10px;width:150px;display: block;text-decoration: none;border:0;text-align: center;font-weight: bold;font-size: 15px;font-family: sans-serif;color: #ffffff;background: #43A047;border: 1px solid #43A047;-moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px;line-height:17px;" class="button_link">
                    Confirm registration
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0"> 
          <tr>
            <td height="20" width="100%" style="font-size: 20px; line-height: 20px;">
            &nbsp;
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>';
  return $htmlC;
}

?>