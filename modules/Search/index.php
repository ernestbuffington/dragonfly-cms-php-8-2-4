<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!class_exists('Dragonfly', false)) { exit; }

\Dragonfly\Page::title(_SEARCH, false);
require_once('header.php');

// Create an array of active modules with search.inc capabilities.
$modlist = array();
$handle = opendir('modules');
while ($file = readdir($handle)) {
	$file = str_replace('.phar', '', $file);
	$dir = \Dragonfly::getModulePath($file);
	if (is_file("{$dir}search.inc") && \Dragonfly\Modules::isActive($file)) {
		list($name, $view) = $db->uFetchRow("SELECT custom_title, view FROM {$db->TBL->modules} WHERE title='{$file}'");
		if ($view == 0 || ($view == 1 && is_user()) || ($view == 3 && !is_user()) || can_admin() || ($view > 3 && in_group($view-3))) {
			$modlist[$file] = array(
				'search_class' => $file.'_search',
				'module' => $file,
				'title' => $name ?: $file,
				'dir' => $dir,
			);
		}
	}
}
asort($modlist);

if (!isset($_POST['search']) && !isset($_GET['search'])) {
	foreach ($modlist as $file => $mod) {
		include_once("{$mod['dir']}search.inc");
		if (class_exists($mod['search_class'], false)) {
			$search = new $mod['search_class'];
			$modlist[$file]['search_options'] = $search->options;
		} else {
			$modlist[$file]['search_options'] = null;
		}
	}

	$TPL = \Dragonfly::getKernel()->OUT;
	$TPL->search_modules = $modlist;
	$TPL->display('Search/index');
} else {
	$page  = isset($_GET['page']) ? intval($_GET['page']) : 0;
	$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
	$query = isset($_POST['search']) ? $_POST['search'] : $_GET['search'];

	$modules = array();
	if (isset($_POST['modules'])) {
		foreach ($_POST['modules'] as $mod) {
			if (isset($modlist[$mod])) $modules[$mod] = $modlist[$mod];
		}
	} else if (isset($_GET['mod'])) {
		if (isset($modlist[$_GET['mod']])) $modules[$_GET['mod']] = $modlist[$_GET['mod']];
	} else {
		$modules = $modlist;
	}

	$TPL = \Dragonfly::getKernel()->OUT;
	$TPL->search_query = $query;
	$TPL->searches = array();

	// process all searches
	if ($modules) {
		$sql_query = Fix_Quotes($query);
		foreach ($modules as $file => $mod) {
			include_once("{$mod['dir']}search.inc");
			if (class_exists($mod['search_class'], false)) {
				$search = new $mod['search_class'];
				$search->search($sql_query, $TPL->search_query, $limit, $page);
				if ($search->result_count > 0) {
					$TPL->searches[] = $search;
				}
				unset($search);
			}
			unset($modlist[$mod['module']]);
		}
	}

	$TPL->display('Search/result');
}
