<?php
////////////////////////////////////////
//
//	ipay.class.php
//
//	Class to interface with UofI
//	iPay credit card system.
//
//	By: David Slater
//	Date: 06/2008
//
////////////////////////////////////////


class ipay {

	///////Private Variables//////////
	private $siteid;
	private $sendKey;
	private $receiveKey;
	private $urlRegistration;
	private $urlResult;
	private $urlCapture;
	private $debug;
	private $token;
	private $gmtimestamp;
	private $gmtime;
	private $responseCodeErrors = array(
					'0' => 'Success',
					'1' => 'Request Attribute Parse Error',
					'2' => 'Certification Match Failure',
					'3' => 'Site Not Active',
					'4' => 'Tender Mismatch',
					'5' => 'Transaction Token Expired',
					'6' => 'Authorization Failure',
					'7' => 'Token Not Found',
					'8' => 'Capture Failed',
					'9' => 'TimeStamp offset too large',
					'10' => 'Mismatched Auth Amount',
					'100' => 'System Error',
					'101' => 'Invalid Received Certification',
					);

/////////////////////////////Public Functions////////////////////////////////
	
	//__construct() - creates ipay object with some default information
	//parameters -
	//		$debug - boolean - enables or disables debug mode
	//		$inSiteId - string - site ID that was given for your website
	//		$inSendKey - string - encryption send key
	//		$inReceieveKey	- string - encryption receieve key
	//returns - none
	public function __construct($debug,$inSiteId,$inSendKey,$inReceiveKey) {
		$this->siteid = $inSiteId;
		$this->sendKey = $inSendKey;
		$this->receiveKey = $inReceiveKey;
		
		$this->gmtimestamp = gmmktime();
		$this->gmtime = gmdate('m-d-Y H:i:s',$this->gmtimestamp);

		if ($debug == true) {
			$this->urlRegistration = 'https://webtest.obfs.uillinois.edu/ipay/pc/interfaces/actRegisterCCPaymentNVP.cfm';
			$this->urlResult = 'https://webtest.obfs.uillinois.edu/ipay/pc/interfaces/actQueryCCPaymentNVP.cfm';
			$this->urlCapture = 'https://webtest.obfs.uillinois.edu/ipay/pc/interfaces/actCaptureCCPaymentNVP.cfm';
		}
		elseif ($debug == false) {
			$this->urlRegistration = 'https://www.ipay.uillinois.edu/pc/interfaces/actRegisterCCPaymentNVP.cfm';
			$this->urlResult = 'https://www.ipay.uillinois.edu/pc/interfaces/actQueryCCPaymentNVP.cfm';
			$this->urlCapture = 'https://www.ipay.uillinois.edu/pc/interfaces/actCaptureCCPaymentNVP.cfm';
		}
	}
	
	//__destruct()
	public function __destruct() {}

	//register() - step 1 - 
	//parameters -
	//$referenceId - integer number
	//$amount - number
	//returns - array - 
	public function register($referenceId,$amount) {
		$amount = number_format($amount,2);
	
		$certifythis = array($amount,$referenceId,$this->siteid,$this->gmtime);
		$certification = $this->certify($certifythis,$this->sendKey);
		$params = "siteid=" . $this->siteid . "&referenceid=" . $referenceId . "&amount=" . $amount . "&timeStamp=" . $this->gmtime . "&certification=" . $certification;
		$result = $this->httppost($params,$this->urlRegistration);
		$result['ErrorMsg'] = $this->responseCodeErrors[$result['ResponseCode']];
		$certifythis = array($result['Redirect'],$result['ResponseCode'],$result['TimeStamp'],$result['Token']);
		if ($result['ResponseCode'] != 0) {
				return $result;
		}
		elseif ($result['Certification'] !=  $this->certify($certifythis,$this->receiveKey)) {
			$result['ResponseCode'] = '101';
			$result['ErrorMsg'] = $this->responseCodeErrors['101'];
		}
		return $result;
		
	}
	
