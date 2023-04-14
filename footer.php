<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/footer.php,v $
  $Revision: 9.23 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 04:01:38 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

function footmsg() {
	if (CPG_DEBUG) {
		if (function_exists('themesidebox')) {
			depricated_warning('themesidebox', (PHPVERS >= 43) ? debug_backtrace() : false);
		} else {
			function themesidebox($title, $content, $bid=0) {
				depricated_warning('themesidebox', (PHPVERS >= 43) ? debug_backtrace() : false);
				return false;
			}
		}
	}
	global $db, $foot1, $foot2, $foot3, $total_time, $start_mem;
	if ($foot1 != '') { $foot1 .= '<br />'."\n"; }
	if ($foot2 != '') { $foot1 .= $foot2.'<br />'."\n"; }
	if ($foot3 != '') { $foot1 .= $foot3.'<br />'."\n"; }
	if (is_admin()) {
		$total_time = (get_microtime() - START_TIME - $db->time);
		$foot1 .= sprintf(_PAGEFOOTER, round($total_time,4), $db->num_queries, round($db->time,4));
		// only works if your PHP is compiled with the --enable-memory-limit configuration option
		if (function_exists('memory_get_usage') && $start_mem > 0) {
			$total_mem = memory_get_usage()-$start_mem;
			$foot1 .= '<br />Memory Usage: '.(($total_mem >= 1048576) ? round((round($total_mem / 1048576 * 100) / 100), 2).' MB' : (($total_mem >= 1024) ? round((round($total_mem / 1024 * 100) / 100), 2).' KB' : $total_mem.' Bytes'));
		}
		$foot1 .= '<br />';
	}
// MS-Analysis Entry
//	  require( "modules/MS_Analysis/mstrack.php" );
	$foot1 = '<div style="text-align:center;">'.$foot1.'
	Interactive software released under <a href="http://dragonflycms.org/GNUGPL.html" target="_blank" title="GNU Public License Agreement">GNU GPL</a>,
	<a href="'.getlink('credits').'">Code Credits</a>,
	<a href="'.getlink('privacy_policy').'">Privacy Policy</a></div>';

	global $MAIN_CFG, $cpgtpl, $cpgdebugger;
	$debug_php = $debug_sql = false;
	if (is_admin() || CPG_DEBUG) {
		$strstart = strlen(BASEDIR);
		if ($MAIN_CFG['debug']['database']) {
			$debug_sql = '<span class="genmed"><strong>SQL Queries:</strong></span><br /><br />';
			foreach ($db->querylist as $file => $queries) {
				$file = substr($file, $strstart);
				if (empty($file)) $file = 'unknown file';
				$debug_sql .= '<b>'.$file.'</b><ul>';
				foreach ($queries as $query) { $debug_sql .= "<li>$query</li>"; }
				$debug_sql .= '</ul>';
			}
		}
		$report = $cpgdebugger->stop();
		if (is_array($report)) {
			foreach ($report as $file => $errors) {
				$debug_php .= '<b>'.substr($file, $strstart).'</b><ul>';
				foreach ($errors as $error) { $debug_php .= "<li>$error</li>"; }
				$debug_php .= '</ul>';
			}
		}
	}
	$cpgtpl->assign_vars(array(
		'S_DEBUG_PHP' => $debug_php,
		'S_DEBUG_SQL' => $debug_sql
	));
	unset($debug_php, $debug_sql);
	return $foot1;
}

global $db, $SESS, $cpgtpl, $Blocks;
$Blocks->display('d');
if (!function_exists('themefooter')) {
	global $sitename, $nukeurl;
	echo '<div style="text-align:center;">Content received from: '.$sitename.', '.$nukeurl.'</div>';
} else {
	themefooter();
//	$cpgtpl->assign_vars(array('S_FOOTER' => footmsg()));
//	$cpgtpl->set_filenames(array('footer' => 'footer.html'));
//	$cpgtpl->display('footer');
}

$cpgtpl->destroy();
$SESS->write_close();
$db->sql_close();
if (GZIP_COMPRESS) {
	// Copied from php.net!
	$gzip_contents = ob_get_contents();
	ob_end_clean();
	$gzip_size = strlen($gzip_contents);
	$gzip_crc = crc32($gzip_contents);
	$gzip_contents = gzcompress($gzip_contents, 9);
	$gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);
	echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
	echo $gzip_contents;
	echo pack('V', $gzip_crc);
	echo pack('V', $gzip_size);
}
exit;
