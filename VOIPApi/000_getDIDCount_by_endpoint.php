<?php

require "./000_include_file.php";

	$payloadLogin=array(
        
        'refreshToken' => $refreshToken
        
    );
    //var_dump($payloadLogin);
	$payloadLogin = /*urlencode(*/json_encode($payloadLogin)/*)*/;
	//var_dump($payloadLogin);
	
	$url = $urlApi."auth/refreshToken";
	
	$headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Content-Length: ' . strlen($payloadLogin);
    $headers[] = 'Accept: */*';

	//return executeCurlGet($url,$headers,$payloadLogin);

?>