<?php

require_once('oauth.php');


class DropboxOAuth {
	/* Contains the last HTTP status code returned */
	public $http_code;
	/* Contains the last API call */
	public $last_api_call;
	
	/* Set up the API root URL */
	public $host = "https://api.dropbox.com/1";
	public $api_url = "https://api.dropbox.com/1/";
	public $api_content_url = 'https://api-content.dropbox.com/1/';
	
	public $root = 'dropbox'; //sandbox = the folder for the app | dropbox = full access
	/* Set timeout default */
	public $timeout = 60;
	/* Set connect timeout */
	public $connecttimeout = 60; 
	/* Verify SSL Cert */
	public $ssl_verifypeer = FALSE;
	/* Respons type */
	public $type = 'json';
	/* Decode returne json data */
	public $decode_json = TRUE;
	/* Immediately retry the API call if the response was not successful. */
	//public $retry = TRUE;

	/**
	 * Set API URLS
	 */
	function accessTokenURL()  { return 'https://www.dropbox.com/1/oauth/access_token'; }
	function authorizeURL()    { return 'https://www.dropbox.com/1/oauth/authorize'; }
	function requestTokenURL() { return 'https://www.dropbox.com/1/oauth/request_token'; }

	/**
	 * Debug helpers
	 */
	function lastStatusCode() { return $this->http_status; }
	function lastAPICall() { return $this->last_api_call; }

