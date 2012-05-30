<?php
include('config.php'); 
$dp = getDropbox();
if ($dp==NULL) {
	//Not logined
	header('location: ./example_login.php');
	exit;
}
?><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Made it!</title>
<style type="text/css">
<!--
body {
	color: #333333;
	background: #CCCCCC;
	font-family: Arial, Helvetica, sans-serif;
}
a:link {
	color: #003366;
}
-->
</style>
</head><body>
<h1>Congratulation! You succeed!</h1>
<p>Welcome back. The app has already connected to Dropbox.</p>
<p>Here is the test result:</p>
<pre><?php var_dump($dp->getAccountInfo()) ?></pre>
<p>The OAuth Token has been saved into your cookies.</p>
<p>Before start coding, read the <a href="https://www.dropbox.com/developers/reference/api">DropBox API document</a>!</p>
<hr>
based on <a href="http://laobubu.net">laobubu</a>'s <a href="https://github.com/laobubu/DropBox-API-For-PHP/">DropBox-API-For-PHP</a>.
</body></html>