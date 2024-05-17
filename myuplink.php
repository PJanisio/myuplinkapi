<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.0.1
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink {

    public array $config;
    private string $configPath;


    public function __construct(string $configPath) {

        $this->configPath = $configPath; 
        include($this->configPath);

        $this->config = $config;
        //returns config as array

        return $this->config;

    }


    public function authURL() {

        return 'https://api.myuplink.com/oauth/authorize?response_type=code&scope=' .$this->config['scope']. '&client_id=' .$this->config['clientID'].'&redirect_uri=' .$this->config['redirectUri'];

    }



}


?>