<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2016 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('referers')) { exit('Access Denied'); }

$db = \Dragonfly::getKernel()->SQL;

if (isset($_GET['del']) && 'all' == $_GET['del'])
{
	$db->TBL->referer->delete();
	URL::redirect(URL::admin());
}
else
{
	\Dragonfly\Page::title(_HTTPREFERERS);
	$TPL = Dragonfly::getKernel()->OUT;
	$TPL->referers = $db->query("SELECT url FROM {$db->TBL->referer}");
	$TPL->no_referers = sprintf(_ERROR_NONE_TO_DISPLAY, strtolower(_HTTPREFERERS));
	$TPL->display('admin/referers');
}
