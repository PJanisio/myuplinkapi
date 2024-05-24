<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.11.8
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#INDEX FILE


/*
That is an index and an example at once. Can be used in a browser, or as an event file like cronjob.
*/

//include autoloader for classes
include('src/autoloader.php');
spl_autoload_register('autoloader');



//start main class and fetch config
$nibe = new myuplink('config.php'); //best practise - use absolute path
    

    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE)
    
    {
        //if authorized switching to class which get data
        $nibeGet = new myuplinkGet($nibe);
        //check if API is online
        $nibeGet->pingAPI();
        //get all parameters from device and save to jSON
        $nibeGet->getDevicePoints();
    }
    
    
    
    
?>