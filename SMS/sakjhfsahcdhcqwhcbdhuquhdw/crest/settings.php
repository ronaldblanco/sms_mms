<?php

//Get ENV
	$env = file_get_contents('../../../../.env', true);
	$env = explode("\n",$env);
	$getEnv = [];
	foreach($env as $data){
		$data = explode("=",$data);
		$getEnv[$data[0]] = $data[1];
	}
	$env = $getEnv;
	unset($getEnv);

//define('C_REST_CLIENT_ID','local.1234');//Application ID
//define('C_REST_CLIENT_SECRET','1234');//Application key
// or
define('C_REST_WEB_HOOK_URL',$env['bitrixwebhook']);//url on creat Webhook

//define('C_REST_CURRENT_ENCODING','windows-1251');
//define('C_REST_IGNORE_SSL',true);//turn off validate ssl by curl
define('C_REST_LOG_TYPE_DUMP',false); //logs save var_export for viewing convenience
define('C_REST_BLOCK_LOG',false);//turn off default logs
//define('C_REST_LOGS_DIR', __DIR__ .'/logs/'); //directory path to save the log