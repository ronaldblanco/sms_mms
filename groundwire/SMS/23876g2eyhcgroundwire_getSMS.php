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

$recipient = isset($_GET['to']) ? trim(str_replace("+","",$_GET["to"])) : '';
$message = isset($_GET['text']) ? $_GET["text"] : '';
$sender = isset($_GET['from']) ? $_GET["from"] : '';
$type = isset($_GET['messageType']) ? $_GET["messageType"] : 'SMS';
$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];
if($type === "MMS"){
	$files = isset($_GET['fileUrls']) ? $_GET["fileUrls"] : "['']";
	$files = ltrim($files, "['");
	$files = rtrim($files, "']");
	$filesArr = explode("', '", $files);
	if(count($filesArr) > 1) $files = str_replace("', '", ", ", $files);
}
if($type === "SMS"){ //If SMS with url garanty good url results
	if(strstr( $message, 'https://' ) != false){
		$message = str_replace("Files from MMS","Files%20from%20MMS",$message); //Fix know url error if necesary
		
		
	}
}

if($token != "" && $token == $env['voipkey']){

$domain = $env['pbx1domainforgetsms'];//Domain on the 1 server; A
$pbxhandler = "https://".$domain.$env['pbxhandler'];


if($recipient != "" && ($message != "" || $files != "") && $sender != ""){
		
	//For SMS
	/*curl_setopt($ch, CURLOPT_POSTFIELDS, "from=".$sender."&to=".$recipient."&text=".$message . "&messageType=SMS");
	$out = curl_exec($ch);
	curl_close ($ch);*/
	
	$out = file_get_contents($pbxhandler."?from=".base64_encode($sender)."&to=".base64_encode($recipient)."&text=".base64_encode($message) . "&messageType=SMS");
	//echo $out;
		
	if($type === "MMS") { //For MMS
	//file_put_contents("temp/temp.jpg",file_get_contents($filesArr[0]));
	//var_dump($_SERVER['DOCUMENT_ROOT']);
		$i = 0;
		$uploadfiles = "";
		
		foreach($filesArr as $file){
						
			$uploadfiles = $uploadfiles . $file . ";";
			$i = $i + 1;	
		}
		
		$message = $message . ".Attachments->" . $uploadfiles;
		$out = file_get_contents($pbxhandler."?from=".base64_encode($sender)."&to=".base64_encode($recipient)."&text=".base64_encode($message) . "&messageType=SMS");
		echo $out;
	
	/*$comment = "A MMS was receibed from sender: " . $sender . ", contact: " . $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'] . ", with the message: " . $message . " and files! The new files were uploaded as " . $uploadfiles." in ubication ".$contact['result'][0]['UF_CRM_1594736087']."!";*/	
	}
			
} else {
	echo "There it is no Information to work!</br>";
}

//log information:
	/*$content = $result;
	$content['out_of_pbx'] = $out;
	$content["GET"] = $_GET;
	$content["date"] = date("F j, Y, g:i a");
	file_put_contents("log_GET_SMS.txt", print_r($content, true), FILE_APPEND);*/
	
} else echo "Access Denied!";

?>