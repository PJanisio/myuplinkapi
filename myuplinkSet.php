<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#SET data class

class myuplinkSet extends myuplinkGet
{

    /*
     * Constructor inherits from myuplinkGet
     */
    public function __construct($myuplink)
    {
        parent::__construct($myuplink);
    }

    /*
     * Internal helper to find parameter ID dynamically from the locally saved JSON file
     * category: string (e.g., 'sh-hwBoost', 'sh-indoorSpOffsHeat')
     * returns string|null
     */
    protected function getParameterIdByCategory(string $category): ?string
    {
        $jsonFilePath = $this->myuplink->config['jsonOutPath'] . 'devicePoints.json';

        // If the json file does not exist yet, fetch device points to create it
        if (!file_exists($jsonFilePath)) {
            $this->getDevicePoints();
        }

        $jsonContent = @file_get_contents($jsonFilePath);
        $points = $jsonContent ? json_decode($jsonContent, TRUE) : null;

        if (is_array($points)) {
            foreach ($points as $point) {
                if (isset($point['smartHomeCategories']) && is_array($point['smartHomeCategories'])) {
                    if (in_array($category, $point['smartHomeCategories'])) {
                        return (string) $point['parameterId'];
                    }
                }
            }
        }

        return null;
    }

    /*
     * Internal helper to resolve parameter ID dynamically with safety check and logging
     */
    protected function resolveParameterIdOrWarn(string $category, string $fallbackId, string $parameterName): string
    {
        $parameterId = $this->getParameterIdByCategory($category);

        if ($parameterId !== null) {
            $this->myuplink->debugMsg("DEBUG: Parameter [{$parameterName}] successfully resolved dynamically via category [{$category}] -> ID: ", $parameterId);
            return $parameterId;
        } else {
            $this->myuplink->msg("WARNING: Failed to resolve parameter [{$parameterName}] dynamically via category [{$category}]. Falling back to hardcoded ID: [{$fallbackId}]");
            return $fallbackId;
        }
    }

    /*
     * Update a specific parameter value on the device using PATCH method
     * parameterId: string (e.g., '47011' for heating offset)
     * value: int|float|string
     * returns bool|array
     */
    public function setParameter(string $parameterId, $value)
    {
        // myUplink API requires a key-value associative array payload for PATCH points: {"parameterId": "value"}
        $payload = [
            strval($parameterId) => strval($value)
        ];

        // Writing parameters must target the v2 points endpoint via PATCH
        $endpoint = '/v2/devices/' . $this->systemInfo['deviceId'] . '/points';
        
        $result = $this->myuplink->patchData($endpoint, $payload, 200);

        return $result;
    }

    /*
     * Reset active device alarms if permitted
     * returns bool
     */
    public function resetAlarm()
    {
        if (isset($this->device['availableFeatures']->resetAlarm) && $this->device['availableFeatures']->resetAlarm === TRUE) {
            $endpoint = '/v2/devices/' . $this->systemInfo['deviceId'] . '/resetAlarm';
            return $this->myuplink->patchData($endpoint, [], 200);
        } else {
            $this->msg('Reset alarm feature is not available for this device.');
            return FALSE;
        }
    }

    /*
     * Boost hot water production
     * mode: int 
     * Available mode values:
     * 0 - Off
     * 1 - 3 hr
     * 2 - 6 hr
     * 3 - 12 hr
     * 4 - One-time incr.
     * returns bool|array
     */
    public function setHotWaterBoost(int $mode)
    {
        // Resolve parameter ID dynamically with fallback safety check
        $parameterId = $this->resolveParameterIdOrWarn('sh-hwBoost', '48132', 'Hot Water Boost');
        
        return $this->setParameter($parameterId, $mode);
    }

    /*
     * Set heating offset climate system 1
     * offsetValue: int|float (typically between -10 and 10)
     * returns bool|array
     */
    public function setHeatingOffset(float $offsetValue)
    {
        // Resolve parameter ID dynamically with fallback safety check
        $parameterId = $this->resolveParameterIdOrWarn('sh-indoorSpOffsHeat', '47011', 'Heating Offset');
        
        return $this->setParameter($parameterId, $offsetValue);
    }
} //end of class