<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) exit;

if (\Dragonfly\Modules::isActive(basename(dirname(__DIR__)))) {
	Dragonfly_Module_Statistics::$misc[] = array (
		'class' => 'topics',
		'name'  => _SACTIVETOPICS,
		'url'   => '',
		'hits'  => Dragonfly::getKernel()->SQL->TBL->topics->count()
	);
}