	//result() - step 2 - 
	//parameters -
	//		$token - string
	//returns - 
	public function result($token) {
		$this->token = $token;
		$certifythis = array($this->siteid,$this->gmtime,$token);	
		$certification = $this->certify($certifythis,$this->sendKey);
		
		$params = "siteid=" . $this->siteid . "&token=" . $token . "&timeStamp=" . $this->gmtime . "&certification=" . $certification;
		$result = $this->httppost($params,$this->urlResult);
		$result['ErrorMsg'] = $this->responseCodeErrors[$result['ResponseCode']];
		$certifythis = array ($result['ResponseCode'],$result['TimeStamp'],$result['TransactionID']);
		if ($result['ResponseCode'] != 0) {
				return $result;
		}
		elseif ($result['Certification'] != $this->certify($certifythis,$this->receiveKey)){
			$result['ResponseCode'] = '101';
			$result['ErrorMsg'] = $this->responseCodeErrors['101'];
		}
		return $result;

	}

	//capture() - step 3 -
	//parameters -
	//		$accounts - array of strings - an array of cfop accounts
	//		$amounts - array of numbers - corresponding array of amounts to put into each cfop account
	//returns - 
	public function capture($accounts,$amounts) {
		if ((count($accounts) == count($amounts)) && (count(accounts) > 0)) {
			$numAccounts = count($accounts);
			$params = "&numaccounts=" . $numAccounts;
			$certifythis = array();
			for ($i=1;$i<=$numAccounts;$i++) {
				$amount = number_format($amounts[$i-1],2);
				$account = $accounts[$i-1];
				$params .= "&account" . $i . "=" . $accounts[$i-1] . "&amount" . $i . "=" . $amount;
				array_push($certifythis,$account);
				array_push($certifythis,$amount);
			}
			array_push($certifythis,$numAccounts,$this->siteid,$this->gmtime,$this->token);
			$certification = $this->certify($certifythis,$this->sendKey);
			$params = "siteid=" . $this->siteid ."&token=" . $this->token . "&timestamp=" . $this->gmtime . $params . "&certification=" . $certification;
			$result = $this->httppost($params,$this->urlCapture);
			$result['ErrorMsg'] = $this->responseCodeErrors[$result['ResponseCode']];
			
			$certifythis = array($result['CaptureAmount'],$result['ResponseCode'],$result['TimeStamp'],$result['TransactionID']);
			if ($result['ResponseCode'] != 0) {
				
				return $result;
			}
			elseif ($result['Certification'] !=  $this->certify($certifythis,$this->receiveKey)){
				$result['ResponseCode'] = '101';
				$result['ErrorMsg'] = $this->responseCodeErrors['101'];
			}
			
			return $result;
		}
		
	}
	
	//getTime() - returns the time for logging purposes
	//parameters - none
	//returns - time
	public function get_time() {
		$currentTime = gmdate('Y-m-d H:i:s',$this->gmtimestamp);
		return $currentTime;
	}
	
	public function get_error($error_code) {
		return $responseCodeErrors[$error_code];
		
	}
//////////////////////////////Private Functions/////////////////////////////
	
	//certify() - strings to get values from an array and returns mhash  based on the input key
	//parameters -
	//	$values - string array - contains variables to certify
	//	$key	- string - encryption key
	//returns - encrypted certification string of the values
	private function certify($values,$key) {
		$certifythis = implode("",$values);
		$certification = strtoupper(bin2hex(mhash(MHASH_SHA1,$certifythis,$key)));
		//echo "<br>certify() - certification is $certification";
		return $certification;
	}

	//httppost() - posts variables to iPay website using curl
	//parameters - 
	//		$params - string - contains http post variables to be sent in form "variable1=value1&variable2=value2&variable3=value3"
	//		$url - string - website address
	//returns - an array of the results
	private function httppost($params,$url) {
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,true);
		curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		$result = curl_exec($ch);
		curl_close($ch);
		//echo "<br>httppost() result - $result";
		
		$delimiter = chr(13) . chr(10);  // carriage return + line feed (separates elements)
		$explodearray = explode($delimiter, $result);
		
		$resultarray;
		foreach($explodearray as $value) {
			list($key,$resultvalue) = explode('=',$value);
			$resultarray[$key] = $resultvalue;
		}
		
		
		
		return $resultarray;
	}
}



?>