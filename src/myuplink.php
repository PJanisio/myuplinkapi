<?php
/*
myuplinkphp - class to connect and fetch data from Nibe heat pump
Author: Pawel 'Pavlus' Janisio
License: GPL v3
github: https://github.com/PJanisio/myuplinkapi
*/

#MAIN CLASS


class myuplink
{

	//define main variables
	const VERSION = '2.0.1';

	public string $lastVersion = '';
	public $config = array();
	private $authorized = FALSE;
	public $endpoints = array();
	private string $configPath = '';
	public string $authURL = '';
	public $token = array();
	public $tokenStatus = array();
	public int $tokenLife = 0;
	public string $msg = '';
	protected string $debug = '';


	/**
	 * Prepare configuration variables
	 * @param string $configPath Path to config file
	 * @return array Config variables
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

	/**
	 * Helper method to initialize and configure CURL request
	 * @param string $url Target URL
	 * @param string $method HTTP method (GET, POST, PATCH, etc.)
	 * @param string|null $postFields Request body for POST/PATCH requests
	 * @param array $headers HTTP headers
	 * @return object CURL resource handle
	 */
	private function initCurl(string $url, string $method = 'GET', ?string $postFields = null, array $headers = []): object
	{
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		
		if ($method !== 'GET') {
			curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
		}
		
		if ($postFields !== null) {
			curl_setopt($c, CURLOPT_POSTFIELDS, $postFields);
		}
		
		if (!empty($headers)) {
			curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		}
		
		curl_setopt($c, CURLOPT_HTTP_VERSION, $this->config['curl_http_version']);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		
		return $c;
	}

	/**
	 * Internal function to redirect after class operations
	 * @param string $uri Target URI
	 * @param int $delay Delay in seconds
	 * @return void
	 */
	protected function redirectMe(string $uri, int $delay = 3): void
	{
		header('Refresh:' . $delay . '; url=' . $uri);
	}


	/**
	 * Internal function to format myuplink class messages
	 * @param string $text Message text
	 * @return void
	 */
	public function msg(string $text): void
	{

		$eol = (php_sapi_name() == 'cli') ? PHP_EOL : "<br />";

		//if running from terminal or cron
		if (php_sapi_name() == 'cli') {

			$output = '*=======================================================================================================================================*' . $eol;
			$output .= '[' . date("Y-m-d H:i:s") . '] ' . $text . $eol;
			$output .= '*=======================================================================================================================================*' . $eol;

			echo $output . $eol;
		}
		//running from browser
		else {

			echo '<fieldset><legend> [' . date("Y-m-d H:i:s") . '] <b>System message</b></legend>
					' . $text . '
				            </fieldset><br>';
		}
	}

	/**
	 * Internal function to send debug <pre> messages
	 * @param string $title Debug title
	 * @param mixed $var Variable to dump
	 * @return void
	 */
	protected function debugMsg(string $title, $var): void
	{

		if ($this->config['debug']) {
			error_reporting(E_ALL);
			echo '<pre>' . $title;
			var_dump($var);
			echo '</pre>';
		}
	}


	/**
	 * Internal function to check if there are newer RELEASED version of this class
	 * @return string|null Most updated version or null if current version is up to date
	 */
	public function checkUpdate(): ?string
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
        //cast json_handler to string
        $jsonObj = json_decode((string)$json_handler);

		$this->lastVersion = strval(substr($jsonObj->tag_name, 2));

