<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.1.1
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink {

    public array $config;
    private string $configPath;
    public string $authURL = '';


    public function __construct(string $configPath) {

        $this->configPath = $configPath; 
        include($this->configPath);
            $this->config = $config;
            
            if($this->config['debug'] == TRUE) {
                echo '<pre>';
                var_dump($this->config);
                echo '</pre>';
            }
            //returns config as array
        return $this->config;

    }


    public function authURL() {
        
        
        $this->authURL = 'https://api.myuplink.com/oauth/authorize?response_type=code&scope='.htmlentities($this->config['scope']).'&client_id='.$this->config['clientID'].'&redirect_uri='.$this->config['redirectUri'];
        return $this->authURL;
        
    }
    
    public function authorizeAPI () {
        
        if (isset($_GET) AND isset($_GET['code'])) {
            
            $code = urlencode($_GET['code']);
            
           $c = curl_init();
           curl_setopt($c, CURLOPT_URL,'https://api.myuplink.com/oauth/token');
           curl_setopt($c, CURLOPT_POST, 1);
		   curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&client_id=' .urlencode($this->config['clientID']).'&client_secret='.urlencode($this->config['clientSecret']).'&code='.$code.'&redirect_uri='.$this->config['redirectUri']);
		   curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		   curl_setopt($c, CURLOPT_HTTP_VERSION, $this->config['curl_http_version']);
		   curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		   
		   
		   $response = curl_exec($c);
		   
		   //we should have a token here
		    if($this->config['debug'] == TRUE) {
		        echo '<pre>';
		   var_dump($response);
		   echo '</pre>';
		    }

        }
        else {
        
            echo 'You are not authorized. Please follow this <a href="'.$this->authURL().'">LINK</a>'; 
        }
        
    }



}

?>