<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

function cpg_header($cpginfo) {
	return '<!DOCTYPE html>
<html>
<head>
	<title>'.$cpginfo.'</title>
	<meta name="robots" content="none" />
	<link rel="stylesheet" href="includes/css/cpg.css" type="text/css" />
</head>
<body>
	<h1 class="head">
		<img src="'.rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/images/logo.png" alt="Dragonfly CMS" title="Dragonfly CMS"/>
		'.$cpginfo.'
	</h1>
	<div class="border">
';
}

function cpg_footer() {
	$goback = (isset($_SESSION['SECURITY']['flood_count']) && $_SESSION['SECURITY']['flood_count'] > 2) ? '' : (defined('_GOBACK') ? _GOBACK : '[ <a href="javascript:history.go(-1)"><strong>Go Back</strong></a> ]');
	return '<br /><br />'.$goback.'
  </div>
</body></html>';
}
