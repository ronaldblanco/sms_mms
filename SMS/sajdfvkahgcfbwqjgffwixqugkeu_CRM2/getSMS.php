<?php

/*
 * 
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

$recipient = isset($_GET['to']) ? trim(str_replace("+","",$_GET["to"])) : '';
$recipientfix = $recipient;
if(strlen($recipientfix) > 10) $recipientfix = substr($recipientfix, 1, 10);
$message = isset($_GET['text']) ? $_GET["text"] : '';
$sender = isset($_GET['from']) ? $_GET["from"] : '';
$senderfix = $sender;
if(strlen($senderfix) > 10) $senderfix = substr($senderfix, 1, 10);
$type = isset($_GET['messageType']) && $_GET['messageType'] == 'SMS' ? 'sms' : 'mms';
$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];
if($type === "mms" && $token != "" && $token == $env['voipkey']){
	$files = isset($_GET['fileUrls']) ? $_GET["fileUrls"] : "['']";
	$fileUrls = $files;
	$files = ltrim($files, "['");
	$files = rtrim($files, "']");
	$filesArr = explode("', '", $files);
	if(count($filesArr) > 1) $files = str_replace("', '", ", ", $files);
	
}

if($token != "" && $token == $env['voipkey']){

if($recipient != "" && ($message != "" || $files != "") && $sender != ""){

	if(strlen($sender) > 10) $sender = substr($sender,1);
    if(strlen($recipient) > 10) $recipient = substr($recipient,1);

	$url = $env['crm2getmessage'];
	$url = $url . "?from=".$sender."&to=".$recipient."&text=".base64_encode($message)."&type=".$type;

	if($type == 'mms') foreach($_GET['fileUrls'] as $fileUrl){
    	$url = $url . "&fileUrls[]=" . base64_encode($fileUrl);
    }
    //echo $url;
	//exit;
	$sendToCrm2 = file_get_contents($url);
		
	//Send comment to groundwire with curl ##############################
	/*$curl_error = "";
	$cURLConnection = curl_init();
	curl_setopt($cURLConnection, CURLOPT_URL, str_replace(" ","%20",$groundwire.$externalmessage));
	curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
	$sendtogw = curl_exec($cURLConnection);
	//var_dump($sendtogw);
	if($sendtogw === false) $curl_error = curl_error($cURLConnection);
	curl_close($cURLConnection);*/
	//###################################################################
	//Send comment to groundwire
	//$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));
	//var_dump($groundwire."You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']."!");
	//var_dump($sendtogw);
		
	//}
	
} else {
	echo "There it is no Information to work!</br>";
}

echo $sendToCrm2;

} else {
	echo "Access Denied!</br>";
}

$content = $_GET;
$content['$sendToCrm2'] = $sendToCrm2;
$content['url'] = $url;
$content["date"] = date("F j, Y, g:i a");
file_put_contents("log_".$recipient.".txt", print_r($content, true), FILE_APPEND);

?>