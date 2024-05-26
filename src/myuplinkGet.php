<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.16.12
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
    public $systemInfo; //object
    public $devicePoints = array();
    public $aidMode; //object
    public $device;
    public $newEndpoints = array();
    public $smartHomeCat;
    public $smartHomeZones;
    public $smartHomeMode;

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
            //rewrite endpoints and take values from this function
            $this->newEndpoints();

        return $this->systemInfo;
        
        
    }
    
    /*
    Internal function to rewrite all ednpoints and add variables from _construct like systemId and deviceId
    returns array of newEndpoints
    */
    public function newEndpoints()
    {
        
        $toChange = '';
            $new = '';
        
        foreach ($this->myuplink->endpoints as $end => $value )
        {
            
            preg_match_all('/{(.*?)}/', $value, $matches);
            
            //found curly bracket
            if($matches[0])
            {
                
                $toChange = $matches[1][0];
                
                    if($toChange == 'deviceId')
                    {
                      $changed = (string) $this->systemInfo['deviceId'];
                      $new = str_replace('{'.$toChange.'}', $changed, $value);
                      $this->newEndpoints[$end] = $new;
                    }
                    
                     else if($toChange == 'systemId')
                    {
                      $changed = (string) $this->systemInfo['systemId'];
                      $new = str_replace('{'.$toChange.'}', $changed, $value);
                      $this->newEndpoints[$end] = $new;
                    }
                    
                    
            }
            else 
            {
              $this->newEndpoints[$end] = $value;  
                
            }
            
        }
        $this->myuplink->debugMsg('DEBUG: New Endpoints:', $this->newEndpoints);
        
        //overwrite existing endpoints to a new one
        $this->myuplink->endpoints = $this->newEndpoints;
        
        return $this->newEndpoints;
        
    }
    

     /*
    Get function to check iof API is online
    result TRUE when online
    */
    public function pingAPI() 
    
    {
        //send request to API (204 response, w/o save to jSON)
        $this->pingAPI = $this->myuplink->getData($this->newEndpoints['ping'], 204, 0);  
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
        
        //send request to API
        $this->devicePoints = $this->myuplink->getData($this->newEndpoints['devicePoints']);  
        //return array
        return $this->devicePoints;
        
        
    }
    
    
    /*
    Get additional heater status
    save to json
    returns int 1?0 as an object
    */
    public function getAidMode() 
    
    {

        //send request to API
        $this->aidMode = $this->myuplink->getData($this->newEndpoints['aidMode']);  
        //return object
        return $this->aidMode;
        
        
    }
    
    
    /*
    Get device status
    save to json
    returns int 1?0 as an object
    */
    public function getDevice() 
    
    {
        //send request to API
        $this->device = $this->myuplink->getData($this->newEndpoints['device']);  
        //return object
        return $this->device;
        
        
    }
    
    
    /*
    Get smart home categories
    save to json
    returns int 1?0 as an object
    */
    public function getSmartHomeCat() 
    
    {
        //send request to API
        $this->device = $this->myuplink->getData($this->newEndpoints['smart-home-cat']);  
        //return object
        return $this->smartHomeCat;
        
        
    }
    
    /*
    Get smart-home-zones
    save to json
    returns int 1?0 as an object
    */
    public function getSmartHomeZones() 
    
    {
        //send request to API
        $this->device = $this->myuplink->getData($this->newEndpoints['smart-home-zones']);  
        //return object
        return $this->smartHomeZones;
        
        
    }
    
    
    /*
    Get smart-home-mode
    save to json
    returns int 1?0 as an object
    */
    public function getSmartHomeMode() 
    
    {
        //send request to API
        $this->device = $this->myuplink->getData($this->newEndpoints['smart-home-mode']);  
        //return object
        return $this->smartHomeMode;
        
        
    }
    

}  //end of class


?>