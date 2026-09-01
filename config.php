<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

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
$endpoints = [

    // MAIN SYSTEM & DEVICE ENDPOINTS
    'system' => '/v2/systems/me',                                // Fetches all systems belonging to the authenticated user
    'systemById' => '/v2/systems/{systemId}',                    // Fetches specific system details by its ID
    'device' => '/v2/devices/{deviceId}',                        // Fetches basic information about a specific device
    'firmware' => '/v2/devices/{deviceId}/firmware-info',        // Fetches current and available firmware versions
    'aidMode' => '/v2/devices/{deviceId}/aidMode',               // Fetches additional heater status/mode
    'ping' => '/v2/ping',                                        // Success when HTTPCODE == 204
    'premium' => '/v2/systems/{systemId}/subscriptions',         // Will return 204 if subscription is not available      
    
    // DATA & ALERTS ENDPOINTS 
    'devicePoints' => '/v3/devices/{deviceId}/points',           // Recommended V3 endpoint for fetching device parameters
    'active-alerts' => '/v2/systems/{systemId}/notifications/active', // Fetches currently active alerts/alarms
    'all-alerts' => '/v2/systems/{systemId}/notifications',      // Fetches all historical alerts
    'serviceInfoCategories' => '/v2/systems/{systemId}/serviceinfo/categories', // Fetches service info and maintenance data

    // LEGACY / ALTERNATIVE & SMART HOME ENDPOINTS 
    // Note: Nibe is moving away from separate Smart Home API endpoints.
    // For Smart Home Thermostats, it is now recommended to use standard device points and filter by 'smartHomeCategories'.
    'devicePointsV2' => '/v2/devices/{deviceId}/points',         // V2 alternative for points, often used by Home Assistant or older devices
    'smart-home-cat' => '/v2/devices/{deviceId}/smart-home-categories', // Legacy endpoint for Smart Home categories
    'smart-home-zones' => '/v2/devices/{deviceId}/smart-home-zones',    // Legacy endpoint for Smart Home zones
    'smart-home-mode' => '/v2/systems/{systemId}/smart-home-mode'       // Legacy endpoint for Smart Home modes
    
];

