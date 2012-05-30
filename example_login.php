<?php 
include('config.php');

$time = time()+3600*24*365;

if (isset($_COOKIE['oauth_token'])) {
	if($_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
		@session_destroy();
		setcookie('oauth_token','',time(),'/');
		setcookie('oauth_token_secret','',time(),'/');
		header('Location: ./example_login.php');
		exit;
	}
	else{
		$connection = new DropboxOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_COOKIE['oauth_token'], $_COOKIE['oauth_token_secret']);
		
		$access_token = $connection->getAccessToken($_REQUEST['uid'],$_REQUEST['oauth_token']);

		//var_dump($access_token);
		//die();

		$_SESSION['access_token'] = $access_token;
		
		unset($_SESSION['oauth_token']);
		unset($_SESSION['oauth_token_secret']);
		
		setcookie('oauth_token',$access_token['oauth_token'],$time,'/');
		setcookie('oauth_token_secret',$access_token['oauth_token_secret'],$time,'/');
		
		if (200 == $connection->http_code) {
			$_SESSION['login_status'] = 'verified';
			$t = getDropbox();
			
			header('Location: ./example_index.php');
		} else {
			session_destroy();
			setcookie('oauth_token','',time(),'/');
			setcookie('oauth_token_secret','',time(),'/');
			header('Location: ./example_login.php');
			exit;
		}
	}

}else{
	$connection = new DropboxOAuth(CONSUMER_KEY, CONSUMER_SECRET);
	
	$scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
	$port = $_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '';
	$oauth_callback = $scheme . '://' . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
	
	$request_token = $connection->getRequestToken($oauth_callback);
	

	$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
	$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

	setcookie('oauth_token',$request_token['oauth_token'],$time,'/');
	setcookie('oauth_token_secret',$request_token['oauth_token_secret'],$time,'/');
	
	switch ($connection->http_code) {
	case 200:
		
		$url = $connection->getAuthorizeURL($request_token['oauth_token'],$oauth_callback);
		header('Location: ' . $url); 
		break;
	default:
		echo 'Cannot connect to Dropbox. Try again later.';
		break;
	}
}
?>