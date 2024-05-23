<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.7.6
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#GET data class


class myuplinkGet extends myuplink {

    //define main variables
    public $system = array();
    
    
    
    
    /*
    /Construct will get main system variables like systemID to fetch further data
    */
    public function __construct($myuplink) {
     
     
     $this->myuplink = $myuplink;
        //will save to json
        $system = $this->myuplink->getData($this->myuplink->endpoints['system']);
        
        //is it possible to array push to config systemId?
        
        return $this->system;
    }
    

    

}  //end of class

?>