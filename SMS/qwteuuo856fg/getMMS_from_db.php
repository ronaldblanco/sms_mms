<?php

if(isset($_SERVER['REMOTE_ADDR']) == false){ //Running from command allowed

require_once (__DIR__.'/crest/crest.php');

//Get ENV
	$env = file_get_contents('/home/techcnet/.env', true);
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

$db = new MyDB();

$result = $db->query('SELECT * FROM mms WHERE readyf = 0 LIMIT 6;'); //Select not proceced MMS messages

$mydata = array();
while($row = $result->fetchArray()) {
	//Mark as ready before to proceed (in cases of delays this will avoid too many repetitions of the same request!)
	array_push($mydata,$row); //Get all rows informations in array
	$update = $db->exec("UPDATE mms SET readyf = 1 WHERE tof = '".$row['tof']."' AND fromf = '".$row['fromf']."' AND textf = '".$row['textf']."' AND fileurls = '".$row['fileurls']."';"); //Update row
}

//while($row = $result->fetchArray()) {
foreach($mydata as $row) { //Start normal operations with the MMS
		//Operations with MMS##########################
	
	$groundwire = $env['crm2getmessagenotificationextapp']."?token=".$env['voipkey']."&from=".$row['fromf']."&to=".$row['tof']."&text=";
	
	//Calling user and Contact:####################
	$user = ( CRest :: call (
    	'user.get' ,
   		[
	  		'FILTER' => ["PERSONAL_MOBILE" => $row['tof']],
   		])
	);
	//var_dump($user['result']);
	
	if(!isset($user['result'][0]['ID'])){
		$user = ( CRest :: call (
    		'user.get' ,
   			[
	  			'FILTER' => ["PERSONAL_MOBILE" => substr($row['tof'],1,10)],
   			])
		);
		//var_dump($user['result']);
	}
	
	$contact = ( CRest :: call (
    'crm.contact.list' ,
   	[
	  'FILTER' => ["PHONE" => $row['fromf']],
	  'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   	])
	);
		
	if(!isset($contact['result'][0]['ID'])){
		$contact = ( CRest :: call (
    	'crm.contact.list' ,
   		[
	  	'FILTER' => ["PHONE" => substr($row['fromf'],1,10)],
	  	'SELECT' => ['ID','ASSIGNED_BY_ID','NAME','LAST_NAME','UF_CRM_1594739199','UF_CRM_1594736087'],
   		])
		);
		
	}
	//#########################

		$i = 0;
		$uploadfiles = "";
		
		$filesArr = explode(", ", $row['fileurls']);
		foreach($filesArr as $file){
		
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
		
		$uploadfiles = $uploadfiles . str_replace(" ","%20",$upload["result"]["DETAIL_URL"]) . " || ";
		$i = $i + 1;	
		}
	
    $contactLink = "<a href='https://crm.305plasticsurgery.com/crm/contact/details/".$contact['result'][0]['ID']."/'>". $contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME']. "</a>";
	$comment = "A MMS was receibed from sender: " . $row['fromf'] . ", contact: " . $contactLink . ", with the message: " . $row['textf'] . " and files! The new files were uploaded as " . $uploadfiles." in ubication ".str_replace(" ","%20",$contact['result'][0]['UF_CRM_1594736087'])."!";
	$externalmessage = base64_encode("You have a new message in the CRM from contact: ".$contact['result'][0]['NAME'] . " " . $contact['result'][0]['LAST_NAME'].", with the message: " . $row['textf'] . " and files ".$uploadfiles."; to contact go " . "https://crm.305plasticsurgery.com/crm/contact/details/".$contact['result'][0]['ID']."/!");


	//Send comment to groundwire with curl ##############################
	$curl_error = "";
	$cURLConnection = curl_init();
	curl_setopt($cURLConnection, CURLOPT_URL, str_replace(" ","%20",$groundwire.$externalmessage));
	curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, false);
	$sendtogw = curl_exec($cURLConnection);
	//$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));
	//var_dump($sendtogw);
	if($sendtogw === false) $curl_error = curl_error($cURLConnection);
	curl_close($cURLConnection);
	//###################################################################
	//Send comment to groundwire
	//$sendtogw = file_get_contents(str_replace(" ","%20",$groundwire.$externalmessage));

	//Bitrix notifications###############################
	
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
	
	//###################################################

	//End of the MMS operations###################
	//$update = $db->exec("UPDATE mms SET readyf = 1 WHERE tof = '".$row['tof']."' AND fromf = '".$row['fromf']."' AND textf = '".$row['textf']."' AND fileurls = '".$row['fileurls']."';"); //Update row
	//var_dump($update);
	if($update) echo "MMS proceced, from ". $row['fromf']." to ". $row['tof']. " sucessfull!\r\n";
	else echo "An error ocurred during the operation!\r\n";

	//log information:
	/*$content = $row;
	$content['PBX_response'] = $sendtogw;
	$content['curl_error'] = $curl_error;
	$content["date"] = date("F j, Y, g:i a");
	file_put_contents($env['phplogfile']."log_db_".$row['tof'].".txt", print_r($content, true), FILE_APPEND);*/
}
//echo "Run -> ". date("F j, Y, g:i a")."\n\r";

} else echo "Access Denied!";

?>