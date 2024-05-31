<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 1.2.8
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
    public $devicePoints = array();
    public $aidMode;
    public $device;
    public $newEndpoints = array();
    public $smartHomeCat;
    public $smartHomeZones;
    public $smartHomeMode;
    public $firmware;
    public $activeAlerts;
    public $allAlerts;
    public $premium;
    public $all = array();

    //flatten array ready to read
    public $systemInfo = array();




    /*
    /Construct will get main system variables like systemID to fetch further data
    */
    public function __construct($myuplink)
    {

        $this->myuplink = $myuplink;
        $this->getSystemInfo();
        //rewrite endpoints and take values from this function
        $this->newEndpoints();

        if (php_sapi_name() != 'cli') {
            $this->landingPage();
        }

        $this->myuplink->debugMsg('DEBUG: Nibe Systems: ', $this->systemInfo);
    }

    /*
    Internal function to display connection status
    returns string
    */
    protected function landingPage(): void
    {
        $FW = $this->getFirmware();

        if (version_compare($this->systemInfo['currentFwVersion'], $FW['desiredFwVersion'], '<')) {
            $FW_text = 'Your firmware version: <b>' . $this->systemInfo['currentFwVersion'] . '</b> is not up to date! Upgrade your device. Newest firmware version: <b>' . $FW['desiredFwVersion'] . '</b>';
        } else {
            $FW_text = 'Your firmware version: <b>' . $this->systemInfo['currentFwVersion'] . '</b> is up to date!';
        }


        if ($this->myuplink->checkUpdate() !== NULL) {
            $classFW = 'Myuplink class version: <b>' . constant('myuplink::VERSION') . '</b> <a href="https://github.com/PJanisio/myuplinkapi/releases/tag/v.' . $this->myuplink->lastVersion . '"> Update available (' . $this->myuplink->lastVersion . ')</a>';
        } else {

            $classFW = 'Myuplink class version: <b>' . constant('myuplink::VERSION') . '</b> (Cool! You are up to date.)';
        }



        $this->myuplink->msg(
            'Hi, <b>' . $this->systemInfo['name'] . '</b><br>
        Your SystemId: <b>' . $this->systemInfo['systemId'] . '</b><br>
        Your DeviceId: <b>' . $this->systemInfo['deviceId'] . '</b><br>
        Your Device S/N: <b>' . $this->systemInfo['serialNumber'] . '</b><br>
        ' . $FW_text . '<br>'
                . $classFW . '<br>'
        );
    }

    /*
    Internal function to rewrite all ednpoints and add variables from _construct like systemId and deviceId
    returns array of newEndpoints
    */
    public function newEndpoints()
    {

        $toChange = '';
        $new = '';

        foreach ($this->myuplink->endpoints as $end => $value) {

            preg_match_all('/{(.*?)}/', $value, $matches);

            //found curly bracket
            if ($matches[0]) {

                $toChange = $matches[1][0];

                if ($toChange == 'deviceId') {
                    $changed = (string) $this->systemInfo['deviceId'];
                    $new = str_replace('{' . $toChange . '}', $changed, $value);
                    $this->newEndpoints[$end] = $new;
                } else if ($toChange == 'systemId') {
                    $changed = (string) $this->systemInfo['systemId'];
                    $new = str_replace('{' . $toChange . '}', $changed, $value);
                    $this->newEndpoints[$end] = $new;
                }
            } else {
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
    Get function to receive main system information
    save to json
    returns array of parameters
    */
    public function getSystemInfo()
    {

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

        //return array
        return $this->systemInfo;
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
    returns object of array
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
    returns object of array
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
    returns object of array
    */
    public function getSmartHomeCat()
    {
        //send request to API
        $this->smartHomeCat = $this->myuplink->getData($this->newEndpoints['smart-home-cat']);
        //return object
        return $this->smartHomeCat;
    }

    /*
    Get smart-home-zones
    save to json
    returns object of array
    */
    public function getSmartHomeZones()
    {
        //send request to API
        $this->smartHomeZones = $this->myuplink->getData($this->newEndpoints['smart-home-zones']);
        //return object
        return $this->smartHomeZones;
    }


    /*
    Get smart-home-mode
    save to json
    returns object of array
    */
    public function getSmartHomeMode()
    {
        //send request to API
        $this->smartHomeMode = $this->myuplink->getData($this->newEndpoints['smart-home-mode']);
        //return object
        return $this->smartHomeMode;
    }


    /*
   Get actual and newest firmware
   save to json
   returns object of array
   */
    public function getFirmware()
    {
        //send request to API
        $this->firmware = $this->myuplink->getData($this->newEndpoints['firmware']);
        //return object
        return $this->firmware;
    }

    /*
    Get active alerts from device
    save to json
    returns object of array with active alerts
    */
    public function getActiveAlerts()
    {
        //send request to API
        $this->activeAlerts = $this->myuplink->getData($this->newEndpoints['active-alerts']);
        //return object
        return $this->activeAlerts;
    }


    /*
    Get all alerts from device
    save to json
    returns object of array of all historical alerts (only first page)
    */
    public function getAllAlerts()
    {
        //send request to API
        //paging not supported currently

        $this->allAlerts = $this->myuplink->getData($this->newEndpoints['all-alerts']);
        //return object
        return $this->allAlerts;
    }

    /*
    Get information about premium subscription
    save to json
    returns FALSE when no subscription is valid
    returns object of array with expire time if valid subscription
    */
    public function getPremium()
    {
        //send request to API
        $this->premium = $this->myuplink->getData($this->newEndpoints['premium']);
        //return object
        return $this->premium;
    }


    /*
    Get all data which can be gotten :) all methods together at once
    save to json
    returns array of all parameters with key name = endpoint in config.php
    */
    public function getAll()
    {
        //send requests to API
        $this->all['system'] = $this->getSystemInfo();
        $this->all['ping'] = $this->pingAPI();
        $this->all['devicePoints'] = $this->getDevicePoints();
        $this->all['device'] = $this->getDevice();
        $this->all['smart-home-mode'] = $this->getSmartHomeMode();
        $this->all['smart-home-cat'] = $this->getSmartHomeCat();
        $this->all['smart-home-zones'] = $this->getSmartHomeZones();
        $this->all['firmware'] = $this->getFirmware();
        $this->all['activeAlerts'] = $this->getActiveAlerts();
        $this->all['allAlerts'] = $this->getAllAlerts();
        $this->all['premium'] = $this->getPremium();


        //return array of results
        return $this->all;

        /*
        getAll() function will output a mutlidimensional array
        it makes very easy to get value of desired parameter
        example below of current firmware version

        $this->all['firmware']['currentFwVersion'];
        
        use var_dump($this->all) to help yourself :)
        */
    }
}  //end of class
