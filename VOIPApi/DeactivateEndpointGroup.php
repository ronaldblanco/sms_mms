<?php

//Get ENV
	$env = file_get_contents('../../.env', true);
	$env = explode("\n",$env);
	$getEnv = [];
	foreach($env as $data){
		$data = explode("=",$data);
		$getEnv[$data[0]] = $data[1];
	}
	$env = $getEnv;
	unset($getEnv);

	//var_dump($env);

$user = $env['SMSuser'];
$pass = $env['SMSpass'];

$key = isset($_GET['key']) ? $_GET['key'] : 0;
$groupId = isset($_GET['groupId']) ? $_GET['groupId'] : 0;

if((isset($key) && $key !== $env['key']) || !isset($key)) {
	echo "Access Denied!";
	die();
}

$allowedHost = $env['allowedhosts'];
if(strpos($allowedHost, $_SERVER['REMOTE_ADDR']) === false && strpos($allowedHost, $_SERVER['HTTP_X_FORWARDED_FOR']) === false) {
	echo "Access Denied!";
	die();
}

if(isset($groupId) && $groupId > 0){

	$soapclient = new SoapClient($env['voipprovider']);
	$param=array('login'=>$user,'secret'=>$pass,'groupid'=>$groupId);
	$response =$soapclient->DeactivateEndpointGroup($param);
	$result = json_encode($response);
	$outResult = json_decode($result,true)["DeactivateEndpointGroupResult"]["responseMessage"];

	//var_dump(json_decode($result,true)["AuditDIDForwardsResult"]["responseMessage"]);
	if($outResult == "Success") echo $result;
	else echo $outResult;
	//var_dump($outResult);

} else echo "No GroupId was provided!";

?>