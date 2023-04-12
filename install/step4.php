<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/install/step4.php,v $
  $Revision: 9.7 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:43:37 $

  Setup important settings like emailaddress and cookies
**********************************************/
if (!defined('INSTALL')) { exit; }
global $db, $prefix;
unset($error);

$session = '';
$cookie_path = dirname(getenv('SCRIPT_NAME'));
$cookie_path = str_replace('\\', '/', $cookie_path); //Damn' windows
if (substr($cookie_path,-1) != '/') $cookie_path .= '/';
$domain = preg_replace('#www.#m', '', getenv('HTTP_HOST'));
$setup = array(
	'siten'		   => 'My Dragonfly Site',
	'domain'	   => getenv('HTTP_HOST'),
	'path'		   => $cookie_path,
	'adminm'	   => 'webmaster@'.$domain,
	'cookiedom'	   => $domain,
	'cookiepath'   => $cookie_path,
	'admincookie'  => $prefix.'_admin',
	'membercookie' => 'my_login',
	'cpgcookie'	   => 'cpg',
	'updatemon'	   => 1
);

function session_test($setup) {
	if (isset($setup['sessionpath'])) session_save_path($setup['sessionpath']);
	session_set_cookie_params(0, $setup['cookiepath'], $setup['cookiedom']); // [, bool secure]
	session_start();
	session_register('installtest');
}

if (isset($_POST['domain'])) {
	foreach ($setup AS $key => $value) {
		$setup[$key] = trim($_POST[$key]);
	}
	if ($setup['cookiedom'] == '127.0.0.1' || $setup['cookiedom'] == 'localhost') { $setup['cookiedom'] = NULL; }
	
	if (empty($_POST['siten'])
		|| empty($_POST['path'])
		|| empty($_POST['domain'])
		|| empty($_POST['adminm'])
		|| empty($_POST['admincookie'])
		|| empty($_POST['membercookie'])
		|| empty($_POST['cpgcookie'])) {
		$error = $instlang['s2_error_empty'];
	} elseif (!preg_match('#^[_\.\+0-9a-z-]+@(([a-z]{1,25}\.)?[0-9a-z-]{2,63}\.[a-z]{2,6}(\.[a-z]{2,6})?)$#', $setup['adminm'])) {
		$error = $instlang['s2_error_email'];
	} elseif (!preg_match('#^([a-zA-Z0-9_\\\\\-]+)$#m', $_POST['admincookie']) ||
			  !preg_match('#^([a-zA-Z0-9_\\\\\-]+)$#m', $_POST['membercookie'])) {
		$error = $instlang['s2_error_cookiename'];
	}
	if (!isset($error)) {
		# start cookie test
		$cookie = base64_encode(serialize($setup));
		setcookie('installtest',$cookie, ['expires' => 0, 'path' => $setup['cookiepath'], 'domain' => $setup['cookiedom']]); //, int secure
		session_test($setup);
		$_SESSION['installtest'] = $setup;
		inst_header();
		echo $instlang['s2_cookietest'].'<p>
		<input type="hidden" name="testcookie" value="1" /><input type="hidden" name="step" value="4" />
		<input type="submit" value="'.$instlang['s2_test_settings'].'" class="formfield" />';
		return;
	}
} elseif (isset($_POST['testcookie'])) {
	if (!isset($_COOKIE['installtest'])) {
		$error = $instlang['s2_error_cookiesettings'];
	} else {
		$setup = unserialize(base64_decode($_COOKIE['installtest']));
		session_test($setup);
		if (!isset($_SESSION['installtest']) || !is_array($_SESSION['installtest'])) {
			$error = $instlang['s2_error_sessionsettings'];
			$session = '
	  <td><b>'.$instlang['s2_session_path'].'</b></td>
	  <td><input type="text" name="sessionpath" size="30" maxlength="255" value="'.session_save_path().'" class="formfield" /> '.inst_help('sessionpath').'</td>
	</tr><tr>';
		}
	}
}

