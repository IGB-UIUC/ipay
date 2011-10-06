<?php
//////////////////////////////////
//
// startpayment.php
//
// Page used to start ipay payment system
//
//////////////////////////////////

include "includes/settings.inc.php";
include "includes/creditcard.inc.php";
include "includes/ipay.class.php";
include "includes/ipaydb.class.php";

$siteid = $creditCardSettings['siteid'];
$sendkey= $creditCardSettings['sendKey'];
$receivekey = $creditCardSettings['receiveKey'];
$debug = $creditCardSettings['debug'];

if (isset($_GET['registrationId']) && isset($_GET['total'])) {

	$registrationId = $_GET['registrationId'];
	$total = $_GET['total'];

	$amount = $total . ".00";
	echo "<h1>Your total for this transaction will be: $" . $amount . "</h1>";
	echo "<p>You will be forwarded to the University of Illinois payment center momentarily.</p>";
	echo "<p><b>Please have your credit card ready.</b></p>";

	$creditcard = new ipay($debug,$siteid,$sendkey,$receivekey);
	$time = $creditcard->getTime();

	$db = new ipaydb($mysqlSettings);
	$db->setTime($time);
	$referenceId = $db->startTransaction($amount,$registrationId);

	$registrationResult = $creditcard->register($referenceId,$amount);

	$responsecode = $registrationResult['ResponseCode'];
	$token= $registrationResult['Token'];
	$redirecturl = $registrationResult['Redirect'];
	$timeStamp = $registrationResult['TimeStamp'];
	$transactionId = $registrationResult['TransactionId'];
	$errorMsg = $registrationResult['ErrorMsg'];

	if ($responsecode != '0') { // Some sort of error has occurred...
		echo "<p>An error has occurred.  Response Code $responsecode: " . $responseCodeErrors[$responsecode] . "</p>";
		$db->error($responsecode,$errorMsg,$token);
	}
	else { // It worked successfully--send them on their way..

		$db->updateTransaction($token);
		echo "<meta http-equiv='refresh' content='5;url=$redirecturl?token=$token'>";
	}

}
?>
