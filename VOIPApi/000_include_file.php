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

if((isset($key) && $key !== $env['key']) || !isset($key)) {
	echo "Access Denied!";
	die();
}

$allowedHost = $env['allowedhosts'];
if(strpos($allowedHost, $_SERVER['REMOTE_ADDR']) === false && strpos($allowedHost, $_SERVER['HTTP_X_FORWARDED_FOR']) === false) {
	echo "Access Denied!";
	die();
}

//Functions/////////////////////////////////////////////////////////////////////////////////////
//CURL Post
function executeCurlPost($url,$headers,$payload){
	$ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
    curl_setopt ( $ch, CURLOPT_TIMEOUT, 300 );
    curl_setopt ( $ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
    $result = curl_exec($ch);
    $curl_error = curl_error($ch);
        
    //var_dump($result);
    if(isset($curl_error) && $curl_error != "") var_dump($curl_error);

    curl_close($ch);
    
    return $result;
}

//CURL Get
function executeCurlGet($url,$headers){
	$ch = curl_init();
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
    curl_setopt ( $ch, CURLOPT_TIMEOUT, 300 );
    curl_setopt ( $ch, CURLOPT_VERBOSE, false);
    //curl_setopt($ch, CURLOPT_POST, true);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
    $result = curl_exec($ch);
    $curl_error = curl_error($ch);
        
    //var_dump($result);
    if(isset($curl_error) && $curl_error != "") var_dump($curl_error);

    curl_close($ch);
    
    return $result;
}
//////////////////////////////////////////////////////////////////////////////////////

?>