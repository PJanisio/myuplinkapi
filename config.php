<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.0.1
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#CONFIG FILE
#CREATE APP ON myuplink.com to get data

//array of config data
$config =
	        [
		    "clientID" => 'xxxxxxxx',
		    "clientSecret" => 'xxxxxxxxx',
		    'redirectUri'		=> 'xxxxxxxxx',
            'scope' => 'READSYSTEM WRITESYSTEM offline_access',
		    'debug'		=> FALSE,
        	];

?>