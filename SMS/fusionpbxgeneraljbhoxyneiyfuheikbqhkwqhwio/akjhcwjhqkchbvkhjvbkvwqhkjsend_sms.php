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

$key = '';
if (isset($_GET["key"])) $key = base64_decode($_GET["key"]);
//var_dump($key);
//var_dump($_SERVER['REMOTE_ADDR']);
$extension = "";
if (isset($_GET["extension"])) $extension = base64_decode($_GET["extension"]);
$to = "";
if (isset($_GET["to"])) $to = base64_decode($_GET["to"]);
$message = "";
if (isset($_GET["message"])) $message = base64_decode($_GET["message"]);

if(isset($key) && $key === $env['key']){
/////////////////////////////////////////////////////////////VOIP INNOVATION

    $SMSuser = $env['SMSuser'];
	$SMSpass = $env['SMSpass'];

    try {
        $soapclient = new SoapClient($env['voipprovider']);
        $param = array('login' => $SMSuser, 'secret' => $SMSpass, 'sender' => $extension, 'recipient' => $to, 'message' => $message);

        $response = $soapclient->SendSMS($param);
        $result = json_encode($response);
        echo $result;
        $smsresult = $response->SendSMSResult->responseMessage;
        if ($smsresult != 'Success' && $smsresult != 'Invalid sender' && $smsresult != 'Invalid TN. Sender and recipient must be valid phone numbers and include country code.') $smsresult = 'SMS Error';

        $comment = "The SMS from " . $extension. " to " . $to . " have a result: " . $smsresult . "!";
        echo $comment;
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    /////////////////////////////////////////////////////////////
} else echo "Access Denied!";

?>