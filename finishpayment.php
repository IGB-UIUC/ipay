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
include_once "libs/db.class.inc.php";
include_once "libs/ipay.class.php";
include_once "libs/ipaydb.class.php";

if (!isset($_GET['token']) || $_GET['submit'] == "Return") {
	echo "<meta http-equiv='refresh' content='0;registration.php'>";
}
elseif (isset($_GET['token'])) { // token passed in GET data

	$token=$_GET['token'];

	$db = new db(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);
	$ipaydb = new ipaydb($db);

	$result = $ipaydb->get_transaction($token);

	if (count($result) == 0) { // invalid TOKEN--no rows returned from database

		// echo "<meta http-equiv='refresh' content='0;register.php'>";
	}
	else { // token is in database table.

		$status = $result[0]['ipay_status'];


		if ($status == "Pending") { // need to do the capture rigamarole....

			$ipay = new ipay(__DEBUG__,__SITE_ID__,__SEND_KEY__,__RECEIVE_KEY__);
			$results = $ipay->result($token);

			if ($results['ResponseCode']) { // Some sort of error has occurred...
				echo "<p>An error has occurred.  Response Code " . $results['ResponseCode'] . ": " . $result['ErrorMsg'] . "</p>";
				$ipaydb->error($results['ResponseCode'],$result['ErrorMsg'],$token);
			}
			else { // It worked successfully--send capture information...

				$amount = $ipaydb->get_payment_amount($token);
				$amounts = array($amount);
				$accounts  = array(__ACCOUNT__);
				$capture = $ipay->capture($accounts,$amounts);
				if ($capture['ResponseCode'] != 0) { // Some sort of error has occurred...
					echo "<p>An error has occurred.  Response Code " . $capture['ResponseCode'] . ": " . $capture['ErrorMsg'] . "</p>";
					$ipaydb->error($capture['ResponseCode'],$capture['ErrorMsg'],$token);

				}
				else { // It worked successfully--display receipt...

					$ipaydb->finalize_transaction($token,$capture['TransactionID'],__ACCOUNT__,$amount);

					header ('Location:thankyou.php?token=$token');
				}
			}
		}
	}

}
?>
