<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.0.1
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

# EXAMPLE

include('myuplink.php');

$nibe = new myuplink('config.php');

    //var_dump($nibe->config);

    echo $nibe->authURL();


?>