# myuplinkapi

## What is it?

It`s PHP class to authorize, get and set data for Nibe devices using **Nibe myUplink** -> successor of NibeUplink (which will be closed summer 2024).  Entry level to use this tool is very low, so you can easily get data from you device with basic php skills.

For more information -> [look at wiki pages](https://github.com/PJanisio/myuplinkapi/wiki).

### What is needed?

- [x] PHP version 7.4+

- [x] Account and application created on [myUplink](https://dev.myuplink.com/login)

### What is not needed?

- [ ] any other dependencies nor composers or packages

### What is a goal of this project? And can I contribute?

Goal is to have *easy, non dependent* class which will be cron ready to fetch all heat-pump data into json and in case of premium subscription - also set some parameters. And of course everyone can contribute to this repo.

---

### Installation steps

1. Visit [**MyUplink**](https://myuplink.com/register) and sign up for a user account.

2. Go to [**Applications**](https://dev.myuplink.com/apps), and register a new App

3. Download released version and paste it into your web directory

4. Open **config.php** and fill below settings as written in comments:

```php

'clientID' => 'xxxxxxxxx', //from dev.myuplink.com

'clientSecret' => 'xxxxxxxxxxx', //from dev.myuplink.com

'redirectUri' => 'https://xxxxx/myuplink/', // from dev.myuplink.com - your absolute path where index.php is stored

'jsonOutPath' => '/xxxx/xxxx/myuplink/json/', //your absolute path when you will store json files as well as token.json

  

```

<sub>* redirectUri is a web directory on which you pasted **myuplink class** - please make sure it is the same web URL as you saved in your Myuplink app.</sub>

5. Save config.php and open browser with **redirectUri** address. And see example below.

6. *Optional* - change debug to TRUE if you want to get detailed api responses.

## Example

Below example is exactly the content of **index.php**

**Outcome**: will fetch all device data and save into jSON files and additionally returns array that you can access  in your app.

More examples you can check at [wiki pages](https://github.com/PJanisio/myuplinkapi/wiki).

```php

//include autoloader for classes
include(__DIR__ . '/src/autoloader.php');

//start main class and fetch config
$nibe = new myuplink(__DIR__ . '/config.php');

//authorization, getting token and its status
if ($nibe->authorizeAPI() == TRUE) {
    //if authorized switching to class which get data
    $nibeGet = new myuplinkGet($nibe);

    //get all possible endpoints, put to array and save to jSON
    //$data is an array with key = endpoint key
    $data = $nibeGet->getALL();
}

```

If you doesn`t want to get all data everytime, look at **/src/myuplinkGet.php** for single methods.

For methods description you can look at [**API documentation**](https://api.myuplink.com/swagger/index.html).

---

### Short roadmap

- [x] v.1.x.x - Class can authorize and get all data from Nibe device

- [ ] v.2.x.x - Class can change the parameters (f.e run water heating on demand)
