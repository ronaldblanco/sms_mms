<?php

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

$all_info = json_decode(file_get_contents("php://input"),true);
$all_info['Message'] = json_decode($all_info['Message'],true);
$data = $all_info['Message'];
$type = 'sms'; //$all_info['Type'];

$message = isset($data['messageBody']) ? $data["messageBody"] : '';
$sender = isset($data['originationNumber']) ? $data["originationNumber"] : '';
$senderfix = $sender;
if(strlen($senderfix) > 10) $senderfix = substr($senderfix, 2, 11);
$sender = str_replace("+","",$sender);
//$type = isset($data['messageType']) ? $data["messageType"] : '';
$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];

if($token != "" && $token == $env['voipkey']){

if(($message != "" || $files != "") && $sender != "") {
	
	/*$groundwire = $env['campaigngroundwireintegration'] . "?from=+".$sender."&to=+1".$user['result']['PERSONAL_MOBILE']."&text=";
	
	$contactLink = "<a href='".$env['bitrixcontactlink']."/".$contact['result'][0]['ID']."/'>". $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']. "</a>";
	$comment = "A SMS was received from sender: " . $sender . ", contact: " . $contactLink . ", with the message: " . $message . "!";
	$externalmessage = "You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'].", with the message: " . $message . "!";
		
	//Send comment to groundwire
	$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));*/
	//var_dump($groundwire."You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']."!");
	//var_dump($sendtogw);

	if(strlen($sender) > 10) $sender = substr($sender,1);
	
	$url = $env['crm2getmessagefromcampaign'];
	$url = $url . "?from=".$sender."&text=".base64_encode('Campaign Response: '.$message)."&type=".$type;

	/*if($type == 'mms') foreach($_GET['fileUrls'] as $fileUrl){
    	$url = $url . "&fileUrls[]=" . base64_encode($fileUrl);
    }*/
    //echo $url;
	//exit;
	$sendToCrm2 = file_get_contents($url);
	
   
	
	
//Statistics Data
//////////////////////////////////////
/*$string = trim(str_replace(PHP_EOL, '@',str_replace('\r\n', '@', file_get_contents("statistics.txt"))));
$datasta = explode("@",$string);

//var_dump($string);
//var_dump($datasta);

$datesta = date("Y-m-d");
$found = false;
for($i=0;$i< count($datasta);$i++){
	$tmp = explode(';',$datasta[$i]);
	var_dump($tmp);
	if($tmp[0] == $datesta){
		//$tmp1 = explode(';',$datasta[$i + 1]);
		$datasta[$i] = $tmp[0].';'.($tmp[1] + 1);
		$found = true;
	}
}

if($found == false) $datasta[count($datasta)] = $datesta.";1";

file_put_contents("statistics.txt", "");
foreach($datasta as $day){
	if($day != "") file_put_contents("statistics.txt", $day.PHP_EOL, FILE_APPEND);
}*/
//////////////////////////////////////
	
} else {
	echo "There it is no Information to work!</br>";
}

echo $sendToCrm2;

} else {
	echo "Access Denied!</br>";
}

//Only for TESTs!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()).PHP_EOL);
//fclose($myfile);

/*$content = $all_info;
$content["date"] = date("F j, Y, g:i a");
$content["url"] = $url;
$contact["sender"] = substr($sender,1,10);
file_put_contents("log_GET_SMS_crm2.txt", print_r($content, true), FILE_APPEND);
file_put_contents("log_GET_SMS_crm2.txt", ob_get_flush(), FILE_APPEND);*/


?>