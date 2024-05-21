<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.4.3
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink {

    public $config = array();
    private string $configPath ='';
    public string $authURL = '';
    public $token = array();
    public $tokenStatus = array();
    public int $tokenLife = 0;

    /*
    Prepare configuration variables
    returns array with config variables
    */
    public function __construct(string $configPath) {

        $this->configPath = $configPath; 
        include($this->configPath);
        $this->config = $config;
            
            if($this->config['debug'] == TRUE) {
                
                error_reporting(E_ALL);
                
                echo '<pre> Config: ';
                var_dump($this->config);
                echo '</pre>';
            }
            
            
            //returns config as array
        return $this->config;

    }
    
    /*
    Generate auth URL for myuplink
    returns string with URL
    */
    public function authURL() {
        
        $this->authURL = 'https://api.myuplink.com/oauth/authorize?response_type=code&scope='.htmlentities($this->config['scope']).'&client_id='.$this->config['clientID'].'&redirect_uri='.$this->config['redirectUri'];
        
        return $this->authURL;
    }
    
    
    /*
    Main fuinction. Checks authorization, checks fetch token and check token Status
    returns TRUE
    returns link for authorization
    */
    public function authorizeAPI () {
        
        //first we need to check if we have a token, than if token is valid
        if($this->checkTokenStatus() != FALSE ) {

            //we are already authorized!
             echo 'You are authorized! Token will expire in '.$this->tokenLife.' seconds.';
             exit();

        }
        
        //we are not authorized....yet :)
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
		           if(curl_error($c) != NULL) {
		           echo 'Error resolving token: ' .curl_error($c);
		           header( 'Refresh:3; url='.$this->config['redirectUri'].'');
		           }
		            else {
		                echo 'Error resolving token: '.$c_answer;
		                header( 'Refresh:3; url='.$this->config['redirectUri'].'');
		            }
		           
		        }
		            else {
		                //save token
		                file_put_contents($this->config['tokenPath'], json_encode($token));
		                 //close connection
                        if(isset($c)) {
                        curl_close($c);
                        }
                       
                       if(isset($_GET) AND isset($_GET['code'])) {
            
                            echo 'You are authorized! Refreshing site!';
                            header('Refresh:3; url='.$this->config['redirectUri'].'', true, 303);
                            exit(); 
                        
		            }
		            
		            }
		   

        }
        else {
        
            echo 'You are not authorized. Please follow this <a href="'.$this->authURL().'">LINK</a>'; 
        }
        
		return TRUE;


    }
    
    
    /*
    Refresh token if expired, can be used manually
    returns bool
    */
    public function refreshToken() {
        
            
           $c = curl_init();
           curl_setopt($c, CURLOPT_URL,'https://api.myuplink.com/oauth/token');
           curl_setopt($c, CURLOPT_POST, 1);
		   curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=refresh_token&client_id=' .urlencode($this->config['clientID']).'&client_secret='.urlencode($this->config['clientSecret']).'&refresh_token='.$this->tokenStatus['refresh_token']);
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
		           if(curl_error($c) != NULL) {
		           echo 'Error resolving token: ' .curl_error($c);
		           header( 'Refresh:3; url='.$this->config['redirectUri'].'', true, 303);
		           }
		            else {
		                echo 'Error resolving token: '.$c_answer;
		                header( 'Refresh:3; url='.$this->config['redirectUri'].'', true, 303);
		            }
		           
		        }
		            else {
		                //save token
		                file_put_contents($this->config['tokenPath'], json_encode($token));
		            }
		            
		      return TRUE;
        
    }
    
    
    /*
    Internal function to clear token 
    returns bool
    */
    private function clearToken () {
        
        $clear = file_put_contents($this->config['tokenPath'], '');
        
            if(!empty(file_get_contents($this->config['tokenPath']))) {
                echo 'Can not clear token data, check if json folder has a write access';
                return FALSE;
            }
            else if($clear) {
                if($this->config['debug'] == TRUE) {
                    echo 'Token have been cleared.';
                }
                return TRUE;
            }
        
    }
    
    
    /*
    Function to check if token is still valid 
    returns string = 'Token expired' OR
    returns int token life in seconds if valid
    */
    public function tokenExpiry() {
        
      $mod_time = filemtime($this->config['tokenPath']);
        $t_left = intval($this->tokenStatus['expires_in'] - (time() - $mod_time));
        
            $this->tokenLife = $t_left;
            
            if($this->tokenLife <= 0) {
                
                //token expired
                if($this->config['debug'] == TRUE) {
                    echo 'Token have expired. Refreshing token...';
                }
                
                return 'Token expired';
            }
            else {
                return $this->tokenLife; //seconds
            }
        
    }
    
    /*
    Function to check if token is readable and not expired
    returns FALSE if any of errors found
    returns array of token data if valid
    */
    public function checkTokenStatus() {
        
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

                //lets check if our token didnt expired
               else if($this->tokenExpiry() == 'Token expired') {
                   
                   $this->clearToken();
                   
                   if($this->refreshToken() == TRUE) {
                       $this->tokenStatus = json_decode(file_get_contents($this->config['tokenPath']), TRUE);
                       
                       return $this->tokenStatus;
                   }
                   
               }
                    else if($this->tokenStatus != NULL AND $this->tokenExpiry() != 'Token expired') {
                        //returning array
                            return $this->tokenStatus;
                
                    }
                    
                    else {
                       $this->clearToken();
                        return FALSE; 
                        
                    }
            }
            


} //end of class


?>