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
Current state: class can authorize, refresh authorization token and save ALL device parameters to jSON. See [example output](https://pastebin.pl/view/raw/dbd66f2e)

```php
include('src/myuplink.php');
include('src/myuplink_get.php');

$nibe = new myuplink('config.php'); //best practise - use absolute path
    

    //authorization, getting token and its status
    if($nibe->authorizeAPI() == TRUE)
    
    {
        //if authorized switching to class which get data
        $nibeGet = new myuplinkGet($nibe);

    
        //get all parameters from device and save to jSON
        $nibeGet->getDevicePoints();
    }
