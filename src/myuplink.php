<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 0.7.6
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink
{

	//define main variables
	const VERSION = '0.7.6';
	public $config = array();
	private $authorized = FALSE;
	public $endpoints = array();
	private string $configPath = '';
	public string $authURL = '';
	public $token = array();
	public $tokenStatus = array();
	public int $tokenLife = 0;
	protected string $msg = '';

	/*
	   Prepare configuration variables
	   returns array with config variables
	    */
	public function __construct(string $configPath)
	{

		//load config variables
		$this->configPath = $configPath;
		include ($this->configPath);
		$this->config = $config;
		//lets push configpath into array as additional variable
		$this->config['configPath'] = $this->configPath;

		if ($this->config['debug'] == TRUE) {

			error_reporting(E_ALL);

			echo '<pre> DEBUG: Config: ';
			var_dump($this->config);
			echo '</pre>';
		}

		//returns config as array
		return $this->config;

	}

	/*
	   Internal function to rtedirect after class operations
	   return function of redirect
	   */
	protected function redirectMe(int $delay = 3, string $uri)
	{

		return header('Refresh:' . $delay . '; url=' . $uri);
	}

	/*
	   Internal function to format myuplink class messages
	   */
	protected function msg(string $text)
	{

		$this->msg = '<fieldset><legend><b>System message</b></legend>
					' . $text . '
				</fieldset>';

		return $this->msg;

	}


	/*
	   returns array with endpoints
	   */
	protected function loadEndpoints()
	{

		include ($this->config['configPath']);
		//load endpoints
		$this->endpoints = $endpoints;

		if ($this->config['debug'] == TRUE) {
			echo '<pre> DEBUG: Endpoints: ';
			var_dump($this->endpoints);
			echo '</pre>';
		}
		//returns array of endpoints
		return $this->endpoints;
	}


	/*
	   Generate auth URL for myuplink
	   returns string with URL
	   */
	public function authURL()
	{

		$this->authURL = 'https://api.myuplink.com/oauth/authorize?response_type=code&scope=' . htmlentities($this->config['scope']) . '&client_id=' . $this->config['clientID'] . '&redirect_uri=' . $this->config['redirectUri'];

		return $this->authURL;
	}


	/*
	   Main fuinction. Checks authorization, checks fetch token and check token Status
	   returns TRUE
	   returns link for authorization
	   */
	public function authorizeAPI()
	{

		//first we need to check if we have a token, than if token is valid
		if (is_array($this->checkTokenStatus())) {
			$this->authorized == TRUE;
			//load endpoints available
			$this->loadEndpoints();

			//we are already authorized!
			echo $this->msg('You are authorized! Token will expire in ' . $this->tokenLife . ' seconds.');
			return TRUE;

		} else if (!isset($_GET['code']) and $this->authorized == FALSE) {

			echo $this->msg('You are not authorized. Please follow this <a href="' . $this->authURL() . '">LINK</a>');
			return FALSE;
		}

		//we are not authorized....yet :)
		//check if user if after authorization from myuplink
		if ($this->authorized == FALSE) {

			if (isset($_GET) and isset($_GET['code'])) {
				$code = urlencode($_GET['code']);

				$c = curl_init();
				curl_setopt($c, CURLOPT_URL, 'https://api.myuplink.com/oauth/token');
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&client_id=' . urlencode($this->config['clientID']) . '&client_secret=' . urlencode($this->config['clientSecret']) . '&code=' . $code . '&redirect_uri=' . $this->config['redirectUri']);
				curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				curl_setopt($c, CURLOPT_HTTP_VERSION, $this->config['curl_http_version']);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);


				$c_answer = curl_exec($c);

				//we should have a token here, debug output if needed
				if ($this->config['debug'] == TRUE) {
					echo '<pre> DEBUG: MyUplink.com answer: ';
					var_dump($c_answer);
					echo '</pre>';
				}

				//check answer and token parsing
				$token = json_decode($c_answer, TRUE);
				if ($token == NULL or curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

					//we didnt received token :(
					if (curl_error($c) != NULL) {
						echo $this->msg('Error resolving token: ' . curl_error($c));
						$this->redirectMe(3, $this->config['redirectUri']);
						return FALSE;
					} else {
						echo $this->msg('Error resolving token: ' . $c_answer);
						$this->redirectMe(3, $this->config['redirectUri']);
						return FALSE;
					}

				} else {
					//save token

					$saveToken = file_put_contents($this->config['tokenPath'], json_encode($token));

					if ($saveToken) {
						echo $this->msg('Token saved to ' . $this->config['tokenPath']);
						$this->authorized == TRUE;
					}

					if (isset($c)) {
						curl_close($c);

						$this->redirectMe(0, $this->config['redirectUri']);
						return TRUE;
					}


				}
			}

		}


	}


	/*
	   Refresh token if expired, can be used manually
	   returns TRUE if success 
	   returns FALSE when error
	   */
	public function refreshToken()
	{


		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, 'https://api.myuplink.com/oauth/token');
		curl_setopt($c, CURLOPT_POST, 1);
		curl_setopt($c, CURLOPT_POSTFIELDS, 'grant_type=refresh_token&client_id=' . urlencode($this->config['clientID']) . '&client_secret=' . urlencode($this->config['clientSecret']) . '&refresh_token=' . $this->tokenStatus['refresh_token']);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($c, CURLOPT_HTTP_VERSION, $this->config['curl_http_version']);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);


		$c_answer = curl_exec($c);

		//we should have a token here, debug output if needed
		if ($this->config['debug'] == TRUE) {
			echo '<pre> DEBUG: MyUplink.com answer: ';
			var_dump($c_answer);
			echo '</pre>';
		}


		//check answer and token parsing
		$token = json_decode($c_answer, TRUE);
		if ($token == NULL or curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

			//we didnt received token :(
			if (curl_error($c) != NULL) {
				echo $this->msg('Error resolving token: ' . curl_error($c));
				$this->redirectMe(3, $this->config['redirectUri']);
			} else {
				echo $this->msg('Error resolving token: ' . $c_answer);

				if (isset($c)) {
					curl_close($c);
				}

				$this->redirectMe(3, $this->config['redirectUri']);

				return FALSE;
			}

		} else {
			//save token
			$saveToken = file_put_contents($this->config['tokenPath'], json_encode($token));
			if ($saveToken) {
				echo $this->msg('Token saved to ' . $this->config['tokenPath']);
			}

			//close connection
			if (isset($c)) {
				curl_close($c);
			}

			return TRUE;
		}

	}


	/*
	   Internal function to clear token 
	   returns bool
	   */
	private function clearToken()
	{

		$clear = file_put_contents($this->config['tokenPath'], '');

		if (!empty(file_get_contents($this->config['tokenPath']))) {
			echo $this->msg('Can not clear token data, check if json folder has a write access');
			return FALSE;
		} else if ($clear) {
			if ($this->config['debug'] == TRUE) {
				echo $this->msg('Token have been cleared.');
			}
			return TRUE;
		}

	}


	/*
	   Function to check if token is still valid 
	   returns string = 'Token expired' OR
	   returns int token life in seconds if valid
	   */
	public function tokenExpiry()
	{

		$mod_time = filemtime($this->config['tokenPath']);
		$t_left = intval($this->tokenStatus['expires_in'] - (time() - $mod_time));

		$this->tokenLife = $t_left;

		if ($this->tokenLife <= 0) {

			//token expired
			return 'Token expired';
		} else {
			return $this->tokenLife; //seconds
		}

	}

	/*
	   Function to check if token is readable and not expired
	   returns FALSE if any of errors found
	   returns array of token data if valid
	   */
	public function checkTokenStatus()
	{

		$this->tokenStatus = json_decode(file_get_contents($this->config['tokenPath']), TRUE);

		if ($this->config['debug'] == TRUE) {
			echo '<pre> DEBUG: Token Status: ';
			var_dump($this->tokenStatus);
			echo '</pre>';
		}


		if ($this->tokenStatus == NULL) {

			$this->clearToken();
			return FALSE;
		}

		//lets check if our token didnt expired
		else if ($this->tokenExpiry() == 'Token expired') {
			//expired
			if ($this->config['debug'] == TRUE) {
				echo 'DEBUG: Token have expired. Refreshing token...';
			}

			//clear old token
			$this->clearToken();

			//refresh token
			if ($this->refreshToken() == TRUE) {

				if ($this->config['debug'] == TRUE) {
					echo 'DEBUG: Token have been refreshed!';
				}

				$this->tokenStatus = json_decode(file_get_contents($this->config['tokenPath']), TRUE);

				//update token expiry
				$this->tokenExpiry();
				//redirect to main site
				$this->redirectMe(3, $this->config['redirectUri']);

				return $this->tokenStatus;
			}

		} else if ($this->tokenStatus != NULL and $this->tokenExpiry() != 'Token expired') {
			//returning array
			return $this->tokenStatus;

		} else {
			$this->clearToken();
			return FALSE;

		}
	}

	/*
	   Function read data from API (GET)
	   returns output if success
	   returns FALSE on fail
	   by default saves output to json file ($save = 1)
	   */

	public function getData(string $endpoint, int $save = 1)
	{

		//define json output file name based on endpoint array key :)
		//that means you cant use this class before defining your endpoints, self restrictioning mode on L()
		$jsonName = array_search($endpoint, $this->endpoints) . '.json';


		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, "https://api.myuplink.com" . $endpoint);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->tokenStatus['access_token'] . '', 'Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$c_answer = curl_exec($c);


		//see raw answer 
		if ($this->config['debug'] == TRUE) {
			echo '<pre> DEBUG [READ]: MyUplink.com answer: ';
			var_dump($c_answer);
			echo '</pre>';
		}

		$data = json_decode($c_answer, TRUE);
		if ($data == NULL or curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

			//we didnt received answer
			if (curl_error($c) != NULL) {
				echo $this->msg('Error resolving answer: ' . curl_error($c));
				$this->redirectMe(3, $this->config['redirectUri']);
			} else {
				echo $this->msg('Error resolving answer: ' . $c_answer);

				if (isset($c)) {
					curl_close($c);
				}


				return FALSE;
			}

		} else {

			if ($save == 1) {
				$savetoJson = file_put_contents($this->config['jsonOutPath'] . $jsonName, json_encode($data));

				if ($savetoJson == TRUE) {

					echo $this->msg('Data from GET [' . $endpoint . '] saved to ' . $this->config['jsonOutPath'] . $jsonName);
				}

			}

			//close connection
			if (isset($c)) {
				curl_close($c);
			}

			//returns json data
			return $data;

		}


	}



} //end of class


?>