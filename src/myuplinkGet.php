<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.13.9
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#GET data class


class myuplinkGet extends myuplink
{

    //define main variables
    public $myuplink;
    public $system;
    public $pingAPI = FALSE;
    public $systemInfo = array();
    public $devicePoints = array();
    public $aidMode = array();
    

    /*
    /Construct will get main system variables like systemID to fetch further data
    */
    public function __construct($myuplink)
    {
     
     $this->myuplink = $myuplink;
        //make first get from api and fetch main system info
        $this->system = $this->myuplink->getData($this->myuplink->endpoints['system']);
		
	    //we doesnt want to deep dive into multidimensional arrays or objects, lets flatten them
		$this->systemInfo['numItems'] = intval($this->system['numItems']); //number of systems in myuplinkapi
		$this->systemInfo['systemId'] = strval($this->system['systems'][0]['systemId']); 
		$this->systemInfo['name'] = strval($this->system['systems'][0]['name']); //owner name
		$this->systemInfo['hasAlarm'] = $this->system['systems'][0]['hasAlarm'];
		$this->systemInfo['country'] = strval($this->system['systems'][0]['country']);
		$this->systemInfo['deviceId'] = strval($this->system['systems'][0]['devices'][0]['id']);
		$this->systemInfo['currentFwVersion'] = strval($this->system['systems'][0]['devices'][0]['currentFwVersion']);
        $this->systemInfo['serialNumber'] = strval($this->system['systems'][0]['devices'][0]['product']['serialNumber']);
        $this->systemInfo['deviceName'] = strval($this->system['systems'][0]['devices'][0]['product']['name']); //device name
        
        $this->myuplink->debugMsg('DEBUG: Nibe Systems: ', $this->systemInfo);

        return $this->systemInfo;
        
        
    }
    

     /*
    Get function to check iof API is online
    result TRUE when online
    */
    public function pingAPI() 
    
    {
        //send request to API (204 response, w/o save to jSON)
        $this->pingAPI = $this->myuplink->getData($this->myuplink->endpoints['ping'], 204, 0);  
        //return
        return $this->pingAPI;
        
    }
    
    
    
    /*
    Get function to receive all parameters from device
    save to json
    returns array of parameters
    */
    public function getDevicePoints() 
    
    {
        //TODO!
        //raw endpoints has to be changed with variables from systemInfo f.e {deviceId} == $this->systemInfo['deviceId']
        //currently its just overwriting variables, need to find general solution :)
        
        $this->myuplink->endpoints['devicePoints'] = '/v3/devices/'.$this->systemInfo['deviceId'].'/points';
        
        //send request to API
        $this->devicePoints = $this->myuplink->getData($this->myuplink->endpoints['devicePoints']);  
        
        //return array
        return $this->devicePoints;
        
        
    }
    
    
    /*
    Get additional heater status
    save to json
    returns int 1?0
    */
    public function getAidMode() 
    
    {
        //TODO!
        //raw endpoints has to be changed with variables from systemInfo f.e {deviceId} == $this->systemInfo['deviceId']
        //currently its just overwriting variables, need to find general solution :)
        
        $this->myuplink->endpoints['aidMode'] = '/v2/devices/'.$this->systemInfo['deviceId'].'/aidMode';
        
        //send request to API
        $this->aidMode = $this->myuplink->getData($this->myuplink->endpoints['aidMode']);  
        
        //return int
        return $this->aidMode;
        
        
    }
    

}  //end of class


?>