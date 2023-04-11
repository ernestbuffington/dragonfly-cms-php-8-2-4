<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
error_reporting(0);
$server_name = str_replace('www.', '', $_SERVER['SERVER_NAME']);
$notify = 'webmaster@'.$server_name;
$notifyfrom = 'noreply@'.$server_name;
$email = false;
# to activate error logging, you must CHMOD cpg_error.log 600 (622 if 600 fails)
$error_log = false;
$sitename = 'My Web Site';

// English
include 'includes/l10n/en/errors.php';
$LNG['_SECURITY_MSG'][404] .= (($error_log || $email) ? '<br /><br />We have logged this error and will correct the problem if it is a broken link.' : '');

// take off the path to the script, we don't want them to see that
$error_notes = ucfirst(preg_replace('/:.*/', '', $_SERVER['REDIRECT_ERROR_NOTES']));
$errorCode = $_SERVER['REDIRECT_STATUS'];
if (false !== stripos($_SERVER['REQUEST_URI'], 'My_eGallery')) {
	$webMsg = 'Do you think we are really that stupid to run My eGallery so that you can hack it?<br />Think again. Coppermine rules!';
} else if (false !== stripos($_SERVER['REQUEST_URI'], 'modules/coppermine')) {
	$webMsg = 'Your hack attempt has been recorded.<br />The IP Address that you used for this attempt, '.$_SERVER['REMOTE_ADDR'].', will be sent to your ISP.';
	$errorCode = 404;
} else {
	$webMsg = nl2br($LNG['_SECURITY_MSG'][$errorCode]);
}
$errorStatus = $errorCode.' '.$LNG['_SECURITY_STATUS'][$errorCode];
header($_SERVER['SERVER_PROTOCOL'].' '.$errorStatus, true, $errorCode);

$ext = pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION);
if ('css' === $ext) {
	header('Content-Type: text/css');
	echo 'body *::after { content: "'.$errorStatus.': '.$_SERVER['PATH_INFO'].'"; }';
} else if ('js' === $ext) {
	header('Content-Type: application/javascript');
	echo 'alert("'.$errorStatus.': '.$_SERVER['PATH_INFO'].'");';
} else if (preg_match('/png|gif|jpe?g/', $ext) && $fp = fopen('images/error.gif', 'r')) {
	header('Content-Type: image/gif');
	fpassthru($fp);
	fclose($fp);
} else {
	$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
	echo <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
	<title>{$errorStatus} Error</title>
	<style type="text/css">
	body {
  background-color: #fff;
  font-family: Verdana,Helvetica;
  font-size: 10px;
  color: #000;
}
a:link,a:active,a:visited {
  font-weight: bold;
  text-decoration: none;
  color: blue;
}
a:hover {
  font-weight: bold;
  text-decoration: underline;
  color: blue;
}
div.container {
  width: 550px;
  text-align: center;
  margin-left: auto;
  margin-right: auto;
  margin-top: 150px;
  border: thin solid #000;
}
div.imageBox {
  background-color: #fff;
  float: left;
  width: 30%;
  border-right: thin solid #000;
  height: 250px;
  padding: 5px;
}
div.textBox {
  padding: 5px;
  background-color: #dedef6;
  text-align: center;
  height: 250px;
}
div.errorHeader {
  color: #666;
  font-size: 11px;
  margin-bottom: 10px;
}
div.errorNotes {
  color: #dd0101;
  font-weight: bold;
  font-size: 12px;
  margin-bottom: 10px;
}
div.error {
  font-weight: bold;
  margin-bottom: 10px;
}
div.errorDetails {
  font-size: small;
  font-weight: bold;
}
div.return {
  font-weight: small;
}
	</style>
  </head>
<body>
  <div class="container">
	<div class="imageBox"><img src="{$base_path}/images/error.gif" alt="" /></div>
	<div class="textBox">
	<div class="errorHeader">More than a billion websites, and you had to pick this one</div><br />
	  <div class="errorNotes">{$error_notes}</div>
	  <div class="error">{$webMsg}</div><br />
	  <div class="return">Please <a href="//{$_SERVER['HTTP_HOST']}{$base_path}/">return to the homepage</a></div>
	</div>
  </div>
</body>
</html>
EOT;
}

if ($email) {
	$date = date('D M j G:i:s T Y');
	$message = "
------------------------------------------------------------------------------
Site:\t\t{$sitename} ({$_SERVER['SERVER_NAME']})
Error Code:\t{$_SERVER['REDIRECT_STATUS']} ({$_SERVER['REDIRECT_ERROR_NOTES']})
Occurred:\t{$date}
Requested URL:\t{$_SERVER['REQUEST_URI']}
User Address:\t{$_SERVER['REMOTE_ADDR']}
User Agent:\t{$_SERVER['HTTP_USER_AGENT']}
------------------------------------------------------------------------------";
	mail($notify, "[ {$sitename} Error: 404 ]", $message, "From: {$notifyfrom}");
}

if ($error_log) {
	$date = date('D M j G:i:s T Y');
	$message = "
------------------------------------------------------------------------------
Error Code:\t{$_SERVER['REDIRECT_STATUS']} ({$_SERVER['REDIRECT_ERROR_NOTES']})
Occurred:\t{$date}
Requested URL:\t{$_SERVER['REQUEST_URI']}
User Address:\t{$_SERVER['REMOTE_ADDR']}
User Agent:\t{$_SERVER['HTTP_USER_AGENT']}
------------------------------------------------------------------------------";
	if (!($fp = fopen("cpg_error.log", "a"))) exit;
	flock( $fp, LOCK_EX ); // exclusive lock
	// write to the file
	fwrite( $fp, $message );
	flock( $fp, LOCK_UN ); // release the lock
	fclose( $fp );
}
