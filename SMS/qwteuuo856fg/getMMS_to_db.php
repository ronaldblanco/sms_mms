<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//phpinfo();

require_once (__DIR__.'/crest/crest.php');

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
//$token = isset($_GET['token']) ? $_GET["token"] : '';
$result = ['result' => 'false'];
if($type === "MMS"){

	$files = isset($_GET['fileUrls']) ? $_GET["fileUrls"] : "['']";
	$files = ltrim($files, "['");
	$files = rtrim($files, "']");
	$filesArr = explode("', '", $files);
	if(count($filesArr) > 1) $files = str_replace("', '", ", ", $files);

$db = new MyDB();

//$db->exec('CREATE TABLE foo (bar STRING)');
$execution = $db->exec("INSERT INTO mms VALUES('".$recipient."','".$sender."','".$message."','".$files."','".$_GET['date']."',0)");
//var_dump($execution);
if($execution) echo "MMS Receibed!";
else echo "An error happen!";

}

?>