		if (version_compare(constant('myuplink::VERSION'), $this->lastVersion, '<')) {

			return $this->lastVersion;
		} else {
			//no need to update
			return null;
		}
	}


	/**
	 * Load endpoints from config file
	 * @return array Endpoints array
	 */
	protected function loadEndpoints(): array
	{

		include($this->config['configPath']);
		//load endpoints
		$this->endpoints = $endpoints;

		$this->debugMsg('DEBUG: Endpoints:', $this->endpoints);

		//returns array of endpoints
		return $this->endpoints;
	}


	/**
	 * Generate auth URL for myuplink
	 * @return string Authorization URL
	 */
	public function authURL(): string
	{

		$this->authURL = 'https://api.myuplink.com/oauth/authorize?response_type=code&scope=' . htmlentities($this->config['scope']) . '&client_id=' . $this->config['clientID'] . '&redirect_uri=' . $this->config['redirectUri'];

		return $this->authURL;
	}


	/**
	 * Main function. Checks authorization, fetches token and checks token Status
	 * @return bool|string TRUE if authorized, FALSE otherwise, or authorization link
	 */
	public function authorizeAPI(): bool
	{

		//first we need to check if we have a token, than if token is valid
		if (is_array($this->checkTokenStatus())) {
			$this->authorized = true;
			//load endpoints available
			$this->loadEndpoints();

			//we are already authorized!
			$this->msg('You are authorized! Token will expire in ' . $this->tokenLife . ' seconds.');
			return true;
		} else if (!isset($_GET['code']) && !$this->authorized) {

			$this->msg('You are not authorized. Please follow this <a href="' . $this->authURL() . '">LINK</a>');
			return false;
		}

		//we are not authorized....yet :)
		//check if user if after authorization from myuplink
		if (!$this->authorized) {

			if (isset($_GET['code'])) {
				$code = urlencode($_GET['code']);

				$postFields = 'grant_type=authorization_code&client_id=' . urlencode($this->config['clientID']) . '&client_secret=' . urlencode($this->config['clientSecret']) . '&code=' . $code . '&redirect_uri=' . $this->config['redirectUri'];
				
				$c = $this->initCurl('https://api.myuplink.com/oauth/token', 'POST', $postFields, ['Content-Type: application/x-www-form-urlencoded']);

				$c_answer = curl_exec($c);

				//we should have a token here, debug output if needed
                //cast c_answer to string to avoid passing boolean to json_decode

                $this->debugMsg('DEBUG: MyUplink.com answer:', json_decode((string)$c_answer));
                //check answer and token parsing
                $token = json_decode((string)$c_answer, true);

				if ($token === null || curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

					//we didnt received token :(
					if (curl_error($c)) {
						$this->msg('Error resolving token: ' . curl_error($c));
						$this->redirectMe($this->config['redirectUri'], 3);
						return false;
					} else {
						$this->msg('Error resolving token: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . $c_answer);
						$this->redirectMe($this->config['redirectUri'], 3);
						return false;
					}
				} else {
					//save token

					$saveToken = file_put_contents($this->config['jsonOutPath'] . 'token.json', json_encode($token));

					if ($saveToken) {
						$this->msg('Token saved to ' . $this->config['jsonOutPath'] . 'token.json. Reloading page. Please wait...');
						$this->authorized = true;
					}

					$this->redirectMe($this->config['redirectUri'], 0);
					//we need to return false and reload to check again token status
					return false;
				}
			}
		}
	}


	/**
	 * Refresh token if expired, can be used manually
	 * @return bool True if success, false on error
	 */
	public function refreshToken(): bool
	{

		$postFields = 'grant_type=refresh_token&client_id=' . urlencode($this->config['clientID']) . '&client_secret=' . urlencode($this->config['clientSecret']) . '&refresh_token=' . $this->tokenStatus['refresh_token'];
		
		$c = $this->initCurl('https://api.myuplink.com/oauth/token', 'POST', $postFields, ['Content-Type: application/x-www-form-urlencoded']);

		$c_answer = curl_exec($c);

		//we should have a token here, debug output if needed
        $this->debugMsg('DEBUG: MyUplink.com answer:', json_decode((string)$c_answer));

        //check answer and token parsing
        $token = json_decode((string)$c_answer, true);
		if ($token === null || curl_getinfo($c, CURLINFO_HTTP_CODE) != 200) {

			//we didnt received token :(
			if (curl_error($c)) {
				$this->msg('Error resolving token: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . curl_error($c));
				$this->redirectMe($this->config['redirectUri'], 0);
			} else {
				$this->msg('Error resolving token: ' . $c_answer);
				$this->redirectMe($this->config['redirectUri'], 3);

				return false;
			}
		} else {
			//save token
			$saveToken = file_put_contents($this->config['jsonOutPath'] . 'token.json', json_encode($token));
			if ($saveToken) {
				$this->msg('Token saved to ' . $this->config['jsonOutPath'] . 'token.json');
			}

			return true;
		}
	}


	/**
	 * Internal function to clear token 
	 * @return bool Success status
	 */
	private function clearToken(): bool
	{

		$bytes = file_put_contents($this->config['jsonOutPath'] . 'token.json', '');

		if ($bytes === 0) {
			if ($this->config['debug']) {
				$this->msg('Token has been cleared.');
			}
			return true;
		} else {
			$this->msg('Failed to clear token. Check write permissions.');
			return false;
		}
	}


	/**
	 * Function to check if token is still valid 
	 * @return int|string Token life in seconds (int) or 'Token expired' (string)
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

	/**
	 * Function to check if token is readable and not expired
	 * @return array|bool Array of token data if valid, false otherwise
	 */
	public function checkTokenStatus()
	{

		// Read file first, then parse to avoid passing false to json_decode
        $jsonContent = @file_get_contents($this->config['jsonOutPath'] . 'token.json');
        $this->tokenStatus = $jsonContent ? json_decode($jsonContent, true) : null;

		$this->debugMsg('DEBUG: Token Status:', $this->tokenStatus);

		if ($this->tokenStatus === null) {

			$this->clearToken();
			return false;
		}

		//lets check if our token didnt expired
		else if ($this->tokenExpiry() == 'Token expired') {
			//expired
			$this->msg('Token have expired. Please wait, token will refresh...');

			//clear old token
			$this->clearToken();

			//refresh token
			if ($this->refreshToken()) {

				if ($this->config['debug']) {
					$this->msg('Token succesfully refreshed!');
				}

				$this->tokenStatus = json_decode(file_get_contents($this->config['jsonOutPath'] . 'token.json'), true);

				//update token expiry
				$this->tokenExpiry();
				//redirect to main site
				$this->redirectMe($this->config['redirectUri'], 3);

				return $this->tokenStatus;
			}
		} else if ($this->tokenStatus !== null && $this->tokenExpiry() != 'Token expired') {
			//returning array
			return $this->tokenStatus;
		} else {
			$this->clearToken();
			return false;
		}
	}

	/**
	 * Function read data from API (GET)
	 * @param string $endpoint API endpoint
	 * @param int $successHTTP Expected HTTP response code
	 * @param int $save Save to JSON file (1) or not (0)
	 * @return array|bool Response data on success, false on error, true for 204 responses
	 */
	public function getData(string $endpoint, int $successHTTP = 200, int $save = 1)
	{

		//define json output file name based on endpoint array key :)
		//that means you cant use this class before defining your endpoints, self restrictioning mode on ;)

		$jsonName = array_search($endpoint, $this->endpoints) . '.json';

		$c = $this->initCurl(
			"https://api.myuplink.com" . $endpoint,
			'GET',
			null,
			['Authorization: Bearer ' . $this->tokenStatus['access_token'], 'Content-Type: application/x-www-form-urlencoded']
		);
		
		$c_answer = curl_exec($c);

		//see raw answer 
        $this->debugMsg('DEBUG [READ]: MyUplink.com answer:', json_decode((string)$c_answer));

        $data = json_decode((string)$c_answer, true);

		//204 is a special htttp response f.e for API ping
		if ($data === null && curl_getinfo($c, CURLINFO_HTTP_CODE) != $successHTTP) {

			if (curl_getinfo($c, CURLINFO_HTTP_CODE) == 504) {
				//gateway error we have timeout from api, that could mean we have lost authorization status
				//to be checked!

				$this->clearToken();
				$this->redirectMe($this->config['redirectUri'], 0);
				return false;
			}

			//we didnt received answer
			if (curl_error($c)) {
				$this->msg('Error resolving answer from GET [' . $endpoint . ']: ' . curl_error($c));
				$this->redirectMe($this->config['redirectUri'], 3);
			} else {
				$this->msg('Empty answer from GET [' . $endpoint . ']: ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . $c_answer);
				return false;
			}
		} else {

			if ($save == 1) {
				$savetoJson = file_put_contents($this->config['jsonOutPath'] . $jsonName, json_encode($data));

				if ($savetoJson) {

					$this->msg('Data from GET [' . $endpoint . '] saved to ' . $this->config['jsonOutPath'] . $jsonName);
				}
			}

			//returns TRUE if httpcontent == 204 (no data)
			if ($successHTTP == 204) {
				$this->msg('Response from GET [' . $endpoint . '] is succesful! (204)');
				return true;
			}
			//returns data if httpcontent == 200
			else if ($successHTTP == 200) {
				return $data;
			}
		}
	}
	
	/**
	 * Function to patch/update data in API (PATCH)
	 * @param string $endpoint API endpoint
	 * @param array $data Data to patch
	 * @param int $successHTTP Expected HTTP response code
	 * @return array|bool Response data on success, false on error, true for 204/empty responses
	 */
    public function patchData(string $endpoint, array $data, int $successHTTP = 200)
    {
        $jsonName = array_search($endpoint, $this->endpoints) . '_set.json';

        $c = $this->initCurl(
        	"https://api.myuplink.com" . $endpoint,
        	'PATCH',
        	json_encode($data),
        	[
        		'Authorization: Bearer ' . $this->tokenStatus['access_token'],
        		'Content-Type: application/json'
        	]
        );

        $c_answer = curl_exec($c);
        
        // Cast c_answer to string to avoid passing boolean to json_decode
        $this->debugMsg('DEBUG [PATCH]: MyUplink.com answer:', json_decode((string)$c_answer));

        $responseData = json_decode((string)$c_answer, true);

        if (curl_error($c) || curl_getinfo($c, CURLINFO_HTTP_CODE) != $successHTTP) {
            $this->msg('Error updating data on [' . $endpoint . ']: HTTP ' . curl_getinfo($c, CURLINFO_HTTP_CODE) . ' - ' . $c_answer);
            return false;
        } else {
            $this->msg('Data successfully updated on [' . $endpoint . ']');
            return $responseData !== null ? $responseData : true;
        }
    }
} //end of class