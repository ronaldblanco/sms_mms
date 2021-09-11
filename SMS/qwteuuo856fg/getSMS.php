<?php
require_once (__DIR__.'/crest/crest.php');

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

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open(__DIR__.'/db/mms.db');
    }
}

$recipient = isset($_GET['to']) ? trim(str_replace("+","",$_GET["to"])) : '';
$recipientfix = $recipient;
if(strlen($recipientfix) > 10) $recipientfix = substr($recipientfix, 1, 10);
$message = isset($_GET['text']) ? $_GET["text"] : '';
$sender = isset($_GET['from']) ? $_GET["from"] : '';
$senderfix = $sender;
if(strlen($senderfix) > 10) $senderfix = substr($senderfix, 1, 10);
$type = isset($_GET['messageType']) ? $_GET["messageType"] : '';
$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];
if($type === "MMS" && $token != "" && $token == $env['voipkey']){
	$files = isset($_GET['fileUrls']) ? $_GET["fileUrls"] : "['']";
	$files = ltrim($files, "['");
	$files = rtrim($files, "']");
	$filesArr = explode("', '", $files);
	if(count($filesArr) > 1) $files = str_replace("', '", ", ", $files);

	$db = new MyDB();
	//var_dump($db);
	//$db->exec('CREATE TABLE foo (bar STRING)');
	$execution = $db->exec("INSERT INTO mms VALUES('".$recipient."','".$sender."','".$message."','".$files."','".$_GET['date']."',0)");
	//var_dump($execution);

	//Write to log
	$content = $_GET;
	$content["date"] = date("F j, Y, g:i a");
	file_put_contents("log_".$recipient.".txt", print_r($content, true), FILE_APPEND);

	if($execution) die("MMS was receibed and keep!");
	else {
    	// sleep for 5 seconds in the case of error!
		sleep(5);
    	die("An Error ocurred with the MMS!");
    }
	
	//die("MMS was receibed and keep!");
}
//To notify the user in it groundwire phone application.

$groundwire = $env['crm2getmessagenotificationextapp']."?token=".$token."&from=".$sender."&to=".$recipient."&text=";

