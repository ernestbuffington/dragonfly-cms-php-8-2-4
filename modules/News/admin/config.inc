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

if ('POST' === $_SERVER['REQUEST_METHOD'])
{
	$CFG = Dragonfly::getKernel()->CFG;
	$CFG->set('global', 'storyhome',      $_POST->uint('news','storyhome'));
	$CFG->set('global', 'oldnum',         $_POST->uint('news','oldnum'));
	$CFG->set('global', 'anonpost',       $_POST->bool('news','anonpost'));
	$CFG->set('global', 'articlecomm',    $_POST->bool('news','articlecomm'));

	$notify = $_POST->bool('news','notify');
	try {
		$CFG->set('global', 'notify_email', $_POST->email('news','notify_email'));
	} catch (Exception $e) {
		if ($notify) { throw $e; }
		$CFG->set('global', 'notify_email', null);
	}
	try {
		$CFG->set('global', 'notify_from', $_POST->email('news','notify_from'));
	} catch (Exception $e) {
		if ($notify) { throw $e; }
		$CFG->set('global', 'notify_from', null);
	}
	$CFG->set('global', 'notify',         $notify);
	$CFG->set('global', 'notify_subject', $_POST->txt('news','notify_subject'));
	$CFG->set('global', 'notify_message', $_POST->txt('news','notify_message'));

	URL::redirect(URL::admin('&file=config'));
}

Dragonfly::getKernel()->OUT->display('News/admin/config');
