<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 1.2.6
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#INDEX FILE


/*
That is an index and an example at once. Can be used in a browser, or as an event file like cronjob.
*/

//include autoloader for classes
include(__DIR__.'/src/autoloader.php');

//start main class and fetch config
$nibe = new myuplink(__DIR__.'/config.php'); 
    
    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE)
    {
        //if authorized switching to class which get data
        $nibeGet = new myuplinkGet($nibe);

        
        //get all possible endpoints, put to array and save to jSON 
        $nibeGet->getAll();

    }
    
    
    
    
?>