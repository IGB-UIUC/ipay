<?php
//////////////////////////////////
//
// startpayment.php
//
// Page used to start ipay payment system
//
//////////////////////////////////

include_once "includes/settings.inc.php";
include_once "libs/db.class.inc.php";
include_once "libs/ipay.class.php";
include_once "libs/ipaydb.class.php";


if (isset($_GET['registration_id']) && isset($_GET['total'])) {

	$registration_id = $_GET['registration_id'];
	$total = $_GET['total'];

	$amount = $total . ".00";
	echo "<h1>Your total for this transaction will be: $" . $amount . "</h1>";
	echo "<p>You will be forwarded to the University of Illinois payment center momentarily.</p>";
	echo "<p><b>Please have your credit card ready.</b></p>";

	$ipay = new ipay(__DEBUG__,__SITE_ID__,__SEND_KEY__,__RECEIVE_KEY__);

	$db = new db(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);
	$ipaydb = new ipaydb($db);
	$ipay_id = $ipaydb->start_transaction($amount,$registration_id);

	$result = $ipay->register($ipay_id,$amount);

	if ($result['ResponseCode']) { // Some sort of error has occurred...
		echo "<p>An error has occurred.  Response Code " . $result['ResponseCode'] . ": " . $result['ErrorMsg'] . "</p>";
		$ipaydb->error($result['ResponseCode'],$result['ErrorMsg'],$result['Token']);
	}
	else { // It worked successfully--send them on their way..
		$ipaydb->update_transaction($result['Token']);
		echo "<meta http-equiv='refresh' content='5;url=" . $result['Redirect'] . "?token=" . $result['Token'] . "'>";
	}

}
?>
