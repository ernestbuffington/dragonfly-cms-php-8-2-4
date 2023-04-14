<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/News/print.php,v $
  $Revision: 9.5 $
  $Author: phoenix $
  $Date: 2007/05/01 11:05:28 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

if (isset($_GET['sid'])) {
	$sid = intval($_GET['sid']);
	$result = $db->sql_query('SELECT s.title, s.time, s.hometext, s.bodytext, s.informant, s.notes FROM '.$prefix.'_stories s LEFT JOIN '.$prefix."_topics t ON (t.topicid = s.topic) WHERE s.sid='$sid'");
	if ($db->sql_numrows($result) < 1) { url_redirect(getlink()); }
	list($title, $time, $hometext, $bodytext, $author, $notes) = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	require_once('includes/nbbcode.php');
	$hometext = decode_bb_all($hometext, 1, true);
	$bodytext = decode_bb_all($bodytext, 1, true);
	$notes = decode_bb_all($notes, 1, true);
	if (!defined('_CHARSET')) { define('_CHARSET', 'UTF-8'); }
	if (!defined('_BROWSER_LANGCODE')) { define('_BROWSER_LANGCODE', _LANGCODE); }
	echo '
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html dir="'._TEXT_DIR.'" lang="'._BROWSER_LANGCODE.'">
	<head>
	 <base href="'.$BASEHREF.'" />
	 <meta http-equiv="Content-Type" content="text/html; charset='._CHARSET.'" />
	 <title>'.$sitename.' '._BC_DELIM.' '.$title.'</title>
	 <link rel="stylesheet" href="themes/'.$CPG_SESS['theme'].'/style/style.css" type="text/css" media="screen" />
	 <link rel="stylesheet" href="includes/css/print.css" type="text/css" media="print" />
	 <style type="text/css">
<!--
.holder {
	text-indent: 20px;
	border: thin solid #000000;
	padding-right: 20px;
	padding-left: 20px;
	width: 600px;
	color: #000000;
	background: #FFFFFF;
}
-->
     </style>
	</head>
	<body>
	<table align="center" class="holder">
  <tr>
    <td>
	<p align="center">
	<strong>'.$title.'</strong><br />
	'.formatDateTime($time, _DATESTRING).'<br /><br />'.
	_POSTEDBY.' '.$author.'
	</p>
	<p align="left">
	'.$hometext.'<br /><br />
	'. $bodytext.'</p>';
	if ($notes != '') {
		echo '<br /><br /><b>Notes:</b> '.$notes;
	}
	echo '</td>
  </tr>
</table>
	<p align="center">
	Content received from: '.$sitename.', '.$nukeurl.'
	</p></body>
	</html>';
} else {
	url_redirect(getlink());
}
