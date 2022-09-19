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
$did = isset($_GET['did']) ? $_GET['did'] : "";
$forward = isset($_GET['forward']) ? $_GET['forward'] : "";

if((isset($key) && $key !== $env['key']) || !isset($key)) {
	echo "Access Denied!";
	die();
}

$allowedHost = $env['allowedhosts'];
if(strpos($allowedHost, $_SERVER['REMOTE_ADDR']) === false && strpos($allowedHost, $_SERVER['HTTP_X_FORWARDED_FOR']) === false) {
	echo "Access Denied!";
	die();
}

if(
	isset($did) && $did != "" &&
    isset($forward) && $forward != ""
){

	$soapclient = new SoapClient($env['voipprovider']);
	$param=array('login'=>$user,'secret'=>$pass,'tn'=>$did,'forward'=>$forward);
	$response =$soapclient->SetDIDForward($param);
	$result = json_encode($response);
	$outResult = json_decode($result,true)["SetDIDForwardResult"]["responseMessage"];

	//var_dump(json_decode($result,true)["AuditDIDForwardsResult"]["responseMessage"]);
	if($outResult == "Success") echo $result;
	else echo $outResult;
	//var_dump($outResult);

} else echo "No did and forward was provided!";

?>