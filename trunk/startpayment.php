<?php
//////////////////////////////////
//
// startpayment.php
//
// Page used to start ipay payment system
//
//////////////////////////////////

include "settings.inc.php";
include "libs/ipay.class.php";
include "libs/ipaydb.class.php";

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

	$creditcard = new ipay(__DEBUG__,__SITE_ID__,__SEND_KEY__,__RECEIVE_KEY__);
	$time = $creditcard->get_time();

	$db = new ipaydb(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);
	$db->set_time($time);
	$referenceId = $db->start_transaction($amount,$registrationId);

	$result = $creditcard->register($referenceId,$amount);

	$responsecode = $result['ResponseCode'];
	$token= $result['Token'];
	$redirecturl = $result['Redirect'];
	$timeStamp = $result['TimeStamp'];
	$transactionId = $result['TransactionId'];
	$errorMsg = $result['ErrorMsg'];

	if ($responsecode != '0') { // Some sort of error has occurred...
		echo "<p>An error has occurred.  Response Code " . $responsecode . ": " . $errorMsg . "</p>";
		$db->error($responsecode,$errorMsg,$token);
	}
	else { // It worked successfully--send them on their way..

		$db->updateTransaction($token);
		echo "<meta http-equiv='refresh' content='5;url=$redirecturl?token=$token'>";
	}

}
?>
