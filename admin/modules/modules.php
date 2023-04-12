<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/modules.php,v $
  $Revision: 9.50 $
  $Author: nanocaiordo $
  $Date: 2007/12/18 11:37:21 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }

if (isset($_GET['change'])) {
	if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	$mid = intval($_GET['change']);
	$result = $db->sql_query('SELECT active FROM '.$prefix."_modules WHERE mid=$mid AND title<>'Your_Account'");
	if ($db->sql_numrows($result) > 0) {
		list($active) = $db->sql_fetchrow($result);
		if (is_numeric($active)) {
			$active = intval(!$active);
			$db->sql_query('UPDATE '.$prefix."_modules SET active='$active' WHERE mid=$mid");
		}
	}
	Cache::array_delete('active_modules');
	Cache::array_delete('waitlist');
	Cache::array_delete('adlinks');
	url_redirect(adminlink('modules'));
}
else if (isset($_GET['a'])) {
	if (isset($_POST['confirm'])) {
		if ($_GET['a'] == 'all') {
			$db->sql_query('UPDATE '.$prefix."_modules SET active=1 WHERE title<>'Your_Account'");
		} else if ($_GET['a'] == 'none') {
			$db->sql_query('UPDATE '.$prefix."_modules SET active=0 WHERE title<>'Your_Account'");
		}
		Cache::array_delete('active_modules');
		Cache::array_delete('waitlist');
		Cache::array_delete('adlinks');
	} else if (!isset($_POST['cancel'])) {
		if ($_GET['a'] == 'all') {
			$a = 'all';
			$msg = sprintf(_SURETO,_ACTIVATE,_MODULES);
		} else {
			$a = 'none';
			$msg = sprintf(_SURETO,_DEACTIVATE,_MODULES);
		}
		cpg_delete_msg(adminlink("&amp;a=$a"), $msg);
	}
	url_redirect(adminlink('modules'));
}
else if (isset($_GET['home'])) {
	$mid = intval($_GET['home']);
	if (isset($_POST['confirm'])) {
		if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		list($title) = $db->sql_ufetchrow("SELECT title FROM ".$prefix."_modules WHERE mid='$mid'",SQL_NUM);
		$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='$title' WHERE cfg_field='main_module' AND cfg_name='global'");
		$db->sql_query("UPDATE ".$prefix."_modules SET active=1, view=0 WHERE mid='$mid'");
		Cache::array_delete('MAIN_CFG');
		Cache::array_delete('active_modules');
		//Cache::array_delete('blocks_list');
		Cache::array_delete('waitlist');
		Cache::array_delete('adlinks');
	} else if (!isset($_POST['cancel'])) {
		list($new_m) = $db->sql_ufetchrow('SELECT title FROM '.$prefix."_modules WHERE mid=$mid");
		cpg_delete_msg(adminlink('&amp;home='.$mid), _SURETOCHANGEMOD.' <strong>'.$MAIN_CFG['global']['main_module'].'</strong> '._TO." <strong>$new_m</strong>?");
	}
	url_redirect(adminlink('modules'));
}
else if (isset($_GET['edit'])) {
	$mid = intval($_GET['edit']);
	list($title, $custom_title, $view, $inmenu, $blocks, $version) = $db->sql_ufetchrow("SELECT title, custom_title, view, inmenu, blocks, version FROM ".$prefix."_modules WHERE mid=$mid",SQL_NUM);
	$pagetitle .= ' '._BC_DELIM.' '._MODULEEDIT;
	$inst_file = (is_file('modules/'.$title.'/sql/cpg_inst.php') ? 'modules/'.$title.'/sql/cpg_inst.php' : (is_file('modules/'.$title.'/cpg_inst.php') ? 'modules/'.$title.'/cpg_inst.php' : false));
	if ($inst_file) {
		define('ADMIN_MOD_INSTALL', 1);
		include($inst_file);
		if (class_exists($title)) {
			$module = new $title;
		}
	}
	require('header.php');
	GraphicAdmin('_AMENU1');
	OpenTable();
	$a = ($title == $MAIN_CFG['global']['main_module']) ? ' - ('._INHOME.')' : '';
	echo open_form(adminlink('modules'), '', ($title.$a))
	.'<label class="ulog" for="custom_title">'._CUSTOMTITLE.'</label>
	<input type="text" name="custom_title" id="custom_title" value="'.$custom_title.'" size="30" maxlength="255" /><br />';
	if ($title == $MAIN_CFG['global']['main_module']) {
		echo '<input type="hidden" name="view" value="0" />';
	} else {
		echo '<label class="ulog" for="view">'._VIEWPRIV.'</label>'.group_selectbox('view', $view).'<br />';
	}
	echo '<label class="ulog" for="inmenu">'._SHOWINMENU.'</label>'.yesno_option('inmenu', $inmenu).'<br />
	<label class="ulog" for="blocks">'._BLOCKS.'</label>'.select_box('blocks', $blocks, array('0'=>_NONE, '1'=>_LEFT, '2'=>_RIGHT, '3'=>_BOTH)).'<br /><br />
	<input type="hidden" name="save" value="'.$mid.'" />
	<input type="submit" value="'._SAVECHANGES.'" />'.close_form();
	if (isset($module)) {
		if ($version != $module->version) $version .= ' <a href="'.adminlink('&amp;upgrade='.$mid).'">'.sprintf(_UPGRADE, $module->version).'</a>';
		$dbsize = 0;
		$backup = '';
		if (isset($module->dbtables) && !empty($module->dbtables)) {
			if (SQL_LAYER == 'mysql') {
				if ($result = $db->sql_query("SHOW TABLE STATUS FROM `$dbname`", true)) {
					while($table = $db->sql_fetchrow($result)) {
						if (in_array(substr($table['Name'], strlen($prefix)+1), $module->dbtables) &&
							((isset($table['Type']) && $table['Type'] != 'MRG_MyISAM') || (isset($table['Engine']) && $table['Engine'] != "MRG_MyISAM"))
						) {
							$dbsize += $table['Data_length'] + $table['Index_length'];
						}
					}
				}
			}
			$backup = '<label class="ulog">'._DBSIZE.'</label>'.filesize_to_human($dbsize).', <a href="'.adminlink('&amp;backup='.$title).'">'._SAVEDATABASE.'</a><br />';
		}
		if (strlen($module->website) > 3) $author = '<a href="http://'.$module->website.'" target="_blank">'.$module->author.'</a>';
		else $author = $module->author;
		echo '<fieldset><legend><b>'._TB_INFO.'</b></legend>
	<label class="ulog">'._CREDITS_AUTHORS.'</label>'.$author.'<br />
	<label class="ulog">'._CREDITS_DESC.'</label>'.$module->description.'<br />
	<label class="ulog">'._VERSION.'</label>'.$version.'<br />
	'.$backup.'
	</fieldset>';
	}
//	if (is_dir('modules/'.$title.'/CVS')) {
	if (is_dir('modules/'.$title.'/CVS') && is_writeable('modules/'.$title)) {
		echo '<fieldset><legend><b>CVS</b></legend>'._CVS_EXPLAIN.'<br />
		<br /><a href="'.adminlink('&amp;getcvs='.$mid).'">'._CVS_UPDATE.'</a>
		</fieldset>';
	}
	CloseTable();
}
else if (isset($_GET['upgrade'])) {
	if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	$mid = $_GET['upgrade'];
	list($class, $version) = $db->sql_ufetchrow("SELECT title, version FROM ".$prefix."_modules WHERE mid=$mid", SQL_NUM);
	if (is_file(BASEDIR.'modules/'.$class.'/sql/cpg_inst.php') && is_file(BASEDIR.'modules/'.$class.'/sql/data.inc')) {
		define('ADMIN_MOD_INSTALL', 1);
		$tablelist = $db->list_tables();
		require_once(CORE_PATH.'classes/db_check.php');
		include(BASEDIR.'modules/'.$class.'/sql/data.inc'); 
		include(BASEDIR.'modules/'.$class.'/sql/cpg_inst.php');
	}
	else if (is_file(BASEDIR.'modules/'.$class.'/cpg_inst.php')) {
		define('ADMIN_MOD_INSTALL', 1);
		include('modules/'.$class.'/cpg_inst.php');
	}
	if (!class_exists($class)) {
		cpg_error(_UPGRADEFAILED.': couldn\'t load installer');
	}
	require(CORE_PATH.'classes/installer.php');
	$installer =& new cpg_installer(true, true);
	$module = new $class;
	if ($module->upgrade($version)) {
		if (!$installer->install()) {
			cpg_error(_UPGRADEFAILED.': '.$installer->error);
		}
		$db->sql_query('UPDATE '.$prefix."_modules SET version='".$module->version."' WHERE title='$class'");
		if ($module->radmin && !isset($_SESSION['CPG_ADMIN'][$radmin])) {
			$db->sql_query('ALTER TABLE '.$prefix.'_admins ADD radmin'.strtolower($class).' INT1(1) NOT NULL DEFAULT 0',true);
			unset($_SESSION['CPG_ADMIN']);
		} elseif (!$module->radmin && isset($_SESSION['CPG_ADMIN'][$radmin])) {
			$db->sql_query('ALTER TABLE '.$prefix.'_admins DROP radmin'.strtolower($class),true);
			unset($_SESSION['CPG_ADMIN']);
		}
		Cache::array_delete('MAIN_CFG');
		Cache::array_delete('active_modules');
		require('header.php');
		cpg_error(_TASK_COMPLETED, '', adminlink('modules&edit='.$mid));
	}
	cpg_error(_UPGRADEFAILED);
}
else if (isset($_GET['getcvs'])) {
	if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	$mid = $_GET['getcvs'];
	list($title) = $db->sql_ufetchrow("SELECT title FROM ".$prefix."_modules WHERE mid=$mid", SQL_NUM);
	require_once(CORE_PATH.'classes/cvs.php');
	$log = CVS::update('modules/'.$title);
	if (!isset($log['error'])) {
		ob_end_clean();
		header('Content-Encoding: none');
		header('Content-Type: text/plain');
		echo CVS::formatlog($log);
		echo "\n\n"._TASK_COMPLETED;
		exit;
	} else {
		cpg_error($log['error']);
	}
}
else if (isset($_POST['cvsmodule'])) {
	require_once(CORE_PATH.'classes/cvs.php');
	if (!ereg('^([a-zA-Z0-9_\-]+)$', $_POST['cvsmodule'])) {
		cpg_error(sprintf(_ERROR_NO_EXIST,_FILENAME));
	}
	$path = 'modules/'.$_POST['cvsmodule'];
	if (!CVS::create($path, $_POST['server'], $_POST['folder'], $_POST['module'], $_POST['cvsusername'], $_POST['cvspassword'])) {
		cpg_error('Error creating important CVS files and folders');
	} else {
		$log = CVS::update($path);
		if (!isset($log['error'])) {
			if (!isset($log['notes'])) {
				$inst_file = (is_file($path.'/sql/cpg_inst.php') ? $path.'/sql/cpg_inst.php' : (is_file($path.'/cpg_inst.php') ? $path.'/cpg_inst.php' : false));
				if ($inst_file) {
					$cpg_inst = implode('', file($inst_file));
					if (preg_match('#class ([a-zA-Z0-9_\-]+)#si', $cpg_inst, $matches)) {
						$cpg_inst = ereg_replace("(class|function) $matches[1]", "\\1 $_POST[cvsmodule]", $cpg_inst);
						if (!file_write($inst_file, $cpg_inst)) {
							cpg_error('Module successfully recieved from CVS Repository.<br /><br /><b>NOTE</b><br />You must edit "'.$path.'/cpg_inst.php" to get the installer properly working', '', adminlink('modules'));
						}
					}
				}
				cpg_error(_TASK_COMPLETED, '', adminlink('modules'));
			}
			$log = nl2br(CVS::formatlog($log));
			cpg_error($log);
		}
		cpg_error($log['error']);
	}
}
else if (isset($_GET['backup'])) {
	$class = $_GET['backup'];
	if (file_exists(BASEDIR.'modules/'.$class.'/sql/cpg_inst.php')) {
		define('ADMIN_MOD_INSTALL', 1);
		include('modules/'.$class.'/sql/cpg_inst.php');
	} else if (file_exists(BASEDIR.'modules/'.$class.'/cpg_inst.php')) {
		define('ADMIN_MOD_INSTALL', 1);
		include('modules/'.$class.'/cpg_inst.php');
	}
	if (defined('ADMIN_MOD_INSTALL') && class_exists($class)) {
		$module = new $class;
		$tables = array();
		require_once(CORE_PATH.'classes/sqlctrl.php');
		foreach($module->dbtables AS $table) {
			$tables[] = $prefix.'_'.$table;
		}
		SQLCtrl::backup($dbname, $tables, str_replace(' ', '_', $module->modname).'.sql');
	}
}
else if (isset($_POST['save'])) {
	if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	$mid = intval($_POST['save']);
	$custom_title = Fix_Quotes($_POST['custom_title'], true);
	$view = intval($_POST['view']);
	$inmenu = intval($_POST['inmenu']);
	$blocks = intval($_POST['blocks']);
	$result = $db->sql_query('SELECT title FROM '.$prefix."_modules WHERE mid=$mid");
	if ($db->sql_numrows($result) > 0) {
		list($title) = $db->sql_fetchrow($result);
		if ($title == 'Your_Account') $view = 0;
		$db->sql_query("UPDATE ".$prefix."_modules SET custom_title='$custom_title', view=$view, inmenu=$inmenu, blocks=$blocks WHERE mid=$mid");
		Cache::array_delete('blocks_list');
	}
	url_redirect(adminlink('modules'));
}
else {
	define('ADMIN_MOD_INSTALL', 1);
	if (isset($_GET['install'])) {
		if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		$class = $_GET['install'];
		if (is_file('modules/'.$class.'/sql/cpg_inst.php') && is_file(BASEDIR.'modules/'.$class.'/sql/data.inc')) {
			$tablelist = $db->list_tables();
			require_once(CORE_PATH.'classes/db_check.php');
			include(BASEDIR.'modules/'.$class.'/sql/data.inc');
			include('modules/'.$class.'/sql/cpg_inst.php');
		} else if (is_file('modules/'.$class.'/cpg_inst.php')) {
			include('modules/'.$class.'/cpg_inst.php');
		}
		if (class_exists($class)) {
			require(CORE_PATH.'classes/installer.php');
			$installer =& new cpg_installer(true, true);
			$module = new $class;
			$addmod = $module->install();
			if (!$addmod) { cpg_error($module->description, 'Install Error'); }
			if (!$installer->install()) {
				cpg_error($installer->error, 'Install Error');
			}
		}
		$db->sql_query('INSERT INTO '.$prefix."_modules (mid, title, uninstall, version) VALUES (DEFAULT, '$class', 1, '".$module->version."')");
		$insertmid = $db->insert_id('mid');
		if (class_exists($class) && $module->radmin) {
			$db->alter_field('add', $prefix.'_admins', 'radmin'.strtolower($class), 'INT1(1)', false, '0');
			unset($_SESSION['CPG_ADMIN']);
		}
		$result = $db->sql_query('SELECT bid, bposition FROM '.$prefix.'_blocks WHERE active=1');
		$in_modules = array();
		$l = $c = $r = $d = 1;
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$in_modules[] = "($row[0], $insertmid, '$row[1]', ".$$row[1].")";
			++$$row[1];
		}
		$db->sql_freeresult($result);
		$db->sql_query('INSERT INTO '.$prefix.'_blocks_custom (bid, mid, side, weight) VALUES '.implode(',', $in_modules));
		Cache::array_delete('MAIN_CFG');
		Cache::array_delete('blocks_list');
		Cache::array_delete('waitlist');
		Cache::array_delete('adlinks');
		cpg_error("The module \"$class\" has been properly installed, have a blast using it!", 'Module Installed', adminlink('modules'));
	} else if (isset($_GET['uninstall'])) {
		if ($CPG_SESS['admin']['page'] != 'modules') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
		$class = $_GET['uninstall'];
		if (isset($_POST['confirm'])) {
			if (is_file('modules/'.$class.'/sql/cpg_inst.php')) {
				include('modules/'.$class.'/sql/cpg_inst.php');
			} else if (is_file('modules/'.$class.'/cpg_inst.php')) {
				include('modules/'.$class.'/cpg_inst.php');
			}
			if (class_exists($class)) {
				$CPG_SESS['admin']['uninstall'] = '';
				unset($CPG_SESS['admin']['uninstall']);
				require(CORE_PATH.'classes/installer.php');
				$installer =& new cpg_installer(true, true);
				$module = new $class;
				$module->uninstall();
				if (!$installer->install()) {
					cpg_error($installer->error, 'Install Error');
				}
				if ($module->radmin) {
					$db->alter_table($prefix.'_admins DROP radmin'.strtolower($class));
					unset($_SESSION['CPG_ADMIN']);
				}
				$mid = $db->sql_fetchrow($db->sql_query('SELECT mid FROM '.$prefix."_modules WHERE title='$class'"));
				$db->sql_query('DELETE FROM '.$prefix."_modules WHERE title='$class'");
				$db->optimize_table($prefix.'_modules');
				$db->sql_query('DELETE FROM '.$prefix."_blocks_custom WHERE mid='$mid[0]'");
				$db->optimize_table($prefix.'blocks_custom');
				Cache::array_delete('MAIN_CFG');
				Cache::array_delete('active_modules');
				Cache::array_delete('blocks_list');
				Cache::array_delete('waitlist');
				Cache::array_delete('adlinks');
				cpg_error('The module "'.$class.'" has been properly uninstalled, you can safely delete the files associated with it', 'Module Uninstall', adminlink('modules'));
			}
		} else if (!isset($_POST['cancel'])) {
			cpg_delete_msg(adminlink('&amp;uninstall='.$class), 'Are you sure that you want to remove all data associated with <strong>'.$class.'</strong>?');
		}
		url_redirect(adminlink('modules'));
	}

	$mods = array();
	$result = $db->sql_query("SELECT mid, title, custom_title, active, view, inmenu, uninstall, blocks FROM ".$prefix."_modules");
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		if (!file_exists("modules/{$row['title']}/index.php")) {
			$db->sql_query("DELETE FROM {$prefix}_modules WHERE title='{$row['title']}'");
			$db->optimize_table($prefix.'_modules');
			$db->sql_query("DELETE FROM {$prefix}_blocks_custom WHERE mid='{$row['mid']}'");
			$db->optimize_table($prefix.'blocks_custom');
			Cache::array_delete('blocks_list');
		} else {
			$mods[$row['title']] = $row;
		}
	}
	$handle = opendir('modules');
	while ($file = readdir($handle)) {
		if (!ereg('[.]',$file) && $file != 'CVS') {
			$class = "$file";
			if ($class != '' && !isset($mods[$class])) {
				if (file_exists('modules/'.$class.'/index.php')) {
					$inst_file = (is_file('modules/'.$class.'/sql/cpg_inst.php') ? 'modules/'.$class.'/sql/cpg_inst.php' : (is_file('modules/'.$class.'/cpg_inst.php') ? 'modules/'.$class.'/cpg_inst.php' : false));
					if ($inst_file) {
						include($inst_file);
						if (class_exists($class)) {
							$module = new $class;
							$mods[$class] = array('mid' => 0, 'title' => $class, 'custom_title' => $module->description, 'active' => 0, 'view' => 0, 'inmenu' => 1, 'uninstall' => -1);
						} else {
							$mods[$class] = array('mid' => 0, 'title' => $class, 'custom_title' => "modules/$class/cpg_inst.php is missing class: $class", 'active' => 0, 'view' => 0, 'inmenu' => 1, 'uninstall' => 0);
						}
					} else {
						$db->sql_query("INSERT INTO ".$prefix."_modules (title) VALUES ('$class')");
						$mods[$class] = array('mid' => $db->sql_nextid('mid'), 'title' => $class, 'custom_title' => '', 'active' => 0, 'view' => 0, 'inmenu' => 1, 'uninstall' => 0);
						$result = $db->sql_query('SELECT bid, bposition FROM '.$prefix.'_blocks WHERE active=1');
						$in_modules = array();
						$l = $c = $r = $d = 1;
						while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
							$in_modules[] = "($row[0], '{$mods[$class]['mid']}', '$row[1]', ".$$row[1].")";
							++$$row[1];
						}
						$db->sql_freeresult($result);
						$db->sql_query('INSERT INTO '.$prefix.'_blocks_custom (bid, mid, side, weight) VALUES '.implode(',', $in_modules));
						Cache::array_delete('blocks_list');
					}
				}
			}
		}
	}
	closedir($handle);
	$pagetitle .= ' '._BC_DELIM.' '._MODULESADMIN;
	require('header.php');
	GraphicAdmin('_AMENU1');
	OpenTable();
	echo '<span class="genmed"><strong>'._MODULESADDONS.'</strong></span><br />
	'._MODULEHOMENOTE.'<br /><br />'._NOTINMENU.'<br /><br />
	<a href="'.adminlink('&amp;a=all').'">'._ACTIVATE.' '._ALL.'</a> | <a href="'.adminlink('&amp;a=none').'">'._DEACTIVATE.' '._ALL.'</a><br /><br />
	<form action="'.adminlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<table border="0" cellspacing="0" width="100%"><tr bgcolor="'.$bgcolor2.'">
	<td align="center"><strong>'._ACTIVE.'</strong></td>
	<td align="center"><strong>'._TITLE.'</strong></td>
	<td align="center"><strong>'._CUSTOMTITLE.'</strong></td>
	<td align="center"><strong>'._VIEW.'</strong></td>
	<td align="center"><strong>'._BLOCKS.'</strong></td>
	<td align="center"><strong>'._FUNCTIONS.'</strong></td></tr>';
	foreach ($mods AS $title => $row) {
		$title = (defined('_'.$row['title'].'LANG'))? (constant('_'.$row['title'].'LANG')) : ereg_replace('_', ' ', $row['title']);
		$allmods[$title] = $row;
	}
	uksort($allmods, 'strnatcasecmp');
	$bgcolor = $bgcolor3;
	while (list($title, $row) = each($allmods)) {
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		$mid = $row['mid'];
		$moduledir = $row['title'];
		if (($row['active'] != 1 || $row['view'] != 0) &&
		    ($row['title'] == 'Your_Account' || $row['title'] == $MAIN_CFG['global']['main_module'])) {
			$row['view'] = 0;
			$db->sql_query("UPDATE ".$prefix."_modules SET active=1, view=0 WHERE mid='$mid'");
		}
		if ($row['title'] == $MAIN_CFG['global']['main_module']) {
			$active = '<img src="images/home.gif" alt="'._INHOME.'" title="'._INHOME.'" />';
		} else if ($row['title'] == 'Your_Account') {
			$active = '<img src="images/checked.gif" alt="'._ACTIVE.'" title="'._ACTIVE.'" border="0" />';
		} else if ($row['active']) {
			$active = '<a href="'.adminlink('&amp;change='.$mid).'"><img src="images/checked.gif" alt="'._ACTIVE.'" title="'._DEACTIVATE.'" border="0" /></a>';
		} else {
			$active = '<a href="'.adminlink('&amp;change='.$mid).'"><img src="images/unchecked.gif" alt="'._INACTIVE.'" title="'._ACTIVATE.'" border="0" /></a>';
		}
		if ($row['view'] == 0) {
			$who_view = _MVALL;
		} elseif ($row['view'] == 1) {
			$who_view = _MVUSERS;
		} elseif ($row['view'] == 2) {
			$who_view = _MVADMIN;
		} elseif ($row['view'] > 3) {		// <= phpBB User Groups Integration
			list($who_view) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix.'_bbgroups WHERE group_id='.($row['view']-3), SQL_NUM);
		}
		if ($row['title'] != $MAIN_CFG['global']['main_module'] && !$row['inmenu']) {
			$title = "[ <big><strong>&middot;</strong></big> ] $title";
		}
		if ($row['title'] == $MAIN_CFG['global']['main_module']) {
			$title = "<strong>$title</strong>";
			$row['custom_title'] = "<strong>$row[custom_title]</strong>";
			$who_view = "<strong>$who_view</strong>";
			$change_status = '';
			$bgcolor = ' bgcolor="'.$bgcolor4.'"';
		} else {
			$change_status = ' <strong>::</strong> <a href="'.adminlink('&amp;home='.$mid).'">'._PUTINHOME.'</a>';
		}
		if ($row['uninstall'] < 0) {
			echo "<tr$bgcolor><td></td><td>$moduledir</td><td colspan=\"3\">".$row['custom_title'].'</td><td><a href="'.adminlink("modules&amp;install=$moduledir").'">Install</a></td></tr>';
		} else {
			if ($row['uninstall'] == 1) {
				$change_status .= ' <strong>::</strong> <a href="'.adminlink('&amp;uninstall='.$moduledir).'">Uninstall</a>';
			}
			if (isset($row['blocks'])) {
				if ($row['blocks'] == 0) $row['blocks'] = _NONE;
				else if ($row['blocks'] == 1) $row['blocks'] = _LEFT;
				else if ($row['blocks'] == 2) $row['blocks'] = _RIGHT;
				else if ($row['blocks'] == 3) $row['blocks'] = _BOTH;
			} else {
				$row['blocks'] = '';
			}
		echo '<tr'.$bgcolor.'>
		<td align="center">'.$active.'</td>
		<td><a href="'.getlink($row['title']).'" title="'._SHOW.'">'.$title.'</a></td>
		<td>'.$row['custom_title'].'</td>
		<td>'.$who_view.'</td>
		<td>'.$row['blocks'].'</td>
		<td><a href="'.adminlink('&amp;edit='.$mid).'">'._EDIT.'</a>'.$change_status.'</td></tr>';
		}
	}
	echo '</table></form>';
	CloseTable();
	if (is_writeable('modules')) {
	if ($MAIN_CFG['global']['admin_help']) {
		echo '
<script language="JavaScript" type="text/javascript">
<!--'."
maketip('cvsmodule','"._EXAMPLE."','will be created as \"modules/{TITLE}/\"');
maketip('server','"._EXAMPLE."','dragonflycms.org');
maketip('repository','"._EXAMPLE."','/CVS');
maketip('module','"._EXAMPLE."','modules/Shoutblock/modules/Shoutblock');
".'// -->
</script>';
	}
		OpenTable();
		echo open_form(adminlink('modules'), '', _LOADNEWCVS).'
	<label'.show_tooltip('cvsmodule').' class="ulog" for="cvsmodule">'._TITLE.'</label><input type="text" name="cvsmodule" id="cvsmodule" size="30" /><br />
	<label'.show_tooltip('server').' class="ulog" for="server">Server</label><input type="text" name="server" id="server" size="30" /><br />
	<label'.show_tooltip('repository').' class="ulog" for="repository">Server Repository</label><input type="text" name="folder" id="folder" size="30" /><br />
	<label'.show_tooltip('module').' class="ulog" for="module">Module/Path</label><input type="text" name="module" id="module" size="30" /><br />
	<label class="ulog" for="cvsusername">User name</label><input type="text" name="cvsusername" id="cvsusername" size="30" value="anonymous" /><br />
	<label class="ulog" for="cvspassword">Password</label><input type="text" name="cvspassword" id="cvspassword" size="30" /><br />
	<input type="submit" value="Checkout module" />'.close_form();
		CloseTable();
	}
}
