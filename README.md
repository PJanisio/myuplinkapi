# myuplinkapi

## What is it?

It`s PHP class to authorize, get and set data for Nibe devices using [Nibe myUplink](https://www.myuplink.com/) successor of NibeUplink (which will be closed summer 2024).

### What is needed?

- PHP version 7.4+ (with lib php-curl)
- Account and application created on [myUplink](https://dev.myuplink.com/login)

### What is not needed?

- any other dependencies nor composers or packages

### What is a goal of this project? And can I contribute?

Goal is to have easy, non dependent class which will be cron ready to fetch all heat-pump data into json and in case of premium subscription - also set some parameters. And of course everyone can contribute to this repo.

### Example

Current state: class can authorize, refresh authorization token and get raw data. First release will fetch all device parameters into jSon.

```php
/*
That is an index and an example at once. Can be used in a browser, or as an event file like cronjob.
*/

//include autoloader for classes
include('src/autoloader.php');
spl_autoload_register('autoloader');


//start main class and fetch config
$nibe = new myuplink('config.php'); //best practise - use absolute path
    

    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE)
    
    {
        //if authorized switching to class which get data
        $nibeGet = new myuplinkGet($nibe);
        //check if API is online
        $nibeGet->pingAPI();
        //get all parameters from device and save to jSON
        $nibeGet->getDevicePoints();
    }
