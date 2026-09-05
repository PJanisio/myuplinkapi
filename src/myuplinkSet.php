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
    // Known parameter ID fallbacks for common operations
    private const PARAM_HOT_WATER_BOOST = '48132';
    private const PARAM_HEATING_OFFSET = '47011';

    /**
     * Constructor inherits from myuplinkGet
     * @param myuplink $myuplink Parent myuplink class instance
     */
    public function __construct($myuplink)
    {
        parent::__construct($myuplink);
    }

    /**
     * Internal helper to find parameter ID dynamically from the locally saved JSON file
     * @param string $category Smart home category (e.g., 'sh-hwBoost', 'sh-indoorSpOffsHeat')
     * @return string|null Parameter ID if found, null otherwise
     */
    protected function getParameterIdByCategory(string $category): ?string
    {
        $jsonFilePath = $this->myuplink->config['jsonOutPath'] . 'devicePoints.json';

        // If the json file does not exist yet, fetch device points to create it
        if (!file_exists($jsonFilePath)) {
            $this->getDevicePoints();
        }

        $jsonContent = @file_get_contents($jsonFilePath);
        $points = $jsonContent ? json_decode($jsonContent, true) : null;

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

    /**
     * Internal helper to resolve parameter ID dynamically with safety check and logging
     * @param string $category Smart home category
     * @param string $fallbackId Hardcoded fallback parameter ID
     * @param string $parameterName Human-readable parameter name for debugging
     * @return string Resolved parameter ID or fallback
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

    /**
     * Update a specific parameter value on the device using PATCH method
     * @param string $parameterId Parameter ID (e.g., '47011' for heating offset)
     * @param int|float|string $value Value to set
     * @return array|bool Response data on success, false on error
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

    /**
     * Reset active device alarms if permitted
     * @return array|bool Success response or false if not available
     */
    public function resetAlarm()
    {
        if (isset($this->device['availableFeatures']->resetAlarm) && $this->device['availableFeatures']->resetAlarm === true) {
            $endpoint = '/v2/devices/' . $this->systemInfo['deviceId'] . '/resetAlarm';
            return $this->myuplink->patchData($endpoint, [], 200);
        } else {
            $this->msg('Reset alarm feature is not available for this device.');
            return false;
        }
    }

    /**
     * Boost hot water production
     * @param int $mode Mode value (0=Off, 1=3hr, 2=6hr, 3=12hr, 4=One-time increment)
     * @return array|bool Response data on success, false on error
     */
    public function setHotWaterBoost(int $mode)
    {
        // Resolve parameter ID dynamically with fallback safety check
        $parameterId = $this->resolveParameterIdOrWarn('sh-hwBoost', self::PARAM_HOT_WATER_BOOST, 'Hot Water Boost');
        
        return $this->setParameter($parameterId, $mode);
    }

    /**
     * Set heating offset climate system 1
     * @param float $offsetValue Offset value (typically between -10 and 10)
     * @return array|bool Response data on success, false on error
     */
    public function setHeatingOffset(float $offsetValue)
    {
        // Resolve parameter ID dynamically with fallback safety check
        $parameterId = $this->resolveParameterIdOrWarn('sh-indoorSpOffsHeat', self::PARAM_HEATING_OFFSET, 'Heating Offset');
        
        return $this->setParameter($parameterId, $offsetValue);
    }
} //end of class