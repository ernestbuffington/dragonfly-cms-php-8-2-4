<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('modules')) { die('Access Denied'); }

$K = Dragonfly::getKernel();
$OUT = $K->OUT;
$L10N = $K->L10N;
$L10N->load('dragonfly/blocks');

if (isset($_GET['change'])) {
	if ('modules' != $_SESSION['CPG_SESS']['admin']['page']) { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	$mid = $_GET->uint('change');
	$result = $db->query("SELECT active FROM {$db->TBL->modules} WHERE mid={$mid} AND title<>'Your_Account'");
	if ($result->num_rows) {
		list($active) = $result->fetch_row();
		$db->TBL->modules->update(array('active'=>!$active), "mid={$mid}");
		\Dragonfly\ModManager\SetupBase::clearCache();
	}
	URL::redirect(URL::admin('modules'));
}

else if (isset($_GET['a'])) {
	if (isset($_POST['confirm'])) {
		if ('all' == $_GET['a']) {
			$db->TBL->modules->update(array('active'=>1), "title<>'Your_Account'");
		} else if ('none' == $_GET['a']) {
			$db->TBL->modules->update(array('active'=>0), "title<>'Your_Account'");
		}
		\Dragonfly\ModManager\SetupBase::clearCache();
	} else if (!isset($_POST['cancel'])) {
		if ($_GET['a'] == 'all') {
			$a = 'all';
			$msg = sprintf(_SURETO,_ACTIVATE,_MODULES);
		} else {
			$a = 'none';
			$msg = sprintf(_SURETO,_DEACTIVATE,_MODULES);
		}
		\Dragonfly\Page::confirm(URL::admin("&a={$a}"), $msg);
	}
	URL::redirect(URL::admin('modules'));
}

else if (isset($_GET['home'])) {
	$mid = $_GET->uint('home');
	list($title) = $db->uFetchRow("SELECT title FROM {$db->TBL->modules} WHERE mid={$mid}");
	if (isset($_POST['confirm'])) {
		if ($_SESSION['CPG_SESS']['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		$db->TBL->config_custom->update(array('cfg_value'=>$title), "cfg_field='main_module' AND cfg_name='global'");
		$db->TBL->modules->update(array('active'=>1), "mid={$mid}");
		\Dragonfly\ModManager\SetupBase::clearCache();
	} else if (!isset($_POST['cancel'])) {
		\Dragonfly\Page::confirm(URL::admin('&home='.$mid), _SURETOCHANGEMOD.' "'.$MAIN_CFG['global']['main_module'].'" '._TO." \"{$title}\"?");
	}
	URL::redirect(URL::admin('modules'));
}

else if (isset($_GET['edit'])) {
	$mid = $_GET->uint('edit');
	if (isset($_POST['save'])) {
		$db->TBL->modules->update(array(
			'custom_title' => $_POST['custom_title'],
			'view' => (int)$_POST->uint('view'),
			'blocks' => empty($_POST['module_blocks']) ? 0 : array_sum($_POST['module_blocks']),
		),"mid={$mid}");
		//Dragonfly::getKernel()->CACHE->delete('blocks_list');
		URL::redirect(URL::admin('modules'));
	}

	if (isset($_POST['upgrade']) || isset($_POST['downgrade'])) {
	//	if ($_SESSION['CPG_SESS']['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		define('ADMIN_MOD_INSTALL', 1);
		try {
			list($modname, $version) = $db->uFetchRow("SELECT title, version FROM {$db->TBL->modules} WHERE mid={$mid}");
			if (is_file(\Dragonfly::getModulePath($modname).'install/cpg_inst.php')) {
				$installer = new \Dragonfly\ModManager\Setup($modname);
			} else {
				$installer = new \Dragonfly\ModManager\SetupV9($modname);
			}
			\Dragonfly\ModManager\SetupBase::clearCache();
			if (!$installer->update_module($version)) {
				cpg_error(_UPGRADEFAILED .': ' .$installer->error, _UPGRADEFAILED);
			}
		} catch (\Exception $e) {
			cpg_error(_UPGRADEFAILED .': ' .$e->getMessage(), _UPGRADEFAILED);
		}
		cpg_error(_TASK_COMPLETED, 'Module update suceeded', DF_MODE_DEVELOPER ? false : URL::admin('modules'));
	}

//	list($title, $custom_title, $view, $blocks, $version) =
	\Dragonfly\Page::title(_MODULEEDIT);
	$module = $db->uFetchAssoc("SELECT title, custom_title, view, blocks, version FROM {$db->TBL->modules} WHERE mid={$mid}");
	$module = array_merge($module, array(
		'is_main_module' => ($module['title'] == $MAIN_CFG['global']['main_module']),
		'author' => null,
		'website' => null,
		'description' => null,
		'upgrade' => null,
		'downgrade' => null,
		'dbsize' => 0,
		'dbtables' => array(),
	));
	define('ADMIN_MOD_INSTALL', 1);
	$editmodule = \Dragonfly\ModManager\Setup::getModuleClass($module['title']);
	if ($editmodule) {
		$module['description'] = $editmodule->description;
		if (version_compare($module['version'], $editmodule->version, '<')) {
			$module['upgrade'] = sprintf(_UPGRADE, $editmodule->version);
		} else if (version_compare($module['version'], $editmodule->version, '>')) {
			$module['downgrade'] = "Downgrade to version {$editmodule->version}";
		}
		if (isset($editmodule->dbtables) && !empty($editmodule->dbtables)) {
			$dbsize = 0;
			if ($result = $db->tablesStatus()) {
				$prefix_l = strlen($db->TBL->prefix);
				while ($table = $result->fetch_assoc()) {
					if (in_array(substr($table['Name'], $prefix_l), $editmodule->dbtables) &&
						((isset($table['Type']) && $table['Type'] != 'MRG_MyISAM') || (isset($table['Engine']) && $table['Engine'] != "MRG_MyISAM"))
					) {
						$dbsize += $table['Data_length'] + $table['Index_length'];
					}
				}
			}
			$module['dbsize'] = $OUT->L10N->filesizeToHuman($dbsize);
			foreach ($editmodule->dbtables as $table) {
				$module['dbtables'][] = $db->TBL->$table;
			}
		}
		if (strlen($editmodule->website) > 3) {
			if ('http' !== substr($editmodule->website,0,4)) {
				$module['website'] = 'http://';
			}
			$module['website'] .= $editmodule->website;
		}
		$module['author'] = $editmodule->author;
	}

	$OUT->module = $module;
	$OUT->display('admin/modules/module');
}

else {
	define('ADMIN_MOD_INSTALL', 1);

	if (isset($_GET['install']) && preg_match('#^[a-z0-9_\-]+$#i', $_GET['install'])) {
		if ($_SESSION['CPG_SESS']['admin']['page'] != 'modules') {
			cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
		}
		$modname = $_GET['install'];
		if (is_file(\Dragonfly::getModulePath($modname)."install/cpg_inst.php")) {
			$installer = new \Dragonfly\ModManager\Setup($modname);
		} else {
			$installer = new \Dragonfly\ModManager\SetupV9($modname);
		}
		if ($installer->error || !$installer->add_module()) {
			cpg_error($installer->error, 'Module install failed');
		}
		\Dragonfly\ModManager\SetupBase::clearCache();
		cpg_error('The module "'.$modname.'" has been properly installed, have a blast using it!', 'Module install succeeded', DF_MODE_DEVELOPER ? false : URL::admin('modules'));
	}

	else if (isset($_GET['uninstall']) && preg_match('#^[a-z0-9_\-]+$#i', $_GET['uninstall'])) {
		if ($_SESSION['CPG_SESS']['admin']['page'] != 'modules') {
			cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
		}
		$modname = $_GET['uninstall'];
		if (isset($_POST['confirm']) && \Dragonfly\Output\Captcha::validate($_POST)) {
			if (is_file(\Dragonfly::getModulePath($modname).'install/cpg_inst.php')) {
				$installer = new \Dragonfly\ModManager\Setup($modname);
			} else {
				$installer = new \Dragonfly\ModManager\SetupV9($modname);
			}
			if ($installer->error || !$installer->remove_module()) {
				cpg_error($installer->error, 'Module uninstall failed');
			}
			\Dragonfly\ModManager\SetupBase::clearCache();
			cpg_error('The module "'.$modname.'" has been properly uninstalled, you can safely delete the files associated with it', 'Module uninstall succeeded', DF_MODE_DEVELOPER ? false : URL::admin('modules'));
		} else if (!isset($_POST['cancel'])) {
			\Dragonfly\Page::confirm(URL::admin('&uninstall='.$modname), 'Are you sure that you want to remove all data associated with "'.$modname.'"?');
		}
		URL::redirect(URL::admin('modules'));
	}

	$mods = array();
	$result = $db->query("SELECT mid, title, custom_title, version, active, view, uninstall, blocks FROM {$db->TBL->modules}");
	while ($row = $result->fetch_assoc()) {
		if (!is_file(\Dragonfly::getModulePath($row['title'])."index.php")) {
			$db->TBL->modules->delete("mid={$row['mid']}");
			$db->optimize($db->TBL->modules);
			$db->TBL->blocks_custom->delete("mid={$row['mid']}");
			$db->optimize($db->TBL->blocks_custom);
			//Dragonfly::getKernel()->CACHE->delete('blocks_list');
		} else {
			$row['is_main_module'] = ($row['title'] == $MAIN_CFG['global']['main_module']);
			$m = \Dragonfly\ModManager\Setup::getModuleClass($row['title']);
			if ($m) {
				$row['needs_upgrade'] = version_compare($row['version'], $m->version, '<');
			}
			$mods[$row['title']] = $row;
		}
	}

	$handle = Dragonfly\Modules::ls('index.php', false);
	foreach ($handle as $class => $file) {
		if (!isset($mods[$class])) {
			$mod = array('title' => $class, 'custom_title' => '', 'active' => 0, 'view' => 0, 'uninstall' => 0);
			$m = \Dragonfly\ModManager\Setup::getModuleClass($class);
			if ($m) {
				$mod['mid'] = 0;
				$mod['custom_title'] = $m->description;
				$mod['uninstall']    = -1;
			} else {
				$mod['mid'] = $db->TBL->modules->insert($mod,'mid');
				$result = $db->query("SELECT bid, bposition FROM {$db->TBL->blocks} WHERE active=1");
				if ($result->num_rows) {
					$in_modules = array();
					$l = $c = $r = $d = 0;
					while ($row = $result->fetch_row()) {
						$in_modules[] = "({$row[0]}, '{$mod['mid']}', '{$row[1]}', ".++${$row[1]}.")";
					}
					$db->query("INSERT INTO {$db->TBL->blocks_custom} (bid, mid, side, weight) VALUES ".implode(',', $in_modules));
					//Dragonfly::getKernel()->CACHE->delete('blocks_list');
				}
				$result->free();
			}
			$mod['is_main_module'] = false;
			$mods[$class] = $mod;
		}
	}

	\Dragonfly\Page::title(_MODULES);

	foreach ($mods AS $title => $row) {
		$row['l10n_title'] = \Dragonfly\Modules\Module::get_title($row['title']);
		$i = ($row['is_main_module']?0:1) . '-' . ($row['active']?0:1) . '-' . $row['l10n_title'];
		$allmods[$i] = $row;
	}
	uksort($allmods, 'strnatcasecmp');
	$groups = \Dragonfly\Groups::getSystem();
	foreach ($allmods as $row)
	{
		if (($row['active'] != 1 || $row['view'] != 0) &&
		    ($row['title'] == 'Your_Account' || $row['is_main_module'])) {
			$row['view'] = 0;
			$db->TBL->modules->update(array('active'=>1,'view'=>0), "mid={$row['mid']}");
		}

		$moduledir = $row['title'];

		$sides = array();
		if (isset($row['blocks'])) {
			$row['blocks'] = (int)$row['blocks'];
			if ($row['blocks'] == 0) $sides[] = _NONE;
			if ($row['blocks'] & \Dragonfly\Blocks::LEFT)   $sides[] = $L10N->get('Left');
			if ($row['blocks'] & \Dragonfly\Blocks::RIGHT)  $sides[] = $L10N->get('Right');
			if ($row['blocks'] & \Dragonfly\Blocks::CENTER) $sides[] = $L10N->get('Center Up');
			if ($row['blocks'] & \Dragonfly\Blocks::DOWN)   $sides[] = $L10N->get('Center Down');
		}

		$OUT->assign_block_vars('mod', array(
			'B_HOME' => $row['is_main_module'],
			'B_ACTIVE' => $row['active'],
			'S_CLASS' => (!empty($row['needs_upgrade']) ? 'upgrade' : (0 > $row['uninstall'] ? 'install' : '')),
			'S_TITLE' => $row['l10n_title'],
			'S_CUSTOM_TITLE' => $row['custom_title'],
			'S_VIEW' => $groups[(int)($row['view'] > 3)]['groups'][$row['view']]['label'],
			'S_BLOCKS' => implode(', ', $sides),
			'U_INDEX' => (0 > $row['uninstall']) ? null : URL::index($moduledir),
			'U_EDIT' => URL::admin('&edit='.$row['mid']),
			'U_CHANGE' => URL::admin('&change='.$row['mid']),
			'U_INSTALL' => (0 > $row['uninstall']) ? URL::admin("&install={$moduledir}") : null,
			'U_UPGRADE' => empty($row['needs_upgrade']) ? null : URL::admin('&edit='.$row['mid']),
			'U_UNINSTALL' => (0 < $row['uninstall']) ? URL::admin("&uninstall={$moduledir}") : null,
			'U_SET_HOME' => $row['is_main_module'] ? null : URL::admin("&home={$row['mid']}"),
		));

	}
	$OUT->display('admin/modules/index');
}
