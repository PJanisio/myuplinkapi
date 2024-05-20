<?php

#CONFIG FILE
#CREATE APP ON myuplink.com to get data

//array of config data
$config =
	        [
		    "clientID" => 'xxxxxxxx',
		    "clientSecret" => 'xxxxxxxxx',
		    'redirectUri'		=> 'xxxxxxxxx',
			'tokenPath'   => '/xxx/xxx/xxxx/json/token.json', //use absolute path
            'scope' => 'READSYSTEM WRITESYSTEM offline_access',
			'curl_http_version' => '\CURL_HTTP_VERSION_1_1',
		    'debug'		=> TRUE
        	];

?>