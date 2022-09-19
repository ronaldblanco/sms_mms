<?php

/*
 * techcnet.com/SMS/sajdfvkahgcfbwqjgffwixqugkeu_CRM2/sendSMS.php
 * POST DATA
 *  
 * */

//Get ENV
	$env = file_get_contents('../../../.env', true);
	$env = explode("\n",$env);
	$getEnv = [];
	foreach($env as $data){
		$data = explode("=",$data);
		$getEnv[$data[0]] = $data[1];
	}
	$env = $getEnv;
	unset($getEnv);

	//var_dump($env);

if (isset($_POST['to']) /*&& isset($_POST['body'])*/ && isset($_POST['from'])){
	$sender = isset($_POST['from']) ? trim(str_replace("+","",$_POST["from"])) : '';
	$recipient = isset($_POST['to']) ? trim(str_replace("+","",$_POST["to"])) : '';
	$message = isset($_POST['body']) ? /*base64_decode(*/$_POST["body"]/*)*/ : '';
}

if(strlen($sender) == 10) $sender = '1'.$sender;

$key = isset($_POST['key']) ? $_POST['key'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : 'sms';
$attch = isset($_POST['attch']) ? $_POST['attch'] : '';

$SMSuser = $env['SMSuser'];
$SMSpass = $env['SMSpass'];

if(isset($key) && $key === $env['key']){

try{
	//$soapclient = new SoapClient($env['voipprovider']);
		
	if($type == 'sms'){
    
    	$payload=array(
        
        	'from'=>$sender,
        	'to'=>$recipient,
        	'text'=>$message,
        	'messageType'=>'SMS'
        );
    
    	/*$auth = array(
        	'username'=>$SMSuser,
        	'password'=>$SMSpass
        );*/
		//$response =$soapclient->SendSMS($param);
    
    		$send_sms_error = "";
			$cURLConnectionSms = curl_init();
			curl_setopt($cURLConnectionSms, CURLOPT_URL, "https://mysmsforward.com/sms/out/");
			curl_setopt($cURLConnectionSms, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($cURLConnectionSms, CURLINFO_HEADER_OUT, true);
			//curl_setopt($cURLConnectionSms, CURLOPT_SSL_VERIFYHOST, false);
			//curl_setopt($cURLConnectionSms, CURLOPT_SSL_VERIFYPEER, false);
    		curl_setopt($cURLConnectionSms, CURLOPT_POST, true);
    		
    		curl_setopt($cURLConnectionSms, CURLOPT_POSTFIELDS, $payload);
    		curl_setopt($cURLConnectionSms, CURLOPT_USERPWD, $SMSuser . ":" . $SMSpass);
    		// Set HTTP Header for POST request 
			curl_setopt($cURLConnectionSms, CURLOPT_HTTPHEADER, array(
    			'Content-Type: application/json',
    			'Content-Length: ' . strlen($payload))//,
                //'Authorization: basic ' . $auth,
			);
    
    		$response = curl_exec($cURLConnectionSms);
            $send_sms_error = curl_error($cURLConnectionSms);
    
    	$result = json_encode($response);
    	var_dump($result);
    	var_dump($send_sms_error);
		//$smsresult = $response->SendSMSResult->responseMessage;
    	curl_close($cURLConnectionSms);
    
    } else {
    
    	$arrContextOptions=array(
      		"ssl"=>array(
            	"verify_peer"=>false,
            	"verify_peer_name"=>false,
        	),
    	);
    
    	//if($attch == '') $attch = [];
    
    	if($attch != ''){
        	$fileNameNoParameters = explode('?',$attch);
    		$file = explode('/',$fileNameNoParameters[0]);
    		$fileName = $file[count($file) - 1];
        }else{
        	
        }
    	
    	$errorString = 'NoSuchKey';
    	
			$curl_error = "";
			$cURLConnection = curl_init();
			curl_setopt($cURLConnection, CURLOPT_URL, $attch);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    		//curl_setopt($cURLConnection, CURLINFO_HEADER_OUT, true);
			curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
    		//curl_setopt($cURLConnection, CURLOPT_POST, true);
    		
    		/*curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $payload);
    		// Set HTTP Header for POST request 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    			'Content-Type: application/json',
    			'Content-Length: ' . strlen($payload))
			);*/
    
    		sleep(7);
			if($attch != '') $fileContent = curl_exec($cURLConnection);
			//var_dump($sendtogw);
  
			if($attch != '') if($fileContent === false || strpos(strval($fileContent), $errorString) !== false) {
            	$curl_error = curl_error($cURLConnection);
            	sleep(5);
            	$fileContent = curl_exec($cURLConnection);
            	if($fileContent === false || strpos(strval($fileContent), $errorString) !== false) {
            		$curl_error = curl_error($cURLConnection);
            		sleep(5);
            		$fileContent = curl_exec($cURLConnection);
                	if($fileContent === false || strpos(strval($fileContent), $errorString) !== false) {
            			$curl_error = curl_error($cURLConnection);
            			sleep(5);
            			$fileContent = curl_exec($cURLConnection);
                    	if($fileContent === false || strpos(strval($fileContent), $errorString) !== false) {
            				$curl_error = curl_error($cURLConnection);
            				sleep(5);
            				$fileContent = curl_exec($cURLConnection);
                    		if($fileContent === false || strpos(strval($fileContent), $errorString) !== false) {
            					$curl_error = curl_error($cURLConnection);
            					sleep(5);
            					$fileContent = curl_exec($cURLConnection);
                    			if($fileContent === false) $curl_error = curl_error($cURLConnection);
            				}
            			}
            		}
            	}
            }
			if($attch != '') curl_close($cURLConnection);
			//###################################################################
        
        	if($attch != '') $files[0] = array("FileName"=>$fileName,"FileContent"=>$fileContent);
    		else $files = null;
        	
        //}
    	//var_dump($_POST);
    	//var_dump($file[count($file) - 1]);
    	if($files != null) $param=array('login'=>$SMSuser,'secret'=>$SMSpass,'sender'=>$sender,'recipient'=>$recipient,'message'=>$message, 'files'=>$files);
    	else $param=array('login'=>$SMSuser,'secret'=>$SMSpass,'sender'=>$sender,'recipient'=>$recipient,'message'=>$message);
		$response =$soapclient->SendMMS($param);
    
    	$result = json_encode($response);
    	//var_dump($files);
    	//var_dump($result);
		$smsresult = $response->SendMMSResult->responseMessage;
    }

	if($smsresult != 'Success' && $smsresult != 'Invalid sender' && $smsresult != 'Invalid TN. Sender and recipient must be valid phone numbers and include country code.') $smsresult = 'SMS o MMS Error!';
	echo $result;
	
}catch(Exception $e){
	echo $e->getMessage();
}
	
} else {
	echo "Access Denied!";
}

/*$content = $_POST;
$content["date"] = date("F j, Y, g:i a");
//$content["attchs"] = $files?$files:[];
$content['curl_error'] = $curl_error?$curl_error:"";
$content['result'] = $result;
file_put_contents("log_send_".$sender.".txt", print_r($content, true), FILE_APPEND);*/

?>