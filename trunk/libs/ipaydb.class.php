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
	private $db; //mysql object
	private $token;
	private $registration_id;
	private $time;
	private $ipay_id;

/////////////////////////////Public Functions////////////////////////////////
	
	//__construct() - constucts ipaydb object
	//parameters - 
	//$db - database object
	//$token - ipay token 
	//returns - none
	public function __construct($db,$token = 0) {
		$this->db = $db;
		if ($token != 0) {
			$this->load($token);
		}
	}
	
	//__destruct()
	public function __destruct() { }
	
	
	public function load($token) {
		$this->token = $token;
		$this->get_ipaydb();
	}
	public function set_time($time) {
		$this->time = $time;
	
	}
	//start_transaction() - creates initial entries into database
	//parameters -
	//$amount - the amount to be charge
	//$registration_id - id number this ipay transaction is for.		
	//$time - timestamp
	//returns - unique id from the new row in ipay table
	public function start_transaction($amount,$registration_id,$time) {
		
		$status = "Pending";
		$sql = "INSERT INTO ipay(ipay_payment_amount,ipay_status,";
		$sql .= "ipay_time,ipay_registration_id) ";
		$sql .= "VALUES ($amount,'" . $status . "','" . $time . "','" . $registration_id . "')";
		$this->ipay_id = $this->db->insert_query($sql);
		$this->registration_id = $registration_id;
		$this->time = $time;
		return $this->ipay_id;
	
	}
	
	//update_transaction() - updates database with token receieved from ipay site which is received after running register()
	//parameters -
	//$token - token receieved from ipay website
	//returns - none
	public function update_transaction($token) {
	
		$sql = "UPDATE ipay SET ipay_token='" . $token . "' ";
		$sql .= "WHERE ipay_id='" . $this->ipay_id . "'";
		$this->db->non_select_query($sql);
	}
	
	public function get_transaction() {
		$sql = "SELECT ipay.* FROM ipay ";
		$sql .= "WHERE ipay_token='" . $this->token . "'";
		return $this->db->query($sql);

	}
	
	public function get_payment_amount() {
		$sql = "SELECT ipay_payment_amount ";
		$sql .= "FROM ipay WHERE ipay_token='" . $this->token . "'";
		$result = $this->db->query($sql);
		return $result[0]['ipay_payment_amount'];
	
	}
	
	//finalize_transaction() - finalizes transaction - sets transaction to being paid
	//parameters -
	//$token - unique token from ipay website
	//$transactionId - unique transaction id from ipay website
	//$account - cfop account money will deposit into
	//$amount - amount of money to be deposited
	//returns - none
	public function finalize_transaction($transaction_id,$account,$amount) {
	
		$status = "Paided";
		$sql = "UPDATE ipay SET ipay_transaction_id='" . $transaction_id . "',";
		$sql .= "ipay_rec_account='" . $account . "',ipay_rec_amount='" . $amount . ",";
		$sql .= "ipay_status='" . $status . "' WHERE ipay_token='" . $this->token . "'";
		$this->db->non_select_query($sql);
	}
	//error() - adds errors into table if something went wrong
	//parameters -
	//$response_code - error number receieved from ipay site
	//$error_msg - corresponding error message
	//returns - none
	public function error($response_code,$error_msg) {
		$sql = "UPDATE ipay SET ipay_response_code='". $response_code . "',ipay_error_msg='" . $error_msg . "') "; 
		$sql .= "WHERE ipay_token='" . $this->token . "'";
		$this->db->non_select_query($sql);
	

	}

	//////////////////Private Functions///////////////
	
	private function get_ipaydb() {
		$sql = "SELECT * from ipay WHERE ipay_token='" . $this->token . "' LIMIT 1";
		$result = $this->db->query($sql);
		if ($result) {
			$this->ipay_id = $result[0]['ipay_id'];
			$this->registration_id = $result[0]['ipay_registration_id'];
			$this->time = $result[0]['ipay_time'];
		}			
		
		
		
	}

}



?>
