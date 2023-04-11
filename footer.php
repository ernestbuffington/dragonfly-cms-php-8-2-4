<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }
global $cpgtpl;

function footmsg()
{
	$K = \Dragonfly::getKernel();
	$CFG = $K->CFG;
	$foot = array_filter(array($CFG->global->foot1, $CFG->global->foot2, $CFG->global->foot3));
	if (CPG_DEBUG || is_admin()) {
		$total_time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'] - $K->SQL->time);
		$foot[] = sprintf(_PAGEFOOTER, round($total_time,4), $K->SQL->num_queries, round($K->SQL->time,4));
		// only works if your PHP is compiled with the --enable-memory-limit configuration option
		if (START_MEMORY_USAGE > 0) {
			$total_mem = memory_get_usage()-START_MEMORY_USAGE;
			$foot[] = 'Memory Usage: '.(($total_mem >= 1048576) ? round((round($total_mem / 1048576 * 100) / 100), 2).' MB' : (($total_mem >= 1024) ? round((round($total_mem / 1024 * 100) / 100), 2).' KB' : $total_mem.' Bytes'));
		}
		$foot[] = '';
		$DEBUGGER = Dragonfly::getKernel()->DEBUGGER;
		$S_DEBUG_SQL = CPG_DEBUG || defined('INSTALL') || $CFG->debug->database ? \Dragonfly\Output\HTML::minify($DEBUGGER->get_report('sql')) : false;
		$S_DEBUG_PHP = CPG_DEBUG || defined('INSTALL') || $CFG->debug->error_level ? \Dragonfly\Output\HTML::minify($DEBUGGER->get_report('php')) : false;
	}
	if ($GLOBALS['cpgtpl']) {
		$GLOBALS['cpgtpl']->S_DEBUG_SQL = isset($S_DEBUG_SQL) ? $S_DEBUG_SQL : false;
		$GLOBALS['cpgtpl']->S_DEBUG_PHP = isset($S_DEBUG_PHP) ? $S_DEBUG_PHP : false;
	}
	return \Dragonfly\Output\HTML::minify('<div class="core_footer">'.implode("<br />\n", $foot).'
	<div>Interactive software released under <a href="http://dragonflycms.org/GNUGPL.html" target="_blank" title="GNU Public License Agreement">GNU GPL</a>,
	<a href="'.URL::index('credits').'">Code Credits</a>,
	<a href="'.URL::index('privacy_policy').'">Privacy Policy</a></div></div>');
//	$GLOBALS['DF']->setState(DF::BOOT_DOWN);
}

if (function_exists('themefooter')) {
	themefooter();
}

if ($cpgtpl) {
	$cpgtpl->destroy();
}
//Dragonfly::getKernel()->SESSION->write_close(); // not needed?
exit;
