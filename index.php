<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.7.5
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

# EXAMPLE


include('src/myuplink.php');
include('src/myuplink_get.php');

$nibe = new myuplink('config.php'); //best practise - use absolute path
    

    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE) {
        
        //if authorized switching to class which parse data
        $nibeGet = new myuplinkGet($nibe);
    
    }

?>