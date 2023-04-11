<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('INSTALL')) { exit; }
//global $db, $prefix;

unset($error);
$session = '';
$cookie_path = dirname(getenv('SCRIPT_NAME'));
$cookie_path = str_replace('\\', '/', $cookie_path);
if (substr($cookie_path,-1) != '/') $cookie_path .= '/';
$domain = str_replace('www.', '', getenv('HTTP_HOST'));
$setup = array(
	'siten'		   => 'My Dragonfly Site',
	'domain'	   => getenv('HTTP_HOST'),
	'path'		   => $cookie_path,
	'adminm'	   => 'webmaster@'.$domain,
	'cookiedom'	   => $domain,
	'cookiepath'   => $cookie_path,
	'admincookie'  => $prefix.'_admin',
	'membercookie' => 'my_login',
	'updatemon'	   => 1
);

\Dragonfly::getKernel()->L10N->load('main');

function session_test($setup) {
	if (isset($setup['sessionpath'])) session_save_path($setup['sessionpath']);
	session_set_cookie_params(0, $setup['cookiepath'], $setup['cookiedom']); // [, bool secure]
	session_start();
	$_SESSION['installtest'] = $setup;
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
		|| empty($_POST['membercookie'])) {
		$error = $instlang['s2_error_empty'];
	} elseif (!preg_match('#^[_\.\+0-9a-z-]+@(([a-z]{1,25}\.)?[0-9a-z-]{2,63}\.[a-z]{2,6}(\.[a-z]{2,6})?)$#', $setup['adminm'])) {
		$error = $instlang['s2_error_email'];
	} elseif (!preg_match('#^([a-zA-Z0-9_\-]+)$#', $_POST['admincookie']) ||
			  !preg_match('#^([a-zA-Z0-9_\-]+)$#', $_POST['membercookie'])) {
		$error = $instlang['s2_error_cookiename'];
	}
	if (!isset($error)) {
		# start cookie test
		$cookie = base64_encode(serialize($setup));
		setcookie('installtest',$cookie,0,$setup['cookiepath'],$setup['cookiedom']); //, int secure
		session_test($setup);
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
	  <td><input type="text" name="sessionpath" size="30" maxlength="255" value="'.session_save_path().'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_session_path2'].'</span></i></td>
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
	$cookie_path = trim($cookie['cookiepath']);
	$admin_cookie = trim($cookie['admincookie']);
	$member_cookie = trim($cookie['membercookie']);
	$updatemon = $cookie['updatemon'];
	if ($cookie_dom == '127.0.0.1' || $cookie_dom == 'localhost') { $cookie_dom = ''; }

	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$siten}' WHERE cfg_name='global' AND cfg_field='sitename'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$siten}' WHERE cfg_name='global' AND cfg_field='backend_title'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$adminm}' WHERE cfg_name='global' AND cfg_field='adminmail'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$domain}' WHERE cfg_name='server' AND cfg_field='domain'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$path}' WHERE cfg_name='server' AND cfg_field='path'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$cookie_dom}' WHERE cfg_name='cookie' AND cfg_field='domain'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$cookie_path}' WHERE cfg_name='cookie' AND cfg_field='path'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$admin_cookie}' WHERE cfg_name='admin_cookie' AND cfg_field='name'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$member_cookie}' WHERE cfg_name='auth_cookie' AND cfg_field='name'");
	$db->exec("UPDATE {$prefix}_config_custom SET cfg_value='{$updatemon}' WHERE cfg_name='global' AND cfg_field='update_monitor'");

	Dragonfly::getKernel()->CACHE->clear();

	if (!$db->count('admins')) {
		inst_header();
		echo $instlang['s2_account'].'<p><input type="hidden" name="step" value="5" /><input type="submit" value="'.$instlang['s2_create'].'" class="formfield" />';
	} else {
		setcookie('installtest','',-1,trim($cookie['cookiepath']),$cookie_dom); //, int secure
		$_SESSION['installtest'] = null;
		$images[3] = 'checked';
		inst_header();
		echo $instlang['s1_doneup'];
	}
} else {
	inst_header();
	if (isset($error)) { echo '<h2 style="color: #FF0000;">'._ERROR.': '.$error.'</h2>'; }
	echo $instlang['s2_info'].'<br /><br />
	<table align="center">
	<tr>'.$session.'
	  <td>'._SITENAME.'</td>
	  <td><input type="text" name="siten" size="30" maxlength="255" value="'.$setup['siten'].'" class="formfield" /></td>
	  <td></td>
	</tr><tr>
	  <td>'.$instlang['s2_domain'].'</td>
	  <td><input type="text" name="domain" size="30" maxlength="255" value="'.$setup['domain'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_domain2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s2_path'].'</td>
	  <td><input type="text" name="path" size="30" maxlength="255" value="'.$setup['path'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_path2'].'</span></i></td>
	</tr><tr>
	  <td>'._ADMINEMAIL.'</td>
	  <td><input type="email" name="adminm" size="30" maxlength="255" value="'.$setup['adminm'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_email2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_domain'].'</td>
	  <td><input type="text" name="cookiedom" size="30" maxlength="255" value="'.$setup['cookiedom'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_cookie_domain2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_path'].'</td>
	  <td><input type="text" name="cookiepath" size="30" maxlength="255" value="'.$setup['cookiepath'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_cookie_path2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_admin'].'</td>
	  <td><input type="text" name="admincookie" size="30" maxlength="25" value="'.$setup['admincookie'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_cookie_admin2'].'</span></i></td>
	</tr><tr>
	  <td>'.$instlang['s2_cookie_member'].'</td>
	  <td><input type="text" name="membercookie" size="30" maxlength="25" value="'.$setup['membercookie'].'" class="formfield" /></td>
	  <td><i class="infobox"><span>'.$instlang['s2_cookie_member2'].'</span></i></td>
	</tr><tr>
	  <td>'._UM_TOGGLE.'</td>
	  <td><input type="radio" name="updatemon" value="1"'.(($setup['updatemon'] == 1) ? ' checked="checked"' : '').' />'._YES.'&nbsp;&nbsp;<input type="radio" name="updatemon" value="0"'.(($setup['updatemon'] == 0) ? ' checked="checked"' : '').' />'._NO.'</td>
	  <td><i class="infobox"><span>'._UM_EXPLAIN.'</span></i></td>
	</tr><tr>
	  <td colspan="2" align="center">
		<input type="hidden" name="step" value="4" /><br />
		<input type="submit" value="'._SUBMIT.'" class="formfield" />
	</td></tr>
	</table>';
}
