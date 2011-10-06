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

include_once "settings.inc.php";
include_once "libs/ipay.class.php";
include_once "libs/ipaydb.class.php";

if (!isset($_GET['token']) || $_GET['submit'] == "Return") {
	echo "<meta http-equiv='refresh' content='0;registration.php'>";
}
elseif (isset($_GET['token'])) { // token passed in GET data

	$token=$_GET['token'];

	$db = new ipaydb(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);;

	$result = $db->get_transaction($token);

	if (count($result) == 0) { // invalid TOKEN--no rows returned from database

		// echo "<meta http-equiv='refresh' content='0;register.php'>";
	}
	else { // token is in database table.

		$status = $result[0]['ipay_status'];


		if ($status == "Pending") { // need to do the capture rigamarole....

			$ipay = new ipay(__DEBUG__,__SITE_ID__,__SEND_KEY__,__RECEIVE_KEY__);
			$time = $ipay->get_time();
			$db->set_time($time);
			$resultResults = $ipay->result($token);
			$responsecode = $resultResults['ResponseCode'];
			$timestamp = $registrationResult['TimeStamp'];
			$transactionId = $registrationResult['TransactionID'];
			$errorMsg = $registrationResult['ErrorMsg'];

			if ($responsecode != 0) { // Some sort of error has occurred...
				echo "<p>An error has occurred.  Response Code " . $responsecode . ": " . $errorMsg . "</p>";
				$db->error($responsecode,$errorMsg,$token);
			}
			else { // It worked successfully--send capture information...

				$amount1 = $db->get_payment_amount($token);

				$amounts = array($amount1);
				$accounts  = array($account1);
				$capture = $ipay->capture($accounts,$amounts);

				$responsecode = $capture['ResponseCode'];
				$timestamp = $capture['TimeStamp'];
				$errorMsg = $capture['ErrorMsg'];
				$transactionId = $capture['TransactionID'];

				if ($responsecode != 0) { // Some sort of error has occurred...
					echo "<p>An error has occurred.  Response Code " . $responsecode . ": " . $errorMsg . "</p>";
					$db->error($responsecode,$errorMsg,$token);

				}
				else { // It worked successfully--display receipt...

					$db->finalize_transaction($token,$transactionId,$account1,$amount1);

					header ("Location:thankyou.php?token=$token");
				}
			}
		}
	}

}
?>