	/**
	 * construct TwitterOAuth object
	 */
	function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		if (!empty($oauth_token) && !empty($oauth_token_secret)) {
			$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
		} else {
			$this->token = NULL;
		}
	}


	/**
	 * Get a request_token from Twitter
	 *
	 * @returns a key/value array containing oauth_token and oauth_token_secret
	 */
	function getRequestToken($oauth_callback = NULL) {
		$parameters = array();
		if (!empty($oauth_callback)) {
			$parameters['oauth_callback'] = $oauth_callback;
		} 
		$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Get the authorize URL
	 *
	 * @returns a string
	 */
	function getAuthorizeURL($token,$callback='') {
		if (is_array($token)) {
			$token = $token['oauth_token'];
		}
		return $this->authorizeURL() . "?oauth_token={$token}&oauth_callback=".urlencode($callback);
	}

	/**
	 * Exchange the request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @returns array("oauth_token" => the access token,
	 *                "oauth_token_secret" => the access secret)
	 */
	function getAccessToken($uid = FALSE, $oauth_token = NULL) {
		$parameters = array();
		//if (!empty($uid)) {
			//$parameters['uid'] = $uid;
		//}
		if (!empty($oauth_token)) {
			$parameters['oauth_token'] = $oauth_token;
			//$this->token = $oauth_token;
		}
		$request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
		$token = OAuthUtil::parse_parameters($request);
		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * GET wrappwer for oAuthRequest.
	 */
	function get($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		if ($this->type == 'json' && $this->decode_json) {
			return json_decode($response);
		}elseif($this->type == 'xml' && function_exists('simplexml_load_string')){
			return simplexml_load_string($response);
		}
		return $response;
	}

	/**
	 * POST wreapper for oAuthRequest.
	 */
	function post($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'POST', $parameters);
		if ($this->type === 'json' && $this->decode_json) {
			return json_decode($response);
		}elseif($this->type == 'xml' && function_exists('simplexml_load_string')){
			return simplexml_load_string($response);
		}
		return $response;
	}

	/**
	 * DELTE wrapper for oAuthReqeust.
	 */
	 /** laobubu:
	  *
	function delete($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->type === 'json' && $this->decode_json) {
			return json_decode($response);
		}elseif($this->type == 'xml' && function_exists('simplexml_load_string')){
			return simplexml_load_string($response);
		}
		return $response;
	} */

	/**
	 * Format and sign an OAuth / API request
	 */
	function http_oauthed($url, $postfields = array(),  $method = 'GET',$headers = array()) {
		if ($method=='GET') {
			return $this->oAuthRequest($url, 'GET', $postfields);
		}
		if ($method=='POST') {
			return $this->oAuthRequest($url, 'POST', $postfields, $headers);
		}
	}
	
	function oAuthRequest($url, $method, $parameters, $headers = array()) {
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = "{$this->host}{$url}.{$this->type}";
		}
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		//var_dump($request);
		//echo "<hr>";
		switch ($method) {
		case 'GET':
			return $this->http($request->to_url(), 'GET',NULL, $headers);
		default:
			return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata(), $headers);
		}
	}

	/**
	 * Make an HTTP request
	 *
	 * @return API results
	 */
	function http($url, $method = 'GET', $postfields = NULL, $headers = array()) {
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array_merge(array('Expect:'),$headers));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);

		switch ($method) {
		case 'POST':
			curl_setopt($ci, CURLOPT_POST, TRUE);
			if (!empty($postfields)) {
				curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
			}
			break;
		case 'DELETE':
			curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
			if (!empty($postfields)) {
				$url = "{$url}?{$postfields}";
			}
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);
		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->last_api_call = $url;
		curl_close ($ci);
		return $response;
	}

	/* ---------- API METHODS ---------- */
	/*                                   */
	
	
	
	    public function getAccountInfo() {
		return $this->get($this->api_url . 'account/info');

        $data = $this->http_oauthed($this->api_url . 'account/info');
        return json_decode($data,true);

    }

    /**
     * Creates a new Dropbox account
     *
     * @param string $email 
     * @param string $first_name 
     * @param string $last_name 
     * @param string $password 
     * @deprecated This method is no longer supported
     * @return bool 
     */
    public function createAccount($email, $first_name, $last_name, $password) {

        throw new Dropbox_Exception('This API method is deprecated as of the version 1 API');

    }


    /**
     * Returns a file's contents 
     * 
     * @param string $path path 
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return string 
     */
    public function getFile($path = '', $root = NULL) {

        if (is_null($root)) $root = $this->root;
        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $result = $this->http_oauthed($this->api_content_url . 'files/' . $root . '/' . ltrim($path,'/'));
        return $result;

    }

    /**
     * Uploads a new file
     *
     * @param string $path Target path (including filename) 
     * @param string $file Either a path to a file or a stream resource 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return bool 
     */
    public function putFile($path, $file, $root = NULL) {

        $directory = dirname($path);
        $filename = basename($path);

        if($directory==='.') $directory = '';
        if (is_null($root)) $root = $this->root;

        if (is_string($file)) {

            $file = fopen($file,'r');

        } elseif (!is_resource($file)) {
            throw new Dropbox_Exception('File must be a file-resource or a string');
        }
        $result=$this->multipartFetch($this->api_content_url . 'files/' . 
                $root . '/' . trim($directory,'/'), $file, $filename);
        
        if(!isset($result["httpStatus"]) || $result["httpStatus"] != 200) 
            throw new Dropbox_Exception("Uploading file to Dropbox failed");

        return true;
    }


    /**
     * Copies a file or directory from one location to another 
     *
     * This method returns the file information of the newly created file.
     *
     * @param string $from source path 
     * @param string $to destination path 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return stdclass 
     */
    public function copy($from, $to, $root = NULL) {

        if (is_null($root)) $root = $this->root;
        $response = $this->http_oauthed($this->api_url . 'fileops/copy', array('from_path' => $from, 'to_path' => $to, 'root' => $root));

        return json_decode($response,true);

    }

    /**
     * Creates a new folder 
     *
     * This method returns the information from the newly created directory
     *
     * @param string $path 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return stdclass 
     */
    public function createFolder($path, $root = NULL) {

        if (is_null($root)) $root = $this->root;

        // Making sure the path starts with a /
        $path = '/' . ltrim($path,'/');

        $response = $this->http_oauthed($this->api_url . 'fileops/create_folder', array('path' => $path, 'root' => $root),'POST');
        return json_decode($response,true);

    }

    /**
     * Deletes a file or folder.
     *
     * This method will return the metadata information from the deleted file or folder, if successful.
     * 
     * @param string $path Path to new folder 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return array 
     */
    public function delete($path, $root = NULL) {

        if (is_null($root)) $root = $this->root;
        $response = $this->http_oauthed($this->api_url . 'fileops/delete', array('path' => $path, 'root' => $root));
        return json_decode($response);

    }

    /**
     * Moves a file or directory to a new location 
     *
     * This method returns the information from the newly created directory
     *
     * @param mixed $from Source path 
     * @param mixed $to destination path
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return stdclass 
     */
    public function move($from, $to, $root = NULL) {

        if (is_null($root)) $root = $this->root;
        $response = $this->http_oauthed($this->api_url . 'fileops/move', array('from_path' => rawurldecode($from), 'to_path' => rawurldecode($to), 'root' => $root));

        return json_decode($response,true);

    }

    /**
     * Returns a list of links for a directory
     *
     * The links can be used to securely open files throug a browser. The links are cookie protected
     * so a user is asked to login if there's no valid session cookie.
     *
     * @param string $path Path to directory or file
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @deprecated This method is no longer supported
     * @return array 
     */
    public function getLink($path, $root = NULL) {

        if (is_null($root)) $root = $this->root;
        
        $response = $this->http_oauthed($this->api_url . 'shares/' . $root . '/' . ltrim($path,'/'));
        return json_decode($response,true);
    }

    /**
     * Returns file and directory information
     * 
     * @param string $path Path to receive information from 
     * @param bool $list When set to true, this method returns information from all files in a directory. When set to false it will only return infromation from the specified directory.
     * @param string $hash If a hash is supplied, this method simply returns true if nothing has changed since the last request. Good for caching.
     * @param int $fileLimit Maximum number of file-information to receive 
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return array|true 
     */
    public function getMetaData($path, $list = true, $hash = NULL, $fileLimit = NULL, $root = NULL) {

        if (is_null($root)) $root = $this->root;

        $args = array(
            'list' => $list,
        );

        if (!is_null($hash)) $args['hash'] = $hash; 
        if (!is_null($fileLimit)) $args['file_limit'] = $fileLimit; 

        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $response = $this->http_oauthed($this->api_url . 'metadata/' . $root . '/' . ltrim($path,'/'), $args);

        /* 304 is not modified */
        /*if ($response['httpStatus']==304) {
            return true; 
        } else {*/
            return json_decode($response,true);
        /*}*/

    } 

    /**
     * Returns a thumbnail (as a string) for a file path. 
     * 
     * @param string $path Path to file 
     * @param string $size small, medium or large 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return string 
     */
    public function getThumbnail($path, $size = 'small', $root = NULL) {

        if (is_null($root)) $root = $this->root;
        $response = $this->http_oauthed($this->api_content_url . 'thumbnails/' . $root . '/' . ltrim($path,'/'),array('size' => $size));

        return $response;

    }

    /**
     * This method is used to generate multipart POST requests for file upload 
     * 
     * @param string $uri 
     * @param array $arguments 
     * @return bool 
     */
    protected function multipartFetch($uri, $file, $filename) {

        /* random string */
        $boundary = 'R50hrfBj5JYyfR3vF3wR96GPCC9Fd2q2pVMERvEaOE3D8LZTgLLbRpNwXek3';

        $headers = array(
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        );

        $body="--" . $boundary . "\r\n";
        $body.="Content-Disposition: form-data; name=file; filename=".rawurldecode($filename)."\r\n";
        $body.="Content-type: application/octet-stream\r\n";
        $body.="\r\n";
        $body.=stream_get_contents($file);
        $body.="\r\n";
        $body.="--" . $boundary . "--";

        // Dropbox requires the filename to also be part of the regular arguments, so it becomes
        // part of the signature. 
        $uri.='?file=' . $filename;

        return $this->http_oauthed($uri, $body, 'POST', $headers);

    }
}
?>