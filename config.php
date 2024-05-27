<?php
#CONFIG FILE
#CREATE APP ON myuplink.com to get data

//array of config data
$config =
	        [
			'clientID' => 'xxxxxx', //from dev.myuplink.com
			'clientSecret' => 'xxxxxxx', //from dev.myuplink.com
			'redirectUri' => 'https://xxxxxxxx', // from dev.myuplink.com - your absolute path where index.php is stored
			'jsonOutPath' => '/www/xxxxxxxx/json/', //your absolute path when you will store json files as well as token.json
			'scope' => 'READSYSTEM WRITESYSTEM offline_access', //dont change
			'curl_http_version' =>    '\CURL_HTTP_VERSION_1_1', //dont change
			'debug' => FALSE //TRUE = var_dump of inputs and outputs, set to TRUE if your app is not working
        	];
        	


			
//do not change	
//array of possible endpoints in myUplink API  
//See description in swagger: https://api.myuplink.com/swagger/index.html
$endpoints =
	        [
		    'system' => '/v2/systems/me',
		    'devicePoints' => '/v3/devices/{deviceId}/points',
		    'aidMode' => '/v2/devices/{deviceId}/aidMode',
		    'device' => '/v2/devices/{deviceId}',
		    'smart-home-cat' => '/v2/devices/{deviceId}/smart-home-categories',
		    'smart-home-zones' => '/v2/devices/{deviceId}/smart-home-zones',
		    'smart-home-mode' => '/v2/systems/{systemId}/smart-home-mode',
		    'firmware' => '/v2/devices/{deviceId}/firmware-info',
		    'active-alerts' => '/v2/systems/{systemId}/notifications/active',
		    'all-alerts' => '/v2/systems/{systemId}/notifications',
		    'ping' => '/v2/ping', //success when HTTPCODE == 204 
		    'premium' => '/v2/systems/{systemId}/subscriptions' //will return 204 if subscription is not available	    
        	];

