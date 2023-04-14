<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/error.php,v $
  $Revision: 9.10 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:34 $
**********************************************/
error_reporting(0);
$server_name = preg_replace('#www.#m', '', $_SERVER['SERVER_NAME']);
$notify = 'webmaster@'.$server_name;
$notifyfrom = 'noreply@'.$server_name;
$returnsite = $_SERVER['HTTP_HOST'];
$email = false;
# to activate error logging, you must CHMOD cpg_error.log 600 (622 if 600 fails)
$error_log = false;
$sitename = 'My Web Site';
$returnLink = 'Please <a href="http://'.$returnsite.'">return to the homepage</a>';
$image = '/images/error.gif';

$client['request'] = str_replace('<', '&lt;', $_SERVER['REQUEST_URI']);
$client['agent']   = str_replace('<', '&lt;', $_SERVER['HTTP_USER_AGENT']);
$client['referer'] = str_replace('<', '&lt;', $_SERVER['HTTP_REFERER']);

$webMsg = array();
// English
$webMsg['400'] = 'The URL that you requested, '.$client['request'].', was a bad request.';
$webMsg['401'] = 'The URL that you requested, '.$client['request'].', requires preauthorization to access.';
$webMsg['403'] = 'Access to the URL that you requested, '.$client['request'].', is forbidden.';
$webMsg['404'] = 'The URL that you requested, '.$client['request'].', could not be found. Perhaps you either mistyped the URL or we have a broken link.'.(($error_log || $email) ? '<br /><br />We have logged this error and will correct the problem if it is a broken link.' : '');
$webMsg['500'] = 'The URL that you requested, '.$client['request'].', resulted in a server configuration error. It is possible that the condition causing the problem will be gone by the time you finish reading this.<br /><br />We have logged this error and will correct the problem.';

$css = '
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
}';

print_page();
if ($email) notify();
if ($error_log) cpg_error_log();

function print_page()
{
	global $image, $client, $webMsg, $css, $sitename, $returnLink;
	// take off the path to the script, we don't want them to see that
	$error_notes = ucfirst(preg_replace('/:.*/', '', $_SERVER['REDIRECT_ERROR_NOTES']));
	$errorCode = $_SERVER['REDIRECT_STATUS'];// $_SERVER['QUERY_STRING'];
	$webMsg = $webMsg[$errorCode];
	if (preg_match('#My_eGallery#mi', $client['request'])) {
		$webMsg = 'Do you think we are really that stupid to run My eGallery so that you can hack it?<br />Think again. Coppermine rules!';
	} else if (preg_match('#modules\/coppermine#mi', $client['request'])) {
		$webMsg = 'Your hack attempt has been recorded.<br />The IP Address that you used for this attempt, '.$_SERVER['REMOTE_ADDR'].', will be sent to your ISP.';
		header('HTTP/1.0 404 Not Found');
	}

	echo <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
	<title>$errorCode Error</title>
	<style type="text/css">
	$css
	</style>
  </head>
<body>
  <div class="container">
	<div class="imageBox"><img src="$image" alt="" /></div>
	<div class="textBox">
	<div class="errorHeader">More than a billion websites, and you had to pick this one</div><br />
	  <div class="errorNotes">$error_notes</div>
	  <div class="error">$webMsg</div><br />
	  <div class="return">$returnLink</div>
	</div>
  </div>
</body>
</html>
EOT;
}

function notify()
{
	global $client, $notify, $sitename, $notifyfrom;
	$date = date('D M j G:i:s T Y');
	$message = "
------------------------------------------------------------------------------
Site:\t\t$sitename ({$_SERVER['SERVER_NAME']})
Error Code:\t{$_SERVER['REDIRECT_STATUS']} ({$_SERVER['REDIRECT_ERROR_NOTES']})
Occurred:\t$date
Requested URL:\t{$client['request']}
User Address:\t{$_SERVER['REMOTE_ADDR']}
User Agent:\t{$client['agent']}
Referer:\t{$client['referer']}
------------------------------------------------------------------------------";
	mail($notify, "[ $sitename Error: 404 ]", $message, "From: $notifyfrom");
}

function cpg_error_log()
{
	global $client, $notify, $sitename, $notifyfrom, $errorCode;
	$date = date('D M j G:i:s T Y');
	$message = "
------------------------------------------------------------------------------
Error Code:\t{$_SERVER['REDIRECT_STATUS']} ({$_SERVER['REDIRECT_ERROR_NOTES']})
Occurred:\t$date
Requested URL:\t{$client['request']}
User Address:\t{$_SERVER['REMOTE_ADDR']}
User Agent:\t{$client['agent']}
Referer:\t{$client['referer']}
------------------------------------------------------------------------------";
	if (!($fp = fopen("cpg_error.log", "a"))) exit;
	flock( $fp, LOCK_EX ); // exclusive lock
	// write to the file
	fwrite( $fp, $message );
	flock( $fp, LOCK_UN ); // release the lock
	fclose( $fp );
}
