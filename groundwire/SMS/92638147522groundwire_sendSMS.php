<?php

//echo $_SERVER['REMOTE_ADDR'];

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

if(isset($_GET['key']) && $_GET['key'] == $env['pbxoutboundmmskey']){

//Reduce picture function
function picture($ext) {
    if($ext == 'image/jpeg' || $ext == 'image/gif' || $ext == 'image/png' || $ext == 'image/jpg'){
		//$output = shell_exec("convert ".$source." -resize 1024x768\> ".$source);
    	return true;
	} else {
		//echo "No valid extension to compress!";
		return false;
	}
}

$sender = isset($_GET['from']) ? trim(str_replace("+","",base64_decode($_GET["from"]))) : '';
$recipient = isset($_GET['to']) ? trim(str_replace("+","",base64_decode($_GET["to"]))) : '';
$message = isset($_GET['body']) ? trim(str_replace("___"," ",base64_decode($_GET['body']))) : '';
$data = isset($_GET['data']) ? trim(base64_decode($_GET['data'])) : '';
$domain = isset($_GET['domain']) ? trim(base64_decode($_GET['domain'])) : '';

$data = str_replace("https://","",$data);
$datapieces = explode(",", $data);
$datapieces[0] = str_replace("{attachments:[{content-size:","content-size:",$datapieces[0]);
$message = /*"'".*/str_replace("body:","",str_replace("}","",str_replace("___"," ",$datapieces[count($datapieces) - 1])))/*."'"*/;

//var_dump($message);

$data = array();
$count = 0;
for($i = 0;$i<count($datapieces) - 1;$i++){
	$datatemp = explode(":", $datapieces[$i]);
	$datatemp[0] = str_replace("{","",$datatemp[0]);
	if(isset($data[$count][$datatemp[0]])) $count = $count + 1;
	$data[$count][$datatemp[0]] = str_replace("}","",str_replace("}]","",$datatemp[1]));
}

//var_dump($data);

//$data = json_decode($data,true);

//var_dump($data);

//$data['attachments'][0];
//$data['attachments'][0]['content-type'];
//$data['attachments'][0]['content-url'];
//$data['attachments'][0]['encryption-key'];
//$data['attachments'][0]['hash'];

$SMSuser = $env['SMSuser'];
$SMSpass = $env['SMSpass'];
$iv = '00000000000000000000000000000000';
$encfile = "";
$decfile ="";
$i = 0;
$files = array();

$total = 0;
$img = false;
for($j = 0; $j < count($data); $j++){
	$total = $total + intval($data[$j]['content-size']);
	if (picture($data[$i]['content-type']) == true) $img = true;
	//var_dump(intval($data[$j]['content-size']));
}
//var_dump($total);
if($total < 2048000 || ($total > 2048000 && $img == true)) {

for($i = 0; $i < count($data); $i++){
	if((intval($data[$i]['content-size']) > 2048000 && picture($data[$i]['content-type']) == true) || (intval($data[$i]['content-size']) < 2048000 && picture($data[$i]['content-type']) == false) || (intval($data[$i]['content-size']) < 2048000)){
	
		echo "For for ".$i." until ".count($data);
		$url = "https://".$data[$i]['content-url'];
		$key = $data[$i]['encryption-key'];
		$ext = explode('/', $data[$i]['content-type']);
		$encfile = "encfile".$data[$i]['hash'].".".$ext[1];
		$decfile = "decfile".$data[$i]['hash'].".".$ext[1];
	
		//var_dump($encfile);
		//var_dump($decfile);
	
		$myencfile = fopen("temp/".$encfile, "a") or die("Unable to open file!");
		fwrite($myencfile, file_put_contents("temp/".$encfile, file_get_contents($url)));
		//echo "<br/>openssl enc -aes-128-ctr -d -K ".$key." -iv ".$iv." -nopad -in temp/".$encfile." -out temp/".$decfile;
		$output = shell_exec("openssl enc -aes-128-ctr -d -K ".$key." -iv ".$iv." -nopad -in temp/".$encfile." -out temp/".$decfile);
		if(intval($data[$i]['content-size']) > 512000){
			//$compress_result = mycompress("temp/".$decfile, $ext);
			if($ext[1] == 'jpeg' || $ext[1] == 'gif' || $ext[1] == 'png' || $ext[1] == 'jpg'){
				$outputconvert = shell_exec("convert "."temp/".$decfile." -resize 1024x768 "."temp/".$decfile);
				echo $outputconvert;
    			//return $output;
			} else {
				echo "No valid extension to compress!";
				//return false;
			}
			//if (!unlink("temp/".$decfile)) echo ("$decfile cannot be deleted due to an error");
			//$decfile = "com_" . $decfile;
		}
		//if(!isset($output)) echo ;
		//sleep for 3 seconds
		//sleep(5);
		$files[$i] = array("FileName"=>$decfile,"FileContent"=>file_get_contents("temp/".$decfile));
		//array_push($files,"MMSFile"=>["FileName"=>$decfile,"FileContent"=>base64_decode(file_get_contents("temp/".$decfile))]);
		// Use unlink() function to delete a file  
		if (!unlink("temp/".$encfile)) echo ("$encfile cannot be deleted due to an error");  
		if (!unlink("temp/".$decfile)) echo ("$decfile cannot be deleted due to an error");  
		
	} else {
		echo "File it is to big to be send; action skiped; only less of 2 mb allowed!";
		$files[$i] = array("FileName"=>"Attachemnt_error.png","FileContent"=>file_get_contents("lib/pictures/error.png"));
	}
}

} else {
	echo "The attachemnts are bigger than the allowed size!; Operation Canceled!";
	$files[0] = array("FileName"=>"Attachemnt_error.png","FileContent"=>file_get_contents("lib/pictures/error.png"));
	$message = "The attachemnts are bigger than the allowed size!; Operation Canceled!; Maximun it is 2 mb! -> " . $message;
}

try{
$soapclient = new SoapClient($env['voipprovider']);
$param=array('login'=>$SMSuser,'secret'=>$SMSpass,'sender'=>$sender,'recipient'=>$recipient,'message'=>$message, 'files'=>$files);
	
	$response =$soapclient->SendMMS($param);
	$result = json_encode($response);
	echo $result;
	$smsresult = $response->SendMMSResult->responseMessage;
	if($smsresult != 'Success' && $smsresult != 'Invalid sender' && $smsresult != 'Invalid TN. Sender and recipient must be valid phone numbers and include country code.') $smsresult = 'MMS Error';
	
	$comment = "The MMS Service for recipient " . $recipient . " from " . $sender . " with text: ".$message."; responded: " . $smsresult . "!";
	
	if($domain == $env['305pbxdomainuuid']){ //305 PBX domain ID only
		//$comment = str_replace(" ","___",$comment);
    	$comment = base64_encode($comment);
    	if(strlen($sender) > 10) $sender = substr($sender,1);
    	if(strlen($recipient) > 10) $recipient = substr($recipient,1);
    
    	$crmNotification = $env['crm2getmessagenotificationextapp']."?from=" . $sender . "&to=" . $recipient . "&text=".$comment . "&type=sms";
    	//Send comment to groundwire with curl ##############################
		$curl_error = "";
		$cURLConnection = curl_init();
		curl_setopt($cURLConnection, CURLOPT_URL, str_replace(" ","%20",$crmNotification));
		curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
		$notify_crm = curl_exec($cURLConnection);
		//var_dump($sendtogw);
		if($notify_crm === false) $curl_error = curl_error($cURLConnection);
		curl_close($cURLConnection);
	//###################################################################
    		
	}
			
}catch(Exception $e){
	echo $e->getMessage();
}

//log information:
	/*$content['result'] = $result;
	$content['crm_notification_url'] = $crmNotification;
	$content['crm_notification_result'] = $notify_crm;
	$content["GET"] = $_GET;
	$content["date"] = date("F j, Y, g:i a");
	file_put_contents("log_SEND_SMS.txt", print_r($content, true), FILE_APPEND);*/

/**/
	
} else echo "Access Denied!";

?>