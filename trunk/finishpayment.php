<?php
//////////////////////////////////
//
// finishpayment.php
// Using PHP to interact with the campus credit card services
//
// This page presumes that certain GET data is returned from the credit card
//   server ($token).
//
//////////////////////////////////

include "includes/settings.inc.php";
include "includes/creditcard.inc.php";
include "includes/ipay.class.php";
include "includes/ipaydb.class.php";

//Site Settings from includes/settings.inc.php
$siteid = $creditCardSettings['siteid'];
$sendkey= $creditCardSettings['sendKey'];
$receivekey = $creditCardSettings['receiveKey'];
$debug = $creditCardSettings['debug'];
$account1 = $creditCardSettings['account1'];

if (!isset($_GET['token']) || $_GET['submit'] == "Return") {
	echo "<meta http-equiv='refresh' content='0;registration.php'>";
}
elseif (isset($_GET['token'])) { // token passed in GET data

	$token=$_GET['token'];

	$db = new ipaydb($mysqlSettings);

	$result = $db->getTransaction($token);

	if (count($result) == 0) { // invalid TOKEN--no rows returned from database

		// echo "<meta http-equiv='refresh' content='0;register.php'>";
	}
	else { // token is in database table.

		$status = $result[0]['status_name'];


		if ($status == "Pending") { // need to do the capture rigamarole....

			$creditcard = new ipay($debug,$siteid,$sendkey,$receivekey);
			$time = $creditcard->getTime();
			$db->setTime($time);
			$resultResults = $creditcard->result($token);
			$responsecode = $resultResults['ResponseCode'];
			$timestamp = $registrationResult['TimeStamp'];
			$transactionId = $registrationResult['TransactionID'];
			$errorMsg = $registrationResult['ErrorMsg'];

			if ($responsecode != 0) { // Some sort of error has occurred...
				echo "<p>An error has occurred.  Response Code $responsecode: " . $errorMsg . "</p>";
				$db->error($responsecode,$errorMsg,$token);
			}
			else { // It worked successfully--send capture information...

				$amount1 = $db->getPaymentAmount($token);

				$amounts = array($amount1);
				$accounts  = array($account1);
				$capture = $creditcard->capture($accounts,$amounts);

				$responsecode = $capture['ResponseCode'];
				$timestamp = $capture['TimeStamp'];
				$errorMsg = $capture['ErrorMsg'];
				$transactionId = $capture['TransactionID'];

				if ($responsecode != 0) { // Some sort of error has occurred...
					echo "<p>An error has occurred.  Response Code $responsecode: " . $errorMsg . "</p>";
					$db->error($responsecode,$errorMsg,$token);

				}
				else { // It worked successfully--display receipt...

					$db->finalizeTransaction($token,$transactionId,$account1,$amount1);

					header ("Location:thankyou.php?token=$token");
				}
			}
		}
	}

}
?>
