<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.6.5
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

# EXAMPLE


include('src/myuplink.php');

$nibe = new myuplink('config.php'); //best practise - use absolute path

    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE) {
    
    //get data from api/device
    $nibe->getData($nibe->endpoints['system']);
    
    }

?>