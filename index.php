<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#INDEX FILE


/*
That is an index and an example at once. Can be used in a browser, or as an event file like cronjob.
*/

//include autoloader for classes
include(__DIR__ . '/src/autoloader.php');

//start main class and fetch config
$nibe = new myuplink(__DIR__ . '/config.php');

//authorization, getting token and its status
if ($nibe->authorizeAPI() == TRUE) {
    //if authorized switching to class which get data
    $nibeGet = new myuplinkGet($nibe);

    //get all possible endpoints, put to array and save to jSON
    //$data is an array with key = endpoint key
    $data = $nibeGet->getALL();

    // Example: Setting parameters using myuplinkSet
    // WARNING: Changing heat pump parameters via API is risky and can affect your system operation. 
    // Uncomment and use at your own risk.
    /*
    $nibeSet = new myuplinkSet($nibe);
    // Set hot water boost (Mode 1: 3 hr, Mode 2: 6 hr, Mode 3: 12 hr, Mode 4: One-time incr., Mode 0: Off)
    $nibeSet->setHotWaterBoost(1);
    */
}