if($token != "" && $token == $env['voipkey']){

if($recipient != "" && ($message != "" || $files != "") && $sender != ""){
	
	$user = ( CRest :: call (
    	'user.get' ,
   		[
	  		'FILTER' => ["PERSONAL_MOBILE" => $recipient],
   		])
	);
	//var_dump($user['result']);
	
	if(!isset($user['result'][0]['ID'])){
		$user = ( CRest :: call (
    		'user.get' ,
   			[
	  			'FILTER' => ["PERSONAL_MOBILE" => substr($recipient,1,10)],
   			])
		);
		//var_dump($user['result']);
	}
	
	$contact = ( CRest :: call (
    'crm.contact.list' ,
   	[
	  'FILTER' => ["PHONE" => $sender],
	  'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   	])
	);
	//echo '1 try!';
	//var_dump($contact['result']);
	
	if(!isset($contact['result'][0]['ID'])){
		$contact = ( CRest :: call (
    	'crm.contact.list' ,
   		[
	  	'FILTER' => ["PHONE" => substr($sender,1,10)],
	  	'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   		])
		);
		//echo '2 try!';
		//var_dump($contact['result']);
	}
	
	$contactLink = "<a href='".$env['bitrixcontactlink']."/".$contact['result'][0]['ID']."/'>". $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']. "</a>";
	$comment = "A SMS was received from sender: " . $sender . ", contact: " . $contactLink . ", with the message: " . $message . "!";
	$externalmessage = base64_encode("You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'].", with the message: " . $message . "!");
	
	if($type === "MMS") {
	//file_put_contents("temp/temp.jpg",file_get_contents($filesArr[0]));
	//var_dump($_SERVER['DOCUMENT_ROOT']);
		$i = 0;
		$uploadfiles = "";
		
		foreach($filesArr as $file){
			
	//$fileext = substr($file, -3);
			$fileext = end(explode(".", $file));
		
			
	$upload = ( CRest :: call (
    'disk.folder.uploadfile' ,
   	[
		//'id' => 4,
		'id' => $contact['result'][0]['UF_CRM_1594739199'],
		'data' =>
           [
               "NAME" => $contact['result'][0]['ID']."_".$i."_MMS_".time()."_.".$fileext,
           ],
		
		'fileContent' => base64_encode(file_get_contents($file))
   	])
	);
		
		$uploadfiles = $uploadfiles . str_replace(" ","%20",$upload["result"]["DETAIL_URL"]) . "\r\n\r\n";
		$i = $i + 1;	
		}
	
    
	$comment = "A MMS was receibed from sender: " . $sender . ", contact: " . $contactLink . ", with the message: " . $message . " and files! The new files were uploaded as " . $uploadfiles." in ubication ".$contact['result'][0]['UF_CRM_1594736087']."!";
		$externalmessage = "You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'].", with the message: " . $message . " and files ".$uploadfiles."; to contact go " . "https://crm.305plasticsurgery.com/crm/contact/details/".$contact['result'][0]['ID']."/!";
	}
	
	//Send comment to groundwire with curl ##############################
	$curl_error = "";
	$cURLConnection = curl_init();
	curl_setopt($cURLConnection, CURLOPT_URL, str_replace(" ","%20",$groundwire.$externalmessage));
	curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
	$sendtogw = curl_exec($cURLConnection);
	//var_dump($sendtogw);
	if($sendtogw === false) $curl_error = curl_error($cURLConnection);
	curl_close($cURLConnection);
	//###################################################################
	//Send comment to groundwire
	//$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));
	//var_dump($groundwire."You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']."!");
	//var_dump($sendtogw);
	
	$timeline = ( CRest :: call (
    'crm.timeline.comment.add' ,
   	[
		'fields' =>
           [
               "ENTITY_ID" => $contact['result'][0]['ID'],
               "ENTITY_TYPE" => "contact",
               "COMMENT" => $comment,
           ]
   	])
	);
	//var_dump($timeline);
	
	if(isset($user['result'][0]['ID'])){
	
	$setmessage = ( CRest :: call (
    	'im.notify' ,
   		[
			"to" => $user['result'][0]['ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
	);
	//var_dump($setmessage);
	$result = ['result' => 'true'];
		
	}
	
	//If contacted user it is not Responsable!
	if($user['result'][0]['ID'] != $contact['result'][0]['ASSIGNED_BY_ID']){
		
		$setmessagetoresp = ( CRest :: call ( //Send Notification to responsable in case it is not the contacted user.
    	'im.notify' ,
   		[
			"to" => $contact['result'][0]['ASSIGNED_BY_ID'],
         	"message" => $comment,
         	"type" => 'SYSTEM',
   		])
		);
		
		$usermanager = ( CRest :: call ( //Find Manager
    		'user.get' ,
   			[
	  			'FILTER' => 
				[
					"UF_DEPARTMENT" => $user['result'][0]['UF_DEPARTMENT'],
					"WORK_POSITION" => "Manager"
				],
   			])
		);
		if(isset($usermanager['result'][0]['ID'])){
			$setmanagermessage = ( CRest :: call (
    			'im.notify' ,
   				[
					"to" => $usermanager['result'][0]['ID'],
         			"message" => "A Message was sent to user ".$user['result'][0]['NAME']." ".$user['result'][0]['LAST_NAME']." by a contact it is not in his/her responsibility; the message content is '".$comment."'!",
         			"type" => 'SYSTEM',
   				])
			);
		}
		
	}
	
} else {
	echo "There it is no Information to work!</br>";
}

echo $result;

} else {
	echo "Access Denied!</br>";
}

//Only for TESTs!
//fwrite($myfile, file_put_contents("log.txt", ob_get_flush()).PHP_EOL);
//fclose($myfile);

/*$content = $_GET;
$content['PBX_response'] = $sendtogw;
$content['curl_error'] = $curl_error;
$content["date"] = date("F j, Y, g:i a");
file_put_contents("log_".$recipient.".txt", print_r($content, true), FILE_APPEND);*/


?>