<?php

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