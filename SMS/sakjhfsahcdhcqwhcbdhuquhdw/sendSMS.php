<?php
//BITRIX24 HEAD for application!
$auth = $_REQUEST['AUTH_ID'];
$domain = ($_REQUEST['PROTOCOL'] == 0 ? 'http' : 'https') . '://'.$_REQUEST['DOMAIN'];

$res = file_get_contents($domain.'/rest/user.current.json?auth='.$auth);
$arRes = json_decode($res, true);

require_once (__DIR__.'/crest/crest.php');
require './vendor/autoload.php';
error_reporting(E_ALL);
ini_set("display_errors", 1);
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

$params = array(
    'credentials' => array(
        'key' => $env['amazonkey'],
        'secret' => $env['amazonsecret'],
    ),
    'region' => $env['amazonarea'], // < your aws from SNS Topic region
    'version' => 'latest'
);
$sns = new \Aws\Sns\SnsClient($params);

if (isset($_POST['properties']['phone_number']) && isset($_POST['properties']['message_text'])){
	$recipient = isset($_POST['properties']['phone_number']) ? trim(str_replace("+","",$_POST["properties"]['phone_number'])) : '';
	$message = isset($_POST['properties']['message_text']) ? $_POST["properties"]['message_text'] : '';
}
if (isset($_POST['message_to']) && isset($_POST['message_body'])){
	$recipient = isset($_POST['message_to']) ? trim(str_replace("+","",$_POST["message_to"])) : '';
	$message = isset($_POST['message_body']) ? $_POST["message_body"] : '';
}
if(strlen($recipient) > 10) $recipient = '+'.$recipient;
else $recipient = '+1'.$recipient;

$contactid = isset($_POST['bindings'][0]['OWNER_ID']) ? $_POST['bindings'][0]['OWNER_ID'] : 0;
$auth = isset($_POST['auth']['access_token']) ? $_POST['auth']['access_token'] : '';
$domain = isset($_POST['auth']['domain']) ? $_POST['auth']['domain'] : '';
$menberid = isset($_POST['auth']['member_id']) ? $_POST['auth']['member_id'] : '';
$apptoken = isset($_POST['auth']['application_token']) ? $_POST['auth']['application_token'] : '';

if(isset($apptoken) && $apptoken === $env['key']){

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

$args = array(
    "MessageAttributes" => [
                'AWS.SNS.SMS.SenderID' => [
                    'DataType' => 'String',
                    'StringValue' => $env['amazonsenderid']
                ],
                'AWS.SNS.SMS.SMSType' => [
                    'DataType' => 'String',
                    'StringValue' => 'Promotional'
                ]
            ],
    "Message" => $message." Msg & data rates may apply. Replay HELP for info. or STOP to unsubscribe!",
    "PhoneNumber" => $recipient
);

try{

	$response = $sns->publish($args);
	$result = serialize($response);
	$pos = strpos($result, 's:10:"statusCode";i:200');
	if ($pos === false) {
    	$smsresult = "SMS Error";
	} else {
    	$smsresult = "Success";
	}
	
	
}catch(Exception $e){
	echo $e->getMessage();
}
	
} else {
	echo "Access Denied!";
}

//sleep for 3 seconds
sleep(3);
if(isset($_POST['redirect']) && $_POST['redirect'] != '') header('Location: '.$_POST['redirect'].'&message='.$message);
//Only for tests!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()));
//fclose($myfile);

//$content["GET"] = $_GET;
/*$content = $_POST;
$content["date"] = date("F j, Y, g:i a");
$content['result'] = $result;
file_put_contents("log_send_SMS.txt", print_r($content, true), FILE_APPEND);*/

?>