<?php
//BITRIX24 HEAD for application!
$auth = $_REQUEST['AUTH_ID'];
$domain = ($_REQUEST['PROTOCOL'] == 0 ? 'http' : 'https') . '://'.$_REQUEST['DOMAIN'];

$res = file_get_contents($domain.'/rest/user.current.json?auth='.$auth);
$arRes = json_decode($res, true);

require_once (__DIR__.'/crest/crest.php');
?>

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

if (isset($_POST['properties']['phone_number']) && isset($_POST['properties']['message_text'])){
	$recipient = isset($_POST['properties']['phone_number']) ? trim(str_replace("+","",$_POST["properties"]['phone_number'])) : '';
	$message = isset($_POST['properties']['message_text']) ? $_POST["properties"]['message_text'] : '';
}
if (isset($_POST['message_to']) && isset($_POST['message_body'])){
	$recipient = isset($_POST['message_to']) ? trim(str_replace("+","",$_POST["message_to"])) : '';
	$message = isset($_POST['message_body']) ? $_POST["message_body"] : '';
}
$contactid = isset($_POST['bindings'][0]['OWNER_ID']) ? $_POST['bindings'][0]['OWNER_ID'] : 0;
$auth = isset($_POST['auth']['access_token']) ? $_POST['auth']['access_token'] : '';
$domain = isset($_POST['auth']['domain']) ? $_POST['auth']['domain'] : '';
$menberid = isset($_POST['auth']['member_id']) ? $_POST['auth']['member_id'] : '';
$apptoken = isset($_POST['auth']['application_token']) ? $_POST['auth']['application_token'] : '';

$defaultsender = $env['bitrixdefaultsender']; //305
$SMSuser = $env['SMSuser'];
$SMSpass = $env['SMSpass'];

if((isset($apptoken) && $apptoken === "9041496a10dd6b2e99ef8581d3f8625c")){

if($contactid > 0){
	$ownerid = ( CRest :: call (
    'crm.contact.list' ,
   [
	  'FILTER' => ["ID" => $contactid],
	  'SELECT' => ['ASSIGNED_BY_ID','NAME','LAST_NAME'],
   ])
);
//var_dump($ownerid);
$ownerphone = ( CRest :: call (
    'user.get' ,
   [
	  'FILTER' => ["ID" => $ownerid['result'][0]['ASSIGNED_BY_ID']],
   ])
);
//var_dump($ownerphone['result'][0]);
}

if(isset($ownerphone['result'][0]['PERSONAL_MOBILE']) && $ownerphone['result'][0]['PERSONAL_MOBILE'] != ''){
	$sender = $ownerphone['result'][0]['PERSONAL_MOBILE']; //If owner have movile send from owner
} else {
	$sender = $defaultsender; //If owner does not have movile send from mainsender
}

try{
$soapclient = new SoapClient($env['voipprovider']);
$param=array('login'=>$SMSuser,'secret'=>$SMSpass,'sender'=>$sender,'recipient'=>$recipient,'message'=>$message);
	
	$response =$soapclient->SendSMS($param);
	$result = json_encode($response);
	echo $result;
	$smsresult = $response->SendSMSResult->responseMessage;
	if($smsresult != 'Success' && $smsresult != 'Invalid sender' && $smsresult != 'Invalid TN. Sender and recipient must be valid phone numbers and include country code.') $smsresult = 'SMS Error';
	
	$comment = "The SMS Service for contact " . $ownerid['result'][0]['NAME'] . " " . $ownerid['result'][0]['LAST_NAME'] . " responded: " . $smsresult . "!";
	
	$timeline = ( CRest :: call (
    'crm.timeline.comment.add' ,
   	[
		'fields' =>
           [
               "ENTITY_ID" => $contactid,
               "ENTITY_TYPE" => "contact",
               "COMMENT" => $comment,
           ]
   	])
	);
	
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => $ownerid['result'][0]['ASSIGNED_BY_ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
	//var_dump($setmessage);
	
}catch(Exception $e){
	echo $e->getMessage();
}
	
} else {
	echo "Access Denied!";
}

//sleep for 3 seconds
sleep(3);
$message = trim(preg_replace('/\s+/', '%20', $message)); //Fix message to avoid redirect faild
if(isset($_POST['redirect']) && $_POST['redirect'] != '') header('Location: '.$_POST['redirect'].'&message='.$message);
//Only for tests!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);

/*$content = $_POST;
$content["date"] = date("F j, Y, g:i a");
$content['result'] = $result;
file_put_contents("log_send_".$sender.".txt", print_r($content, true), FILE_APPEND);*/

?>