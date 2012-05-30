<?php
session_start();
/* Please supply your own consumer key and consumer secret */;
define('CONSUMER_KEY',    '_______________');
define('CONSUMER_SECRET', '_______________');

include 'dropboxapi.php';

function getDropbox(){
	if (isset($_COOKIE['oauth_token'])) 
		return new DropboxOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_COOKIE['oauth_token'], $_COOKIE['oauth_token_secret']);
	return NULL;
}