<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/banners.php,v $
  $Revision: 9.6 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:34 $
**********************************************/
if (!defined('CPG_NUKE')) {
	define('XMLFEED', 1);
	require_once('includes/cmsinit.inc');
	$bid = intval($_GET['bid']);
	$row = $db->sql_ufetchrow('SELECT clickurl FROM '.$prefix."_banner WHERE bid='$bid'");
	$db->sql_query('UPDATE '.$prefix."_banner SET clicks=clicks+1 WHERE bid='$bid'");
	url_redirect($row['clickurl']);
} else {
	echo viewbanner();
}