if (!isset($_POST['domain']) && isset($_COOKIE['installtest']) && isset($_POST['testcookie']) && !isset($error)) {
	$cookie = $_SESSION['installtest'];
	$path = Fix_Quotes($cookie['path']);
	$domain = Fix_Quotes($cookie['domain']);
	$siten = Fix_Quotes($cookie['siten']);
	$adminm = Fix_Quotes($cookie['adminm']);
	$cookie_dom = trim($cookie['cookiedom']);
	$updatemon = $cookie['updatemon'];
	if ($cookie_dom == '127.0.0.1' || $cookie_dom == 'localhost') { $cookie_dom = ''; }

	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$siten."' WHERE cfg_name='global' AND cfg_field='sitename'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$siten."' WHERE cfg_name='global' AND cfg_field='backend_title'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$adminm."' WHERE cfg_name='global' AND cfg_field='adminmail'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$domain."' WHERE cfg_name='server' AND cfg_field='domain'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$path."' WHERE cfg_name='server' AND cfg_field='path'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".$cookie_dom."' WHERE cfg_name='cookie' AND cfg_field='domain'");
	$db->sql_query("UPDATE ".$prefix."_config_custom SET cfg_value='".trim($cookie['cookiepath'])."' WHERE cfg_name='cookie' AND cfg_field='path'");
	$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".trim($cookie['admincookie'])."' WHERE cfg_field='admin' AND cfg_name='cookie'");
	$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".trim($cookie['membercookie'])."' WHERE cfg_field='member' AND cfg_name='cookie'");
	$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".$updatemon."' WHERE cfg_name='global' AND cfg_field='update_monitor'");

	$db->sql_query("UPDATE ".$prefix."_cpg_config SET value='".$domain.$path."' WHERE name='ecards_more_pic_target'");
	$db->sql_query("UPDATE ".$prefix."_cpg_config SET value='".$adminm."' WHERE name='gallery_admin_email'");
	$db->sql_query("UPDATE ".$prefix."_cpg_config SET value='".trim($cookie['cpgcookie'])."' WHERE name='cookie_name'");
	$db->sql_query("UPDATE ".$prefix."_cpg_config SET value='".trim($cookie['cookiepath'])."' WHERE name='cookie_path'");

	Cache::array_delete('MAIN_CFG');

	$images[2] = 'checked';
	if ($db->sql_count($prefix.'_admins') < 1) {
		inst_header();
		echo $instlang['s2_account'].'<p><input type="hidden" name="step" value="5" /><input type="submit" value="'.$instlang['s2_create'].'" class="formfield" />';
	} else {
		setcookie('installtest','', ['expires' => -1, 'path' => trim($cookie['cookiepath']), 'domain' => $cookie_dom]); //, int secure
		$_SESSION['installtest'] = null;
		$images[3] = 'checked';
		inst_header();
		echo $instlang['s1_doneup'];
	}
} else {
	inst_header();
	if (isset($error)) { echo '<h2 style="color: #FF0000;">'._ERROR.': '.$error.'</h2>'; }
	echo $instlang['s2_info'].'<script language="JavaScript" type="text/javascript">
<!--'."
maketip('domainname','".$instlang['s2_domain']."','".$instlang['s2_domain2']."');
maketip('path','".$instlang['s2_path']."','".$instlang['s2_path2']."');
maketip('adminemail','"._ADMINEMAIL."','".$instlang['s2_email2']."');
maketip('sessionpath','".$instlang['s2_session_path']."','".$instlang['s2_session_path2']."');
maketip('cookiedom','".$instlang['s2_cookie_domain']."','".$instlang['s2_cookie_domain2']."');
maketip('cookiepath','".$instlang['s2_cookie_path']."','".$instlang['s2_cookie_path2']."');
maketip('admincookie','".$instlang['s2_cookie_admin']."','".$instlang['s2_cookie_admin2']."');
maketip('membercookie','".$instlang['s2_cookie_member']."','".$instlang['s2_cookie_member2']."');
maketip('cookiecpg','".$instlang['s2_cookie_cpg']."','".$instlang['s2_cookie_cpg2']."');
maketip('updatemon','"._UM_TOGGLE."','"._UM_EXPLAIN."');
".'// -->
</script><br /><br />
	<table border="0" align="center">
	<tr>'.$session.'
	  <td>'._SITENAME.'</td>
	  <td><input type="text" name="siten" size="30" maxlength="255" value="'.$setup['siten'].'" class="formfield" /></td>
	</tr><tr>
	  <td>'.$instlang['s2_domain'].'</td>
	  <td><input type="text" name="domain" size="30" maxlength="255" value="'.$setup['domain'].'" class="formfield" /> '.inst_help('domainname').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_path'].'</td>
	  <td><input type="text" name="path" size="30" maxlength="255" value="'.$setup['path'].'" class="formfield" /> '.inst_help('path').'</td>
	</tr><tr>
	  <td>'._ADMINEMAIL.'</td>
	  <td><input type="text" name="adminm" size="30" maxlength="255" value="'.$setup['adminm'].'" class="formfield" /> '.inst_help('adminemail').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_domain'].'</td>
	  <td><input type="text" name="cookiedom" size="30" maxlength="255" value="'.$setup['cookiedom'].'" class="formfield" /> '.inst_help('cookiedom').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_path'].'</td>
	  <td><input type="text" name="cookiepath" size="30" maxlength="255" value="'.$setup['cookiepath'].'" class="formfield" /> '.inst_help('cookiepath').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_admin'].'</td>
	  <td><input type="text" name="admincookie" size="30" maxlength="25" value="'.$setup['admincookie'].'" class="formfield" /> '.inst_help('admincookie').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_member'].'</td>
	  <td><input type="text" name="membercookie" size="30" maxlength="25" value="'.$setup['membercookie'].'" class="formfield" /> '.inst_help('membercookie').'</td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_cpg'].'</td>
	  <td><input type="text" name="cpgcookie" size="30" maxlength="25" value="'.$setup['cpgcookie'].'" class="formfield" /> '.inst_help('cookiecpg').'</td>
	</tr><tr>
	  <td>'._UM_TOGGLE.'</td>
	  <td><input type="radio" name="updatemon" value="1"'.(($setup['updatemon'] == 1) ? ' checked="checked"' : '').' />'._YES.'&nbsp;&nbsp;<input type="radio" name="updatemon" value="0"'.(($setup['updatemon'] == 0) ? ' checked="checked"' : '').' />'._NO.' '.inst_help('updatemon').'</td>
	</tr><tr>
	  <td colspan="2" align="center">
		<input type="hidden" name="step" value="4" /><br />
		<input type="submit" value="'._SUBMIT.'" class="formfield" />
	</td></tr>
	</table>';
}
