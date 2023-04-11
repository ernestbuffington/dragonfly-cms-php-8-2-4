<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('info')) { die('Access Denied'); }
if ($MAIN_CFG['global']['admingraphic'] < 1) {
	$MAIN_CFG['global']['admingraphic'] = \Dragonfly\Page\Menu\Admin::GRAPH & \Dragonfly\Page\Menu\Admin::BLOCK;
}

\Dragonfly\Page::title('System Info');

	$OUT = \Dragonfly::getKernel()->OUT;
	$OUT->info_general = array_merge(
		array('CMS Version'=>\Dragonfly::VERSION, 'CMS Path' => BASEDIR),
		\Poodle\PHP\Info::get(INFO_GENERAL));
	$OUT->info_config  = \Poodle\PHP\Info::get(INFO_CONFIGURATION);
	$OUT->info_modules = \Poodle\PHP\Info::get(INFO_MODULES);
//	$OUT->info_envi    = \Poodle\PHP\Info::get(INFO_ENVIRONMENT);
	$OUT->info_vars    = \Poodle\PHP\Info::get(INFO_VARIABLES);

	$OUT->db_versions  = $db->get_versions();
//	$OUT->db_details   = $db->get_details();
	$OUT->db_processes = $db->listProcesses();
	$OUT->db_stats     = array();
	$L10N = Dragonfly::getKernel()->L10N;
	$stat = preg_split('/:\s+([0-9]*\.?[0-9]*)/', $db->stat(), -1, PREG_SPLIT_DELIM_CAPTURE ^ PREG_SPLIT_NO_EMPTY);
	$stat[1] = $L10N->timeReadable($stat[1], '%d %h %i %s');
	for ($i = 0; isset($stat[$i]); $i += 2) {
		$val = $stat[$i + 1];
		if (is_numeric($val)) {
			if (fmod($val, 1.0) == 0) {
				$val = $L10N->round($val);
			} else {
				$val = $L10N->round($val, 3);
			}
		}
		$OUT->db_stats[] = array('name'=>$stat[$i], 'value'=>$val);
	}
	try {
		$OUT->db_status = $db->query("SHOW STATUS");
	} catch (\Exception $e) {
		$OUT->db_status = false;
	}

	$OUT->display('admin/info');
