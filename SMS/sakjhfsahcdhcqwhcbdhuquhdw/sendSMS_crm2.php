<?php

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

if (isset($_GET['to']) && isset($_GET['body'])){
	$recipient = isset($_GET['to']) ? trim(str_replace("+","",$_GET["to"])) : '';
	$message = isset($_GET['body']) ? base64_decode($_GET["body"]) : '';
}
if(strlen($recipient) > 10) $recipient = '+'.$recipient;
else $recipient = '+1'.$recipient;

if($message == '') return "No Message Found!";

$apptoken = isset($_GET['token']) ? $_GET['token'] : '';

if(isset($apptoken) && $apptoken === $env['key']){

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
		
	echo "{result:'".$smsresult."'}";
	
}catch(Exception $e){
	echo $e->getMessage();
}
	
} else {
	echo "Access Denied!";
}

//sleep for 3 seconds
//sleep(3);
//if(isset($_POST['redirect']) && $_POST['redirect'] != '') header('Location: '.$_POST['redirect'].'&message='.$message);

/*$content = $_GET;
$content["date"] = date("F j, Y, g:i a");
$content['result'] = $smsresult;
$content['message_decode'] = $message;
file_put_contents("log_send_SMS_crm2.txt", print_r($content, true), FILE_APPEND);*/

?>