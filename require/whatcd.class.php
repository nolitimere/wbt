<?php
/**
 * PHP class for accessing What.CD's API
 *
 * @author GLaDOSDan
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link https://github.com/GLaDOSDan/whatcd-php
 */
class WhatCD {
	
	private $username;
	private $password;
	public 	$URI = 'https://what.cd/ajax.php';
	private $cookie_jar = '/tmp/what.cd.cookies';
	/**
	 * Constructor to load username and password into variables for other functions in the class to access
	 * @param str $username
	 * @param str $password
	 * @return null
	*/
	public function __construct($username, $password){
		$this->username = $username;
		$this->password = $password;
		if (empty($this->password) || empty($this->username)){
			throw new Exception('[What.CD API] Invalid username or password passed to constructor');
		}
		return null;
	}
	/**
	 * Logs into what.cd
	 * @return boolean
	*/
	public function login(){
		$POST = array(
			'username' => $this->username,
			'password' => $this->password,
			'keeplogged' => '1',
			'login' => 'Log in'
		);
		$ch = curl_init('https://what.cd/login.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'NMINML/BlazeOn');
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
		$return = curl_exec($ch);
		curl_close($ch);
		if (false !== strpos($return, 'Your username or password was incorrect')){
			// Username or password incorrect, throw exception
			throw new Exception('[What.CD API] Username or password rejected by What.CD');
		}
		if (false !== strpos($return, 'Manage sessions')){
			// Logged in
			return true;
		}
		return true;
	}
	/**
	 * Interface for making request to What.CD API
	 * @param str $action
	 * @param array $arguments
	 * @return array - parsed JSON response
	*/
	public function request($action, $arguments = null){
		return $this->parse_response($this->curl_request($action, $arguments));
	}
	/**
	 * Parse response from What.CD and return the results
	 * @param str $data - JSON response from API
	 * @return array - parsed JSON response
	*/
	public function parse_response($data){
		$json = json_decode($data, true);
		if ($json['status'] == 'success'){
			return $json['response'];
		}
		if ($json['status'] == 'failure'){
			if (!isset($json['error'])){
				throw new Exception('[What.CD API] Generic API failure');
			} else {
				throw new Exception('[What.CD API] Error: ' . $json['error']);
			}
		}
	}
	/**
	 * Make cURL request to API
	 * @param str $action
	 * @param array $arguments
	 * @return str $response
	*/
	public function curl_request($action, $arguments = null){
		$URI = $this->URI;
		$args[] = '?action=' . $action;
		if (!empty($arguments)){
			foreach ($arguments as $attribute => $value){
				$args[] = urlencode($attribute) . '=' . urlencode($value);
			}
		}
		$URI .= implode('&', $args);
		$ch = curl_init($URI);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'GLaDOSDan/whatcd-php');
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($HTTP_CODE == 302){
			if ($this->login()){
				return $this->request($action, $arguments, true);
			}
			return false;
		}
		return $response;
	}
}
