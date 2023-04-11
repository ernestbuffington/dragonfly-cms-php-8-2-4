<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }

\Dragonfly\Page::title(_BPM, false);
$MAIN_CFG = Dragonfly::getKernel()->CFG;
if ('POST' === $_SERVER['REQUEST_METHOD'])
{
	if (!\Dragonfly\Output\Captcha::validate($_POST)) { cpg_error(_SEC_ERROR); }

	$graphic_length = $_POST->uint('graphic_length');
	if ('%' === $_POST->txt('graphic_style')) {
		$graphic_length = min(100, $graphic_length);
	}
	$MAIN_CFG->set('Private_Messages', 'inbox_max',      $_POST->uint('inbox_max'));
	$MAIN_CFG->set('Private_Messages', 'sentbox_max',    $_POST->uint('sentbox_max'));
	$MAIN_CFG->set('Private_Messages', 'outbox_max',     $_POST->uint('outbox_max'));
	$MAIN_CFG->set('Private_Messages', 'savebox_max',    $_POST->uint('savebox_max'));
	$MAIN_CFG->set('Private_Messages', 'graphic_length', $graphic_length);
	$MAIN_CFG->set('Private_Messages', 'graphic_style',  $_POST->txt('graphic_style'));
	$MAIN_CFG->set('Private_Messages', 'per_page',       $_POST->uint('per_page'));
	$MAIN_CFG->set('Private_Messages', 'flood_interval', $_POST->uint('flood_interval'));
	$MAIN_CFG->set('Private_Messages', 'allow_bbcode',   $_POST->bool('allow_bbcode'));
	$MAIN_CFG->set('Private_Messages', 'allow_smilies',  $_POST->bool('allow_smilies'));
	URL::redirect(URL::admin($op));
}

\Dragonfly::getKernel()->OUT->display('Private_Messages/admin/index');
