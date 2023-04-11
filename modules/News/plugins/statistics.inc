<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) exit;

if (\Dragonfly\Modules::isActive(basename(dirname(__DIR__)))) {

	$TBL = Dragonfly::getKernel()->SQL->TBL;

	Dragonfly_Module_Statistics::$misc[] = array (
		'class' => 'news',
		'name'  => _STORIESPUBLISHED,
		'url'   => '',
		'hits'  => $TBL->stories->count()
	);

	Dragonfly_Module_Statistics::$misc[] = array (
		'class' => 'comments',
		'name'  => _COMMENTSPOSTED,
		'url'   => '',
		'hits'  => $TBL->comments->count()
	);

	Dragonfly_Module_Statistics::$misc[] = array (
		'class' => 'waiting',
		'name'  => _NEWSWAITING,
		'url'   => '',
		'hits'  => $TBL->queue->count()
	);
}
