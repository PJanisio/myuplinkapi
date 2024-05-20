<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.3.2
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink {

    public array $config;
    private string $configPath;
    public string $authURL = '';
    public $token = array();
    public $tokenStatus = array();
    public int $tokenLife = 0;


    public function __construct(string $configPath) {

        $this->configPath = $configPath; 
        include($this->configPath);
            $this->config = $config;
            
            if($this->config['debug'] == TRUE) {
                echo '<pre> Config: ';
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
        
        //first we need to check if we have a token, than if token is valid
        if($this->tokenStatus() != FALSE ) {
            //we are already authorized!
            echo 'You are authorized! Token will expire in '.$this->tokenLife.' seconds.';
            exit();
            
            if (isset($_GET) AND isset($_GET['code'])) {
            echo 'You are authorized! Token will expire in '.$this->tokenLife.' seconds.';
            header( 'Refresh:3; url='.$this->config['redirectUri'].'', true, 303);
            exit();
            
            }
        }
            
        
        //check if user if after authorization from myuplink
        if (isset($_GET) AND isset($_GET['code'])) {

            
            $code = urlencode($_GET['code']);
            
           $c = curl_init();
           curl_setopt($c, CURLOPT_URL,'https://api.myuplink.com/oauth/token');
           curl_setopt($c, CURLOPT_POST, 1);
		   curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&client_id=' .urlencode($this->config['clientID']).'&client_secret='.urlencode($this->config['clientSecret']).'&code='.$code.'&redirect_uri='.$this->config['redirectUri']);
		   curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		   curl_setopt($c, CURLOPT_HTTP_VERSION, $this->config['curl_http_version']);
		   curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		   
		   
		   $c_answer = curl_exec($c);
		   
		   //we should have a token here, debug output if needed
		    if($this->config['debug'] == TRUE) {
		        echo '<pre> MyUplink.com answer: ';
		   var_dump($c_answer);
		        echo '</pre>';
		        }
		  
		   //check answer and token parsing
		    $token = json_decode($c_answer, TRUE);
		        if($token == NULL OR curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {
		           //we didnt received token :(
		           echo 'Error resolving token: ' . curl_error($c);
		        }
		            else {
		                //save token
		                file_put_contents($this->config['tokenPath'], json_encode($token));
		            }
		   

        }
        else {
        
            echo 'You are not authorized. Please follow this <a href="'.$this->authURL().'">LINK</a>'; 
        }
        
        //close copnnection
        if(isset($c)) {
            curl_close($c);
        }
        
        
		return $token;


    }
    
    
    private function clearToken () {
        
        $clear = file_put_contents($this->config['tokenPath'], '');
        
            if(!empty(file_get_contents($this->config['tokenPath']))) {
                echo 'Can not clear token data, check if json folder has a write access';
                exit();
            }
            else {
                return TRUE;
            }
        
    }
    
    
    public function tokenExpiry() {
        
      $mod_time = filemtime($this->config['tokenPath']);
        $t_left = intval(3600 - (time() - $mod_time));
        
            $this->tokenLife = $t_left;
            if($this->tokenLife >= $this->tokenStatus['expires_in']) {
                
                //token expired
                return FALSE;
            }
            else {
                return $this->tokenLife; //seconds
            }
        
    }
    
    

    public function tokenStatus() {
        
        $this->tokenStatus = json_decode(file_get_contents($this->config['tokenPath']), TRUE);
        
        if($this->config['debug'] == TRUE) {
            echo '<pre> Token Status: ';
		   var_dump($this->tokenStatus);
		        echo '</pre>';
        }
		        
        
            if($this->tokenStatus == NULL) {
                
                $this->clearToken();
                return FALSE;
            }
            else {
                
                //lets check if our token didnt expired
               if($this->tokenExpiry() == FALSE) {
                   
                   $this->clearToken();
                return FALSE;
                   
               }
                    else {
                //returning array
                return $this->tokenStatus;
                
                    }
            }
            

    }



} //end of class


?>