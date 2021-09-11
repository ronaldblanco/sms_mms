<?php
//$json_write_to_text = json_decode(file_get_contents("php://input"));
//echo $json_write_to_text;
require_once (__DIR__.'/crest/crest.php');

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
$type = $all_info['Type'];

$message = isset($data['messageBody']) ? $data["messageBody"] : '';
$sender = isset($data['originationNumber']) ? $data["originationNumber"] : '';
$senderfix = $sender;
if(strlen($senderfix) > 10) $senderfix = substr($senderfix, 2, 11);
$sender = str_replace("+","",$sender);
//$type = isset($data['messageType']) ? $data["messageType"] : '';
$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];

if($token != "" && $token == $env['voipkey']){

if(($message != "" || $files != "") && $sender != ""){
	
	$contact = ( CRest :: call (
    'crm.contact.list' ,
   	[
	  'FILTER' => ["PHONE" => $sender],
	  'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   	])
	);
	//echo '1 try!';
	//var_dump($contact['result']);
	
	if(!isset($contact['result'][0]['ID'])){
		$contact = ( CRest :: call (
    	'crm.contact.list' ,
   		[
	  	'FILTER' => ["PHONE" => substr($sender,1,10)],
	  	'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   		])
		);
		//echo '2 try!';
		//var_dump($contact['result']);
	}

	$user = ( CRest :: call (
    	'user.get' ,
   		[
        	'ID' => $contact['result'][0]['ASSIGNED_BY_ID']
	  		//'FILTER' => ["PERSONAL_MOBILE" => $recipient],
   		])
	);

	$groundwire = $env['campaigngroundwireintegration'] . "?from=+".$sender."&to=+1".$user['result']['PERSONAL_MOBILE']."&text=";
	
	$contactLink = "<a href='".$env['bitrixcontactlink']."/".$contact['result'][0]['ID']."/'>". $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']. "</a>";
	$comment = "A SMS was received from sender: " . $sender . ", contact: " . $contactLink . ", with the message: " . $message . "!";
	$externalmessage = "You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'].", with the message: " . $message . "!";
		
	//Send comment to groundwire
	$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));
	//var_dump($groundwire."You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']."!");
	//var_dump($sendtogw);
	
	$timeline = ( CRest :: call (
    'crm.timeline.comment.add' ,
   	[
		'fields' =>
           [
               "ENTITY_ID" => $contact['result'][0]['ID'],
               "ENTITY_TYPE" => "contact",
               "COMMENT" => $comment,
           ]
   	])
	);
	//var_dump($timeline);
	
	if(isset($user['result'][0]['ID'])){
	
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => $user['result'][0]['ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
	//var_dump($setmessage);
	$result = ['result' => 'true'];
		
	}


	if(isset($user['result'][0]['ID']) && ($user['result'][0]['ID'] != 62 || $user['result'][0]['ID'] != '62')){
	//Notify Irlenis Ibarra about campaing SMS
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => 62,
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
    
    }
	
	
//Statistics Data
//////////////////////////////////////
$string = trim(str_replace(PHP_EOL, '@',str_replace('\r\n', '@', file_get_contents("statistics.txt"))));
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
}
//////////////////////////////////////
	
} else {
	echo "There it is no Information to work!</br>";
}

echo $result;

} else {
	echo "Access Denied!</br>";
}

//Only for TESTs!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()).PHP_EOL);
//fclose($myfile);

$content = $all_info;
$content["date"] = date("F j, Y, g:i a");
//$content["contact"] = $contact;
//$contact["sender"] = substr($sender,1,10);
file_put_contents("log_GET_SMS.txt", print_r($content, true), FILE_APPEND);
file_put_contents("log_GET_SMS.txt", ob_get_flush(), FILE_APPEND);


?>