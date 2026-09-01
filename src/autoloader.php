<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#CLASS AUTOLOADER

/*
You can remove this file and load classes manually if you need
*/

function autoloader( $class_name )
{
    $file = __DIR__.'/'.$class_name.'.php';
    if (file_exists($file)) 
    {
        require_once $file;
    }
}

spl_autoload_register('autoloader');

?>