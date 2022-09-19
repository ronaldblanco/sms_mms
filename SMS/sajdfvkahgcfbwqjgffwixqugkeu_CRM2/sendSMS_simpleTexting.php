<?php

$curl = curl_init();

$media = array('https://vixencrm.s3.us-east-2.amazonaws.com/logos/305_menu_logo.png');
$parametersMms = array(
'token'=>'e59330c8c97bfcb08bc1ef07865a139f',
'accountPhone'=>'8335010871',
'phone'=>'7863342521',
'message'=>'test_mms',
'subject'=>'test_mms',
'mediaUrl' => $media
);
$parametersSms = array(
'token'=>'e59330c8c97bfcb08bc1ef07865a139f',
'accountPhone'=>'8335010871',
'phone'=>'7863342521',
'message'=>'test_sms',
'subject'=>'test_sms'

);
$queryParameters = http_build_query($parameters);

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://app2.simpletexting.com/v1/sendmms?".$queryParametersMms,
  //CURLOPT_URL => "https://app2.simpletexting.com/v1/send?".$parametersSms,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json",
    "content-type: application/x-www-form-urlencoded"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}

/*
 *

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://app2.simpletexting.com/v1/sendmms?token=YOUR_API_TOKEN&phone=SOME_STRING_VALUE&message=SOME_STRING_VALUE&subject=SOME_STRING_VALUE&mediaUrl=SOME_ARRAY_VALUE",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "accept: application/json",
    "content-type: application/x-www-form-urlencoded"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}*/

?>