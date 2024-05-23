<?php
#CONFIG FILE
#CREATE APP ON myuplink.com to get data

//array of config data
$config =
	        [
			'clientID' => 'xxxxxx', //from dev.myuplink.com
			'clientSecret' => 'xxxxxxx', //from dev.myuplink.com
			'redirectUri' => 'https://xxxxxxxx', //your absolute path where index.php is stored
			'jsonOutPath' => '/www/xxxxxxxx/json/', //your absolute path when you will store json files
			'tokenPath' => '/www/xxxxxxxx/json/token.json', //absolute path for token
			'scope' => 'READSYSTEM WRITESYSTEM offline_access', //dont change
			'curl_http_version' =>    '\CURL_HTTP_VERSION_1_1', //dont change
			'debug' => TRUE //TRUE = dump of information of received data
        	];
        	


			
//do not change	
//array of possible endpoints in myUplink API  
//https://api.myuplink.com/swagger/index.html
$endpoints =
	        [
		    'system' => '/v2/systems/me',
		    'devicePoints' => '/v3/devices/{deviceId}/points'
        	];

?>