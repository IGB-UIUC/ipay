<?php
////////////////////////////////////////
//
//	ipaydb.class.php
//
//	Class to save ipay information
//	to a database
//	By: David Slater
//	Date: 06/2008
//
////////////////////////////////////////


class ipaydb {

///////Private Variables//////////
	private $db;
	private $registration_id;
	private $time;
	private $ipay_id;

/////////////////////////////Public Functions////////////////////////////////
	
	//__construct() - constucts ipaydb object
	//parameters - 
	//$host - mysql hostname
	//$database - mysql database
	//$username - mysql username
	//$password - mysql password
	//returns - none
	public function __construct($host,$database,$username,$password) {
		//Connects to database.
		$this->db = mysql_connect($host,$username,$password);
		@mysql_select_db($database,$this->db) 
			or die("<br>Error. Unable to select database. " . mysql_error($this->db));
		
		
	}
	
	//__destruct()
	public function __destruct() {
		mysql_close($this->db);

	}
	public function set_time($time) {
		$this->time = $time;
	
	}
	//start_transaction() - creates initial entries into database
	//parameters -
	//$amount - the amount to be charge
	//$registration_id - id number this ipay transaction is for.		
	//returns - unique id from the new row in ipay table
	public function start_transaction($amount,$registration_id) {
	
		$status = "Pending";
		$sql = "INSERT INTO ipay(ipay_payment_amount,ipay_status,";
		$sql .= "ipay_date,ipay_registration_id) ";
		$sql .= "VALUES ($amount,'" . $status . "','" . $this->time . "','" . $registration_id . "')";
		
		@mysql_query($sql,$this->db) or die ("<br>Error. Unable to start transaction. " . mysql_error($this->db));
		$this->ipay_id = mysql_insert_id($this->db);
		$this->registration_id = $registration_id;
		$this->time = $inTime;
		return $this->ipay_id;
	
	}
	
	//update_transaction() - updates database with token receieved from ipay site which is received after running register()
	//parameters -
	//$token - token receieved from ipay website
	//returns - none
	public function update_transaction($token) {
	
		$sql = "UPDATE ipay SET ipay_token='" . $token . "' ";
		$sql .= "WHERE ipay_id='" . $this->ipay_id . "'";
		@mysql_query($query,$this->db) or die ("<br>Error. Unable to update transaction. "  . mysql_error($this->db));
	
	
	}
	
	public function get_transaction($token) {
		$sql = "SELECT ipay.* FROM ipay ";
		$sql .= "WHERE ipay_token='" . $token . "'";
		$result = mysql_query($query,$this->db);
		return $this->mysqlToArray($result);
	
	
	}
	
	public function get_payment_amount($token) {
		$sql = "SELECT ipay_payment_amount ";
		$sql .= "FROM ipay WHERE ipay_token='" . $token . "'";
		$result = mysql_query($sql,$this->db);
		$amount = mysql_result($result,0,'ipay_payment_amount');
		return $amount;
	
	}
	
	//finalize_transaction() - finalizes transaction - sets transaction to being paid
	//parameters -
	//$token - unique token from ipay website
	//$transactionId - unique transaction id from ipay website
	//$account - cfop account money will deposit into
	//$amount - amount of money to be deposited
	//returns - none
	public function finalize_transaction($token,$transaction_id,$account,$amount) {
	
		$status = "Paided";
		$sql = "UPDATE ipay SET ipay_transaction_id='" . $transaction_id . "',";
		$sql .= "ipay_rec_account='" . $account . "',ipay_rec_amount='" . $amount . ",";
		$sql .= "ipay_status='" . $status . "' WHERE ipay_token='" . $token . "'";
		$success = @mysql_query($sql,$this->db) or die("<br>Error: Unable to finalize transaction. " . mysql_error($this->db));
	
	
	}
	//error() - adds errors into error table if something went wrong
	//parameters -
	//$responseCode - error number receieved from ipay site
	//$errorMsg - corresponding error message
	//$token - unique token to identify which transaction failed.
	//returns - none
	
	public function error($error_code,$error_msg,$token) {
		$sql = "UPDATE ipay SET ipay_error_code='". $error_code . "',ipay_error_msg='" . $error_msg . "') "; 
		$sql .= "WHERE ipay_token='" . $token . "'";
		mysql_query($sql,$this->db) 
			or die ("<br>Error. Unable to write errors to database. " . mysql_error($this->db));
	

	}

////////////////////////////////Private Functions///////////////////////////////////
	
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