<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/cvs.php,v $
  $Revision: 9.8 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:15:42 $
**********************************************/

class CVS {

	// CVS::createroot('modules/Shoutblock', 'cpgnuke.com', '/cvs', 'html/Shoutblock', 'djmaze')
	function create($path, $server, $folder, $module, $username='anonymous', $password='', $protocol='pserver') {
		$root = ":$protocol:$username";
		if (!empty($password)) $root .= ":$password";
		$root .= "@$server:$folder";
		return CVS::createroot($path, $root, $module);
	}
	// CVS::createroot('modules/Shoutblock', ':pserver:djmaze@cpgnuke.com:/cvs', 'html/Shoutblock')
	function createroot($path, $root, $module) {
		// create directory $path if not exists
		if (is_dir($path)) { return false; }
		if (!mkdir($path, (PHP_AS_NOBODY ? 0777 : 0755))) { return false; }
		if (!mkdir($path.'/CVS', (PHP_AS_NOBODY ? 0777 : 0755))) { return false; }
		// write $path/CVS files
		if (!file_write($path.'/CVS/Root', $root)) return false;
		if (!file_write($path.'/CVS/Repository', $module)) return false;
		$entries = '';
		if (!file_write($path.'/CVS/Entries', $entries)) return false;
		return true;
	}

	function update($path, $recursive=true) {
		if (is_dir($path.'/CVS')) {
			$MAIN_CFG['cvs']['cmd'] = 'cvs.exe';
			$MAIN_CFG['cvs']['cmd'] = 'C:\\Progra~1\\tortoisecvs\\cvs.exe';
			$cvs = WINDOWS ? $MAIN_CFG['cvs']['cmd'] : 'cvs';
			$log['cvs'] = $cvs.' -q -z6 update -Pd';
			if (!$recursive) $log['cvs'] .= 'l';
			$cvs = file($path.'/CVS/Root');
			$log['root'] = $cvs[0];
			$cvs = file($path.'/CVS/Repository');
			$log['repos'] = $cvs[0];
			if (!is_writeable($path)) {
				$log['error'] = 'NO_WRITE_ACCESS';
				return $log;
			}
			$currentdir = getcwd();
			if (!chdir($path)) {
				$log['error'] = 'NO_CHANGE_DIR';
				return $log;
			}
			set_time_limit(0);
			$tmplog = split("\n", shell_exec($log['cvs'].' 2>&1'));
			natcasesort($tmplog);
			foreach ($tmplog as $entry) {
			  if (!empty($entry)) {
				if (ereg('[CMPU]', substr($entry, 0, 1))) {
					$log['actions'][$entry[0]][] = substr($entry, 2);
				} elseif ($entry[0] == '?') {
					$log['unknown'][] = $entry;
				} else {
					$log['notes'][] = $entry;
				}
			  }
			}
			unset($tmplog);
			chdir($currentdir);
		} else {
			$log['error'] = 'NO_CVS';
		}
		return $log;
	}

	function formatlog(&$log) {
		$output = "CVSROOT: $log[root]\nModule : $log[repos]\n";
//		$log['error']
		if (isset($log['notes'])) {
			$output .= "\n=======================\nMessages:\n=======================\n";
			foreach ($log['notes'] as $note) { $output .= "$note\n"; }
		}
		foreach ($log['actions'] as $type => $files) {
			$output .= "\n=======================\n";
			if ($type == 'U') {
				$output .= "Uploaded/Updated files:\n=======================\n";
			} elseif ($type == 'P') {
				$output .= "Patched files:\n=======================\n";
			} elseif ($type == 'M') {
				$output .= "Merged/Modified files:\n=======================\n";
			} elseif ($type == 'C') {
				$output .= "Conflicted files:\n=======================\n";
			}
			foreach ($files as $file) { $output .= "  - $file\n"; }
		}
		if (isset($log['unknown'])) {
			$output .= "\n=======================\nNon-CVS files:\n=======================\n";
			foreach ($log['unknown'] as $note) { $output .= "  - $note\n"; }
		}
		return $output;
	}

}
