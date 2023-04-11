<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (isset($_SERVER['HTTP_HOST']) && preg_match('#^[a-z0-9-\.]+$#', $_SERVER['HTTP_HOST'])) {
	$_SERVER['REQUEST_URI'] = str_ireplace('rss/forums.php', '', $_SERVER['REQUEST_URI']);
	header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}?feed=Forums", true, 301);
} else {
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
}
exit;
