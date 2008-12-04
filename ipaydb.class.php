<?php
//////////////////////////////////////////
//										//
//	ipaydb.class.php					//
//										//
//	Class to save ipay information		//
//	to a database						//
//	By: David Slater					//
//	Date: 06/2008						//
//										//
//////////////////////////////////////////


class ipaydb {

///////Private Variables//////////
	private $db;
	private $uniqueId;
	private $time;
	private $referenceId;
	
/////////////////////////////Public Functions////////////////////////////////
	
	//__construct() - constucts ipaydb object
	//parameters - 
	//				$mysqlSettings - an array to connect to mysql database
	//returns - none
	public function __construct($mysqlSettings) {
		//Connects to database.
		$this->db = mysql_connect($mysqlSettings['host'],$mysqlSettings['username'],$mysqlSettings['password']);
		@mysql_select_db($mysqlSettings['database'],$this->db) or die("<br>Error. Unable to select database. " . mysql_error($this->db));
		
		
	}
	
	//__destruct()
	public function __destruct() {

		mysql_close($this->db);

	}
	public function setTime($inTime) {
	
		$this->time = $inTime;
	
	}
	//startTransaction() - creates initial entries into database
	//parameters -
	//			$amount - the amount to be charge
	//returns - unique id from the new row in the database
	public function startTransaction($amount,$inUniqueId) {
	
		$statusId = $this->getStatusId("Pending");
		
		$query = "INSERT INTO tbl_creditcard(creditcard_paymentAmount,creditcard_statusId,creditcard_payDate,creditcard_registrationId)" .
		"VALUES ($amount,$statusId,'" . $this->time . "',$inUniqueId)";
		
		@mysql_query($query,$this->db) or die ("<br>Error. Unable to start transaction. " . mysql_error($this->db));
		$this->referenceId = mysql_insert_id($this->db);
		$this->uniqueId = $inUniqueId;
		$this->time = $inTime;
		return $this->referenceId;
	
	}
	
	//updateTransaction() - updates database with token receieved from ipay site which is received after running register()
	//parameters -
	//				$token - token receieved from ipay website
	//returns - none
	public function updateTransaction($token) {
	
		$query = "UPDATE tbl_creditcard SET creditcard_token='$token' WHERE creditcard_referenceId=" . $this->referenceId;
		@mysql_query($query,$this->db) or die ("<br>Error. Unable to update transaction. "  . mysql_error($this->db));
	
	
	}
	
	public function getTransaction($token) {

		$query = "SELECT tbl_creditcard.*,tbl_status.* FROM tbl_creditcard
				LEFT JOIN tbl_status ON tbl_creditcard.creditcard_statusId=tbl_status.status_id
				WHERE creditcard_token='$token'";
		$result = mysql_query($query,$this->db);
		return $this->mysqlToArray($result);
	
	
	}
	
	public function getPaymentAmount($token) {
				
		$query = "SELECT creditcard_paymentAmount FROM tbl_creditcard WHERE creditcard_token='$token'";
		$result = mysql_query($query,$this->db);
		$amount1 = mysql_result($result,0,'creditcard_paymentAmount');
		return $amount1;
	
	}
	
	//finalizeTransaction() - finalizes transaction - sets transaction to being paid
	//parameters -
	//			$token - unique token from ipay website
	//			$transactionId - unique transaction id from ipay website
	//			$account1 - cfop account money will deposit into
	//			$amount1 - amount of money to be deposited
	//returns - none
	public function finalizeTransaction($token,$transactionId,$account1,$amount1) {
	
		$statusId = $this->getStatusId("Paided");
		$query = "UPDATE tbl_creditcard SET creditcard_transactionId='$transactionId'," .
					"creditcard_recAccount='$account1',creditcard_recAmount=$amount1," .
					"creditcard_statusId=$statusId WHERE creditcard_token='$token'";
		$success = @mysql_query($query,$this->db) or die("<br>Error: Unable to finilize transaction. " . mysql_error($this->db));
	
	
	}
	//error() - adds errors into error table if something went wrong
	//parameters -
	//			$responseCode - error number receieved from ipay site
	//			$errorMsg - corresponding error message
	//			$token - unique token to identify which transaction failed.
	//returns - none
	public function error($responseCode,$errorMsg,$token) {
		$query="INSERT INTO tbl_errors(error_code,error_msg,error_token,error_timestamp)" . 
			"VALUES($responseCode,'$errorMsg','$token','" . $this->time . "')";
		mysql_query($query,$this->db) 
			or die ("<br>Error. Unable to write errors to database. " . mysql_error($this->db));
	

	}

////////////////////////////////Private Functions///////////////////////////////////

	private function getStatusId($name) {
		$query = "SELECT * FROM tbl_status WHERE status_name='" . $name . "'";
		$statusResult = @mysql_query($query,$this->db) or die ("<br>Error. Unable to get status Id.");
		return mysql_result($statusResult,0,'status_id');
		
	}

	//mysqlToArray - converts mysql result into a multidimension array
	//parameters - 
	//			$mysqlResult - mysql result variable
	//returns - multidimension array
	private function mysqlToArray($mysqlResult) {
		$dataArray;
		$i =0;
		while($row = mysql_fetch_array($mysqlResult,MYSQL_ASSOC)){	
			foreach($row as $key=>$data) {
				$dataArray[$i][$key] = $data;
			}
			$i++;
		}
		return $dataArray;
	
	}








}



?>