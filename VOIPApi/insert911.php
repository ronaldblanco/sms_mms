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

$address1 = isset($_GET['address1']) ? $_GET['address1'] : "";
$address2 = isset($_GET['address2']) ? $_GET['address2'] : "";
$city = isset($_GET['city']) ? $_GET['city'] : "";
$state = isset($_GET['state']) ? $_GET['state'] : "";
$zip = isset($_GET['zip']) ? $_GET['zip'] : "";
$plusFour = isset($_GET['plusFour']) ? $_GET['plusFour'] : "";
$callerName = isset($_GET['callerName']) ? $_GET['callerName'] : "";

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
isset($address1) && $address1 != "" &&
isset($address2) && $address2 != "" &&
isset($city) && $city != "" &&
isset($state) && $state != "" &&
isset($zip) && $zip != "" &&
isset($plusFour) && $plusFour != "" &&
isset($callerName) && $callerName != ""
){

	$soapclient = new SoapClient($env['voipprovider']);
	$param=array('login'=>$user,'secret'=>$pass,'did'=>$did,'address1'=>$address1,'address2'=>$address2,'city'=>$city,'state'=>$state,'zip'=>$zip,'plusFour'=>$plusFour,'callerName'=>$callerName,);
	$response =$soapclient->insert911($param);
	$result = json_encode($response);
	$outResult = json_decode($result,true)["insert911Result"]["responseMessage"];

	//var_dump(json_decode($result,true)["AuditDIDForwardsResult"]["responseMessage"]);
	if($outResult == "Success") echo $result;
	else echo $outResult;
	//var_dump($outResult);

} else echo "No all data was provided!";

?>