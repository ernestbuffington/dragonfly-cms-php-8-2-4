<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/settings.php,v $
  $Revision: 9.38 $
  $Author: nanocaiordo $
  $Date: 2008/01/27 12:12:04 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { die('Access Denied'); }
$section = isset($_GET['s']) ? intval($_GET['s']) : 0;
$section_t = array('System', _MAINTENANCE, 'Cookies', _FOOTER, _BACKENDCONF, _COMMENTSOPT, _CENSOROPTIONS, _EMAILOPTIONS, _DEBUG, _MISCOPT);
if (extension_loaded('gd')) { $section_t[] = 'Security Code'; }
if (is_writeable(CORE_PATH.'config.php')) { $section_t[11] = 'config.php'; }
$section_t[12] = 'P3P';
# check for valid section
if (!isset($section_t[$section])) { url_redirect(adminlink()); }
if ($section == 12) require(BASEDIR.'includes/data/P3P.inc');
if (isset($_POST['save'])) {
	if ($_POST['save'] != $CPG_SESS['admin']['page']) { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }

	if ($section == 11) {
		$fp = fopen(CORE_PATH.'config.php', 'rb');
		$config = fread($fp, filesize(CORE_PATH.'config.php'));
		fclose($fp);
		$config = preg_replace('#\$adminindex[\s]+=[\s]+\'.*?\'#s', '$adminindex = \''.$_POST['config_admin'].'\'', $config);
		$config = preg_replace('#\$mainindex[\s]+=[\s]+\'.*?\'#s', '$mainindex = \''.(($_POST['config_index']=='[none]')?'':$_POST['config_index']).'\'', $config);
		$config = preg_replace('#define\(\'CPG_DEBUG\', [a-z]+\)#s', 'define(\'CPG_DEBUG\', '.($_POST['config_debug'] ? 'true' : 'false').')', $config);
		$config = preg_replace('#\$sitekey = \'.*?\';#s', '', $config);
		$written = false;
		if ($fp = fopen(CORE_PATH.'config.php', 'wb')) {
			$written = fwrite($fp, $config);
			fclose($fp);
		}
		if (!$written) { cpg_error('Failed modifying file.'); }
		url_redirect(adminlink('&s='.$section));
	}

	if ($section == 2 && (empty($_POST['cookie']['admin']) || empty($_POST['cookie']['member']))) {
		cpg_error(sprintf(_ERROR_NOT_SET, 'Cookie name'));
	}

	$sections = array(
		array('global' => array('sitename', 'site_logo', 'slogan', 'startdate', 'adminmail', 'crumb', 'admin_help', 'update_monitor', 'GoogleTap', 'block_frames', 'Default_Theme'),
		      'server' => array('domain', 'path')),
		array('global' => array('maintenance', 'maintenance_text')),
		array('cookie' => array('server', 'domain', 'path', 'admin', 'member')),
		array('global' => array('foot1', 'foot2', 'foot3')),
		array('global' => array('backend_title', 'backend_language')),
		array('global' => array('commentlimit', 'pollcomm', 'moderate')),
		array('global' => array('CensorMode', 'CensorReplace')),
		array('email'  => array('allow_html_email', 'smtp_on', 'smtphost', 'smtp_auth', 'smtp_uname', 'smtp_pass')),
		array('debug'  => array('database', 'session')),
		array('global' => array('banners', 'httpref', 'httprefmax', 'top'))
	);
	if (extension_loaded('gd')) { $sections[] = array('sec_code' => array('back_img', 'font', 'font_size')); }

	if (isset($sections[$section])) {
		foreach ($sections[$section] AS $area => $keys) {
		foreach ($keys AS $key) {
			if (isset($_POST[$area][$key])) {
				$value = Fix_Quotes(trim($_POST[$area][$key]));
				if ($key == 'path') {
					if (substr($value, -1) != '/') $value .= '/';
					if ($value[0] != '/') $value = '/'.$value;
				}
				elseif ($key == 'Default_Theme') { $CPG_SESS['theme'] = $value; }
				if ($value != $MAIN_CFG[$area][$key]) {
					$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".$value."' WHERE cfg_name='$area' AND cfg_field='$key'");
				}
			}
		}
		}
	}
	if ($section == 8) {
		$error_level = 0;
		if (isset($_POST['error_level'])) {
			foreach ($_POST['error_level'] AS $val) { $error_level |= intval($val); }
		}
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='$error_level' WHERE cfg_name='debug' AND cfg_field='error_level'");
	} elseif ($section == 9) {
		$admingraphic = 0;
		if (isset($_POST['admingraphic'])) {
			foreach ($_POST['admingraphic'] AS $val) { $admingraphic |= intval($val); }
		}
		if ($admingraphic == 0) $admingraphic = 2;
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='$admingraphic' WHERE cfg_name='global' AND cfg_field='admingraphic'");
	} elseif ($section == 10) {
		$sec_code = 0;
		if (isset($_POST['code_show'])) {
			foreach ($_POST['code_show'] AS $val) { $sec_code |= intval($val); }
		}
		$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='$sec_code' WHERE cfg_name='global' AND cfg_field='sec_code'");
	} elseif ($section == 12) {
		if (isset($_POST['P3P'])) {
			$cp = array();
			foreach ($_POST['P3P'] AS $key => $val) {
				if (!isset($P3P_CP[$key])) continue;
				if ($val == 1) { $cp[] = $key; }
				else if (!empty($val) && (strlen($val) == 3 || strlen($val) == 4)) { $cp[] = $val; }
			}
			if (empty($cp)) {
				$cp = $MAIN_CFG['header']['P3P_default'];
			} else {
				$cp = implode(' ', $cp);
			}
			$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='$cp' WHERE cfg_name='header' AND cfg_field='P3P'");
		}
	}

	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink('&s='.$section));
}
else {
	$pagetitle .= ' '._BC_DELIM.' '._SITECONFIG.' '._BC_DELIM.' '.$section_t[$section];
	require('header.php');
	GraphicAdmin('System');
	$cpgtpl->assign_vars(array(
		'B_ADMIN_HELP' => $MAIN_CFG['global']['admin_help'],
		'L_UM_TOGGLE' => _UM_TOGGLE,
		'L_UM_EXPLAIN' => _UM_EXPLAIN,
		'S_CPG_NUKE' => CPG_NUKE,
		'L_SITECONFIG' => _SITECONFIG,
		'S_SECTION' => $section,
		'L_SAVECHANGES' => _SAVECHANGES,
		'L_RESET' => _RESET,
		'S_ACTION' => adminlink('&amp;s='.$section)
	));

	foreach ($section_t as $key => $value) {
		$cpgtpl->assign_block_vars('menu', array(
			'B_URL' => $section != $key,
			'S_URL' => adminlink('&amp;s='.$key),
			'S_VALUE' => $value,
			'S_KEY' => $key,
			'B_NEXT' => next($section_t)
		));
	}

	foreach ($MAIN_CFG['global'] as $var => $value) {
		${$var} = $value;
	}
	if ($section == 0) {
		$handle=opendir('themes');
		while ($file = readdir($handle)) {
			if (!preg_match('#[\.]#m',$file) && $file != 'CVS') { $themelist[] = $file; }
		}
		closedir($handle);
		natcasesort($themelist);
		$LEO = !preg_match('#IIS#m', $_SERVER['SERVER_SOFTWARE']);
		$avail_settings = array(
			array(
				'L_TITLE' => _SITENAME,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[sitename]',
				'S_VALUE' => htmlprepare($sitename),
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' => 'Site Domain',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'server[domain]',
				'S_VALUE' => $MAIN_CFG['server']['domain'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 50
			),
			array(
				'L_TITLE' => 'Site Path',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'server[path]',
				'S_VALUE' => $MAIN_CFG['server']['path'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 100
			),
			array(
				'L_TITLE' => _SITELOGO,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[site_logo]',
				'S_VALUE' => $site_logo,
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' => _SITESLOGAN,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[slogan]',
				'S_VALUE' => htmlprepare($slogan),
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' => _STARTDATE,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[startdate]',
				'S_VALUE' => htmlprepare($startdate),
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 50
			),
			array(
				'L_TITLE' => _ADMINEMAIL,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[adminmail]',
				'S_VALUE' => $adminmail,
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' => _BREADCRUMB,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[crumb]',
				'S_VALUE' => isset($MAIN_CFG['global']['crumb']) ? htmlprepare($MAIN_CFG['global']['crumb']) : _BC_DELIM,
				'S_SIZE' => 8,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' => _TOOLTIPS,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[admin_help]', $admin_help)
			),
			array(
				'L_TITLE' => $LEO ? 'Activate Link Engine Optimization (LEO)' : '',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => $LEO ? yesno_option('global[GoogleTap]', $GoogleTap) : '<input name="global[GoogleTap]" value="0" type="hidden" />'
			),
			array(
				'L_TITLE' => _UM_TOGGLE,
				'L_TOOLTIP' => show_tooltip('update_monitor'),
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[update_monitor]', $MAIN_CFG['global']['update_monitor'])
			),
			array(
				'L_TITLE' => 'Block frames',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[block_frames]', $MAIN_CFG['global']['block_frames'])
			),
			array(
				'L_TITLE' => _DEFAULTTHEME,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_option('global[Default_Theme]', $Default_Theme, $themelist)
			)
		);
	} elseif ($section == 1) {
		$avail_settings = array(
			array(
				'L_TITLE' => _ACTIVE,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[maintenance]', $maintenance)
			),
			array(
				'L_TITLE' => _MESSAGE,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => '<textarea name="global[maintenance_text]" cols="42" rows="5">'.htmlprepare($maintenance_text).'</textarea>'
			)
		);
	} elseif ($section == 2) {
		$avail_settings = array(
			array(
				'L_TITLE' => 'SERVER_NAME as Cookie Domain',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('cookie[server]', intval($MAIN_CFG['cookie']['server'])).' current: '.preg_replace('#www.#m', '', $_SERVER['SERVER_NAME'])
			),
			array(
				'L_TITLE' => 'else Cookie domain',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'cookie[domain]',
				'S_VALUE' => $MAIN_CFG['cookie']['domain'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 255
			),
			array(
				'L_TITLE' =>'Cookie Path',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'cookie[path]',
				'S_VALUE' => $MAIN_CFG['cookie']['path'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 100
			),
			array(
				'L_TITLE' => 'Admin cookie name',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'cookie[admin]',
				'S_VALUE' => $MAIN_CFG['cookie']['admin'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 25
			),
			array(
				'L_TITLE' => 'Member cookie name',
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'cookie[member]',
				'S_VALUE' => $MAIN_CFG['cookie']['member'],
				'S_SIZE' => 45,
				'S_MAXLENGTH' => 25
			)
		);
	} elseif ($section == 3) {
		$avail_settings = array(
			array(
				'L_TITLE' => _FOOTERMSG,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => '<textarea name="global[foot1]" cols="50" rows="7">'.htmlprepare($foot1).'</textarea>'
			),
			array(
				'L_TITLE' => _FOOTERLINE2,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => '<textarea name="global[foot2]" cols="50" rows="7">'.htmlprepare($foot2).'</textarea>'
			),
			array(
				'L_TITLE' => _FOOTERLINE3,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => '<textarea name="global[foot3]" cols="50" rows="7">'.htmlprepare($foot3).'</textarea>'
			)
		);
	} elseif ($section == 4) {
		$avail_settings = array(
			array(
				'L_TITLE' => _BACKENDTITLE,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[backend_title]',
				'S_VALUE' => htmlprepare($backend_title),
				'S_SIZE' => 40,
				'S_MAXLENGTH' => 100
			),
			array(
				'L_TITLE' => _BACKENDLANG,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[backend_language]',
				'S_VALUE' => $backend_language,
				'S_SIZE' => 10,
				'S_MAXLENGTH' => 10
			)
		);
	} elseif ($section == 5) {
		$avail_settings = array(
			array(
				'L_TITLE' => _COMMENTSLIMIT,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[commentlimit]',
				'S_VALUE' => $commentlimit,
				'S_SIZE' => 11,
				'S_MAXLENGTH' => 10
			),
			array(
				'L_TITLE' => _COMMENTSPOLLS,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[pollcomm]', $pollcomm)
			),
			array(
				'L_TITLE' => _COMMENTSMOD,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('global[moderate]', $moderate, array(_NOMOD,_MODADMIN,_MODUSERS))
			)
		);
	} elseif ($section == 6) {
		$avail_settings = array(
			array(
				'L_TITLE' => _CENSORMODE,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('global[CensorMode]', $CensorMode, array(_NOFILTERING,_EXACTMATCH,_MATCHBEG,_MATCHANY))
			),
			array(
				'L_TITLE' => _CENSORREPLACE,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'global[CensorReplace]',
				'S_VALUE' => $CensorReplace,
				'S_SIZE' => 10,
				'S_MAXLENGTH' => 10
			)
		);
	} elseif ($section == 7) {
		$avail_settings = array(
			array(
				'L_TITLE' => _ALLOW_HTML_EMAIL,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('email[allow_html_email]', $MAIN_CFG['email']['allow_html_email'])
			),
			array(
				'L_TITLE' => _USE_SMTP,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('email[smtp_on]', $MAIN_CFG['email']['smtp_on'])
			),
			array(
				'L_TITLE' => _SMTP_HOST,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'email[smtphost]',
				'S_VALUE' => $MAIN_CFG['email']['smtphost'],
				'S_SIZE' => 25,
				'S_MAXLENGTH' => 100
			),
			array(
				'L_TITLE' => _USE_SMTP_AUTH,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('email[smtp_auth]', $MAIN_CFG['email']['smtp_auth'])
			),
			array(
				'L_TITLE' => _SMTP_USER_NAME,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'email[smtp_uname]',
				'S_VALUE' => $MAIN_CFG['email']['smtp_uname'],
				'S_SIZE' => 25,
				'S_MAXLENGTH' => 50
			),
			array(
				'L_TITLE' => _SMTP_USER_PASS,
				'L_TOOLTIP' => '',
				'B_INPUT' => true,
				'S_TYPE' => 'text',
				'S_NAME' => 'email[smtp_pass]',
				'S_VALUE' => $MAIN_CFG['email']['smtp_pass'],
				'S_SIZE' => 25,
				'S_MAXLENGTH' => 50
			),
		);
	} elseif ($section == 8) {
		$error_level = $MAIN_CFG['debug']['error_level'];
		$avail_settings = array(
			array(
				'L_TITLE' => 'Show PHP Warnings',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('error_level[]', (($error_level & E_WARNING) ? E_WARNING : 0), array(E_WARNING=>_YES, '0'=>_NO))
			),
			array(
				'L_TITLE' => 'Show PHP Notices',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('error_level[]', (($error_level & E_NOTICE) ? E_NOTICE : 0), array(E_NOTICE=>_YES, '0'=>_NO))
			),
			array(
				'L_TITLE' => 'Show CMS Warnings',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('error_level[]', (($error_level & E_USER_WARNING) ? E_USER_WARNING : 0), array(E_USER_WARNING=>_YES, '0'=>_NO))
			),
			array(
				'L_TITLE' => 'Show CMS Notices',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('error_level[]', (($error_level & E_USER_NOTICE) ? E_USER_NOTICE : 0), array(E_USER_NOTICE=>_YES, '0'=>_NO))
			),
			array(
				'L_TITLE' => 'Show database queries',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('debug[database]', $MAIN_CFG['debug']['database'])
			),
			array(
				'L_TITLE' => "Show session data on error pages",
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('debug[session]', $MAIN_CFG['debug']['session'])
			)
		);
	} elseif ($section == 9) {
		$avail_settings = array(
			array(
				'L_TITLE' => _ACTBANNERS,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[banners]', $banners)
			),
			array(
				'L_TITLE' => _ACTIVATEHTTPREF,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('global[httpref]', $httpref)
			),
			array(
				'L_TITLE' => _MAXREF,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_option('global[httprefmax]', $httprefmax, array('100', '250', '500', '1000', '2000'))
			),
			array(
				'L_TITLE' => _ITEMSTOP,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_option('global[top]', $top, array('5', '10', '15', '20', '25', '30'))
			),
			array(
				'L_TITLE' => _GRAPHICOPT,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' =>	'
					<input type="checkbox" name="admingraphic[]" value="1" '.(($admingraphic & 1) ? 'checked="checked"' : '').' />'._GRAPHICAL.'<br />
					<input type="checkbox" name="admingraphic[]" value="2" '.(($admingraphic & 2) ? 'checked="checked"' : '').' />'._SIDEBLOCK.'<br />
					<input type="checkbox" name="admingraphic[]" value="4" '.(($admingraphic & 4) ? 'checked="checked"' : '').' />CSS Top Menu<br />'
				//<input type="checkbox" name="admingraphic[]" value="8" '.(($admingraphic & 8) ? 'checked="checked"' : '').' />DHTML Top Menu<br />
			)
		);
	} elseif ($section == 10) {
		$avail_settings = array(
			array(
				'L_TITLE' => _SHOWSEC,
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => '
					<input type="checkbox" name="code_show[]" value="1" '.(($sec_code & 1) ? 'checked="checked"' : '').' />Administrator login<br />
					<input type="checkbox" name="code_show[]" value="2" '.(($sec_code & 2) ? 'checked="checked"' : '').' />Member login<br />
					<input type="checkbox" name="code_show[]" value="4" '.(($sec_code & 4) ? 'checked="checked"' : '').' />Member registration<br />'
			),
			array(
				'L_TITLE' => 'Use background image',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('sec_code[back_img]', $MAIN_CFG['sec_code']['back_img'])
			)
		);
		if (function_exists('imagettftext')) {
			$fontlist = array();
			$handle=opendir(CORE_PATH.'fonts');
			while ($file = readdir($handle)) {
				if (preg_match('#\.ttf$#m',$file)) { $fontlist[$file] = substr($file,0 , -4); }
			}
			closedir($handle);
			natcasesort($fontlist);
			array_unshift($fontlist, '[system]');
			$avail_settings[] = array(
				'L_TITLE' => 'Font face',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_box('sec_code[font]', $MAIN_CFG['sec_code']['font'], $fontlist).' '
					.select_option('sec_code[font_size]', $MAIN_CFG['sec_code']['font_size'], array(8,10,12,14,16)).' px.'
			);
		}
		$avail_settings[] = array(
			'L_TITLE' => _PREVIEW,
			'L_TOOLTIP' => '',
			'B_INPUT' => false,
			'S_TYPE' => generate_secimg()
		);
	} elseif ($section == 11) {
		if (!is_readable(BASEDIR)) {
			trigger_error('Permission denied while reading '.BASEDIR, E_USER_ERROR);
		}
		global $adminindex, $mainindex;
		$ignore = array('banners.php', 'error.php', 'header.php', 'footer.php', 'install.php');
		$filesa = $filesi = array();
		$dir = dir(BASEDIR);
		while ($file = $dir->read()) {
			if (is_file(BASEDIR.$file) && preg_match('#\.php$#m', $file) &&!in_array($file, $ignore)) {
				if ($file != 'index.php') { $filesa[] = $file; }
				$filesi[] = $file;
			}
		}
		$dir->close();
		natcasesort($filesa);
		natcasesort($filesi);
		array_unshift($filesi, '[none]');

		$avail_settings = array(
			array(
				'L_TITLE' => 'Full debugging',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => yesno_option('config_debug', CPG_DEBUG)
			),
			array(
				'L_TITLE' => 'Index file</span><br /><i>If you change this into something else then index.php or [none]<br/>then you must modify LEO in .htaccess</i>',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_option('config_index', $mainindex, $filesi)
			),
			array(
				'L_TITLE' => 'Admin file',
				'L_TOOLTIP' => '',
				'B_INPUT' => false,
				'S_TYPE' => select_option('config_admin', $adminindex, $filesa)
			)
		);
	} else if ($section == 12) {
		$p3p_header = !empty($MAIN_CFG['header']['P3P']) ? $MAIN_CFG['header']['P3P'] : $MAIN_CFG['header']['P3P_default'];
		foreach ($P3P_CP as $policy => $data) {
			$bg = !empty($bg) ? '' : ' style="background-color: '.$bgcolor2.';"';
		//	echo '<td colspan="2"><span class="genmed" style="background-color: '.$bgcolor4."; font-weight: bold;\">$data</span></td>\n";
			//echo "<tr$bg>\n";
			if (is_int($policy)) {
				/*$pos = 0;*/
				$p3p_sect = $policy;
				$avail_settings[] = array(
					'L_TITLE' => $data,
					'L_TOOLTIP' => ' style="background-color: '.$bgcolor4.'; font-weight: bold;"',
					'B_INPUT' => false,
					'S_TYPE' => ''
				);
			} else {
					$def_option = '';
					if (preg_match('/('.$policy.'[aio|\s]{0,1})/', $p3p_header, $match)) {
						$def_option = trim($match[1]);
					}
					$options = array();
					$options[''] = 'No';
					$options[$policy] = $policy;
				if (($p3p_sect == 4 || $p3p_sect == 5)/*&& $pos > 1*/) {
					$options[$policy.'a'] = $policy.'a';
					$options[$policy.'i'] = $policy.'i';
					$options[$policy.'o'] = $policy.'o';
				}
				if (0 == $p3p_sect || 6 == $p3p_sect) {
					$checked =  (false !== strpos($p3p_header,(string) $policy)) ? ' checked="checked"' : '';  
					$avail_settings[] = array(
						'L_TITLE' => '
							<dl><dt><p'.$bg.'><b><input type="radio" name="P3P['.$p3p_sect.']" value="'.$policy.'"'.$checked.' /></b>
							'.$data.'</p>
							</dt></dl>',
						'L_TOOLTIP' => '',
						'B_INPUT' => false,
						'S_TYPE' => ''
					);
				} else {
					$avail_settings[] = array(
						'L_TITLE' => '
							<dl><dt><p'.$bg.'><b>'.select_box("P3P[$policy]", $def_option, $options).'</b>
							'.$data.'</p>
							</dt></dl>',
						'L_TOOLTIP' => '',
						'B_INPUT' => false,
						'S_TYPE' => ''
					);
				}
			}
		/*++$pos*/;
		}
		$avail_settings[] = array(
			'L_TITLE' => 'P3P CP Current &nbsp;Config: '.$MAIN_CFG['header']['P3P'],
			'L_TOOLTIP' => ' style="background-color: '.$bgcolor4.';"',
			'B_INPUT' => false,
			'S_TYPE' => ''
		);
		$avail_settings[] = array(
			'L_TITLE' => 'P3P CP System Default: '.$MAIN_CFG['header']['P3P_default'],
			'L_TOOLTIP' => ' style="background-color: '.$bgcolor4.';"',
			'B_INPUT' => false,
			'S_TYPE' => ''
		);
	}
	foreach ($avail_settings as $settings) {
		$cpgtpl->assign_block_vars('settings', $settings);
	}
	$cpgtpl->set_handle('body', 'admin/settings.html');
	$cpgtpl->display('body');
}
