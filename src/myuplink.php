<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Version: 1.2.8
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink
{

	//define main variables
	const VERSION = '1.2.8';

	public string $lastVersion = '';
	public $config = array();
	private $authorized = FALSE;
	public $endpoints = array();
	private string $configPath = '';
	public string $authURL = '';
	public $token = array();
	public $tokenStatus = array();
	public int $tokenLife = 0;
	protected string $msg = '';
	protected string $debug = '';


	/*
		  Prepare configuration variables
		  returns array with config variables
		   */
	public function __construct(string $configPath)
	{

		if (version_compare(PHP_VERSION, '7.4.0', '<')) {
			$this->msg('Your php version (' . PHP_VERSION . ') is outdated. Class requires at least PHP 7.4+');
			exit();
		}

		//load config variables
		$this->configPath = $configPath;
		include($this->configPath);
		$this->config = $config;
		//lets push configpath into array as additional variable
		$this->config['configPath'] = $this->configPath;
		$this->debugMsg('DEBUG: Config: ', $this->config);
		//returns config as array
		return $this->config;
	}

	/*
		  Internal function to rtedirect after class operations
		  return function of redirect
		  */
	protected function redirectMe(string $uri, int $delay = 3)
	{

		return header('Refresh:' . $delay . '; url=' . $uri);
	}


	/*
		  Internal function to format myuplink class messages
		  returns null 
		  */
	protected function msg(string $text): void
	{

		//if running from terminal or cron
		if (php_sapi_name() == 'cli') {

			$eol = (php_sapi_name() == 'cli') ? PHP_EOL : "<br />";

			$this->msg .= '*=======================================================================================================================================*' . $eol;
			$this->msg .= '[' . date("Y-m-d H:i:s") . '] ' . $text . $eol;
			$this->msg .= '*=======================================================================================================================================*' . $eol;

			echo $this->msg . $eol;

			$this->msg = '';
		}
		//running from browser
		else {

			echo $this->msg = '<fieldset><legend> [' . date("Y-m-d H:i:s") . '] <b>System message</b></legend>
					' . $text . '
				            </fieldset><br>';
		}
	}

	/*
		  Internal function to send debug <pre> messages
		  return var_dump of variable
		  */
	protected function debugMsg(string $title, $var): void
	{

		if ($this->config['debug'] == TRUE) {
			error_reporting(E_ALL);
			echo $this->debug = '<pre>' . $title;
			var_dump($var);
			echo '</pre>';
		}
	}


	/*
		  Internal function to check if there are newer RELEASED version of this class
		  return string with most updated version
		  */
	public function checkUpdate()
	{

		$url = 'https://api.github.com/repos/PJanisio/myuplinkapi/releases/latest';
		$opts = [
			'http' => [
				'method' => 'GET',
				'header' => ['User-Agent: myuplink']
			]
		];

		$ctx = stream_context_create($opts);
		$json_handler = file_get_contents($url, 0, $ctx);
		$jsonObj = json_decode($json_handler);

		$this->lastVersion = strval(substr($jsonObj->tag_name, 2));

		if (version_compare(constant('myuplink::VERSION'), $this->lastVersion, '<')) {

			return $this->lastVersion;
		} else {
			//no need to update
			return NULL;
		}
	}


	/*
		  returns array with endpoints
		  */
	protected function loadEndpoints()
	{

		include($this->config['configPath']);
		//load endpoints
		$this->endpoints = $endpoints;

		$this->debugMsg('DEBUG: Endpoints:', $this->endpoints);

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
			$this->msg('You are authorized! Token will expire in ' . $this->tokenLife . ' seconds.');
			return TRUE;
		} else if (!isset($_GET['code']) and $this->authorized == FALSE) {

			$this->msg('You are not authorized. Please follow this <a href="' . $this->authURL() . '">LINK</a>');
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
				$this->debugMsg('DEBUG: MyUplink.com answer:', json_decode($c_answer));
				//check answer and token parsing
				$token = json_decode($c_answer, TRUE);
				if ($token == NULL or curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

					//we didnt received token :(
					if (curl_error($c) != NULL) {
						$this->msg('Error resolving token: ' . curl_error($c));
						$this->redirectMe($this->config['redirectUri'], 3);
						return FALSE;
					} else {
						$this->msg('Error resolving token: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . $c_answer);
						$this->redirectMe($this->config['redirectUri'], 3);
						return FALSE;
					}
				} else {
					//save token

					$saveToken = file_put_contents($this->config['jsonOutPath'] . 'token.json', json_encode($token));

					if ($saveToken) {
						$this->msg('Token saved to ' . $this->config['jsonOutPath'] . 'token.json. Reloading page. Please wait...');
						$this->authorized == TRUE;
					}

					if (isset($c)) {
						curl_close($c);
					}
					$this->redirectMe($this->config['redirectUri'], 0);
					//we need to return false and reload to check again token status
					return FALSE;
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
		$this->debugMsg('DEBUG: MyUplink.com answer:', json_decode($c_answer));

		//check answer and token parsing
		$token = json_decode($c_answer, TRUE);
		if ($token == NULL or curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

			//we didnt received token :(
			if (curl_error($c) != NULL) {
				$this->msg('Error resolving token: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . curl_error($c));
				$this->redirectMe($this->config['redirectUri'], 0);
			} else {
				$this->msg('Error resolving token: ' . $c_answer);

				if (isset($c)) {
					curl_close($c);
				}

				$this->redirectMe($this->config['redirectUri'], 3);

				return FALSE;
			}
		} else {
			//save token
			$saveToken = file_put_contents($this->config['jsonOutPath'] . 'token.json', json_encode($token));
			if ($saveToken) {
				$this->msg('Token saved to ' . $this->config['jsonOutPath'] . 'token.json');
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

		$clear = file_put_contents($this->config['jsonOutPath'] . 'token.json', '');

		if (!empty(file_get_contents($this->config['jsonOutPath'] . 'token.json'))) {
			$this->msg('Can not clear token data, check if json folder has a write access');
			return FALSE;
		} else if ($clear) {
			if ($this->config['debug'] == TRUE) {
				$this->msg('Token have been cleared.');
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

		$mod_time = filemtime($this->config['jsonOutPath'] . 'token.json');
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

		$this->tokenStatus = json_decode(@file_get_contents($this->config['jsonOutPath'] . 'token.json'), TRUE);

		$this->debugMsg('DEBUG: Token Status:', $this->tokenStatus);

		if ($this->tokenStatus == NULL) {

			$this->clearToken();
			return FALSE;
		}

		//lets check if our token didnt expired
		else if ($this->tokenExpiry() == 'Token expired') {
			//expired
			$this->msg('Token have expired. Please wait, token will refresh...');

			//clear old token
			$this->clearToken();

			//refresh token
			if ($this->refreshToken() == TRUE) {

				if ($this->config['debug'] == TRUE) {
					$this->msg('Token succesfully refreshed!');
				}

				$this->tokenStatus = json_decode(file_get_contents($this->config['jsonOutPath'] . 'token.json'), TRUE);

				//update token expiry
				$this->tokenExpiry();
				//redirect to main site
				$this->redirectMe($this->config['redirectUri'], 3);

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

	public function getData(string $endpoint, int $successHTTP = 200, int $save = 1)
	{

		//define json output file name based on endpoint array key :)
		//that means you cant use this class before defining your endpoints, self restrictioning mode on ;)

		$jsonName = array_search($endpoint, $this->endpoints) . '.json';


		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, "https://api.myuplink.com" . $endpoint);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->tokenStatus['access_token'] . '', 'Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$c_answer = curl_exec($c);


		//see raw answer 
		$this->debugMsg('DEBUG [READ]: MyUplink.com answer:', json_decode($c_answer));

		$data = json_decode($c_answer, TRUE);

		//204 is a special htttp response f.e for API ping
		if ($data == NULL and curl_getinfo($c, CURLINFO_HTTP_CODE) != $successHTTP) {

			if (curl_getinfo($c, CURLINFO_HTTP_CODE) == 504) {
				//gateway error we have timeout from api, that could mean we have lost authorization status
				//to be checked!

				$this->clearToken();
				$this->redirectMe($this->config['redirectUri'], 0);
				return FALSE;
			}

			//we didnt received answer
			if (curl_error($c) != NULL) {
				$this->msg('Error resolving answer from GET [' . $endpoint . ']: ' . curl_error($c));
				$this->redirectMe($this->config['redirectUri'], 3);
			} else {
				$this->msg('Empty answer from GET [' . $endpoint . ']: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . $c_answer);

				if (isset($c)) {
					curl_close($c);
				}


				return FALSE;
			}
		} else {

			if ($save == 1) {
				$savetoJson = file_put_contents($this->config['jsonOutPath'] . $jsonName, json_encode($data));

				if ($savetoJson == TRUE) {

					$this->msg('Data from GET [' . $endpoint . '] saved to ' . $this->config['jsonOutPath'] . $jsonName);
				}
			}

			//close connection
			if (isset($c)) {
				curl_close($c);
			}

			//returns TRUE if httpcontent == 204 (no data)
			if ($successHTTP == 204) {
				$this->msg('Response from GET [' . $endpoint . '] is succesful! (204)');
				return TRUE;
			}
			//returns data if httpcontent == 200
			else if ($successHTTP == 200) {
				return $data;
			}
		}
	}
} //end of class