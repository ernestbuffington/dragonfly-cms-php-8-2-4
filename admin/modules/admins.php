<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/admins.php,v $
  $Revision: 9.14 $
  $Author: nanocaiordo $
  $Date: 2007/09/03 01:52:34 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!is_admin()) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '._AUTHORSADMIN;

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
if ($CLASS['member']->demo) {
	$op = 'admins';
	$mode = '';
}

$adminops = array();
foreach ($CLASS['member']->admin AS $field => $val) {
	if ($field != 'radminsuper' && ereg('radmin', $field)) {
		$adminops[] = substr($field,6);
	}
}
sort($adminops);

// Add new administrator
if (isset($_GET['mode']) && $_GET['mode'] == 'add') {
	if (!can_admin() || $CPG_SESS['admin']['page'] != 'admins') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	if (strlen($_POST['add_aid']) < 3 || strlen($_POST['add_pwd']) < 3 || is_email($_POST['add_email']) < 1) { cpg_error(_COMPLETEFIELDS, _CREATIONERROR); }
	if (!ereg('[0-9]', $_POST['add_pwd']) && !ereg('[a-z]', $_POST['add_pwd']) && !ereg('[A-Z]', $_POST['add_pwd'])) { cpg_error(_PASSWORD_MALFORMED, _CREATIONERROR); }
	$add_pwd = md5($_POST['add_pwd']);
	$fields = 'aid, email, pwd';
	$values = "'$_POST[add_aid]', '$_POST[add_email]', '$add_pwd'";
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_admins LIMIT 0,1');
	$rafields = array('super' => 0);
	for ($i=0; $i<count($adminops); $i++) {
		$rafields[$adminops[$i]] = 0;
	}
	if (isset($_POST['radminsuper']) && $_POST['radminsuper']) {
		$rafields['super'] = 1;
	} else foreach ($_POST['radmin'] AS $table) {
		$rafields[$table] = 1;
	}
	foreach ($rafields AS $key => $val) {
		$fields .= ", radmin$key";
		$values .= ', '.intval($val);
	}
	$db->sql_query('INSERT INTO '.$prefix."_admins ($fields) VALUES ($values)");
	url_redirect(adminlink('admins'));
}
// Delete an administrator
else if (isset($_GET['del_aid'])) {
	if (!can_admin() || $CPG_SESS['admin']['page'] != 'admins') { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	if (!is_numeric($_GET['del_aid'])) { cpg_error(sprintf(_ERROR_NOT_SET, _ADMINID), _SEC_ERROR); }
	$del_aid = intval($_GET['del_aid']);
	if ($del_aid == 1) { cpg_error(_GODNOTDEL); }
	if (isset($_POST['confirm'])) {
		$db->sql_query('DELETE FROM '.$prefix."_admins WHERE admin_id='$del_aid'");
	} else if (!isset($_POST['cancel'])) {
		$pagetitle .= ' '._BC_DELIM.' '._AUTHORDEL;
		list($author_name) = $db->sql_ufetchrow('SELECT aid FROM '.$prefix.'_admins WHERE admin_id='.$del_aid, SQL_NUM);
		cpg_delete_msg(adminlink('admins&amp;del_aid='.$del_aid), sprintf(_ERROR_DELETE_CONF, '<strong>'.$author_name.'</strong>'), $hidden='');
	}
	url_redirect(adminlink('admins'));
}
// Update administrator settings
else if (isset($_GET['update'])) {
	$adm_aid = intval($_GET['update']);
	if ((!can_admin() && $CLASS['member']->admin['admin_id'] != $adm_aid) ||
		$CPG_SESS['admin']['page'] != 'admins' || $adm_aid < 1) {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}
	$chng_email = trim($_POST['chng_email']);
	$chng_pwd  = isset($_POST['chng_pwd']) ? $_POST['chng_pwd'] : '';
	$chng_pwd2 = isset($_POST['chng_pwd2']) ? $_POST['chng_pwd2'] : '';
	$fields = "email='$chng_email'";
	if ($chng_pwd2 != '') {
		if (!ereg("[0-9]", $chng_pwd) && !ereg("[a-z]", $chng_pwd) && !ereg("[A-Z]", $chng_pwd)) { cpg_error(_PASSWORD_MALFORMED); }
		if ($chng_pwd != $chng_pwd2) { cpg_error(_PASSWDNOMATCH); }
		$fields .= ", pwd='".md5($chng_pwd)."'";
	}
	if (can_admin()) {
		$rafields = array('super' => 0);
		for ($i=0; $i<count($adminops); $i++) {
			$rafields[$adminops[$i]] = 0;
		}
		if ($_POST['radminsuper'] || $adm_aid == 1) {
			$rafields['super'] = 1;
		} else foreach($_POST['radmin'] AS $table) {
			$rafields[$table] = 1;
		}
		foreach($rafields AS $key => $val) {
			$fields .= ", radmin$key=$val";
		}
	}
	$db->sql_query('UPDATE '.$prefix.'_admins SET '.$fields.' WHERE admin_id='.$adm_aid);
	unset($_SESSION['CPG_ADMIN']);
	url_redirect(adminlink('admins'));
}
else if (isset($_GET['modify'])) {
	$aid = intval($_GET['modify']);
	if ($aid < 1) { cpg_error(sprintf(_ERROR_NOT_SET, _ADMINID), _SEC_ERROR); }
	if (!can_admin() && $CLASS['member']->admin['admin_id'] != $aid) { cpg_error(_ERROR_BAD_LINK, _SEC_ERROR); }
	if ($CLASS['member']->admin['admin_id'] == $aid) {
		$row = $CLASS['member']->admin;
	} else {
		$row = $db->sql_ufetchrow('SELECT * FROM '.$prefix.'_admins WHERE admin_id='.$aid, SQL_ASSOC);
	}
	ksort($row);
	$pagetitle .= ' '._BC_DELIM.' '._MODIFYINFO;
	require('header.php');
	GraphicAdmin('_AMENU2');
	OpenTable();
	echo open_form(adminlink('admins&amp;update='.$aid), false, _MODIFYINFO).'
	<label class="ulog" for="chng_email">'._NICKNAME.'</label>'.$row['aid'].'<br />
	<label class="ulog" for="chng_email">'._EMAIL.'</label>
	<input type="text" name="chng_email" id="chng_email" value="'.$row['email'].'" size="30" maxlength="60" /><br />';
	if (can_admin()) {
		echo '<label class="ulog" for="radmin[]">'._PERMISSIONS.'</label>
		<select name="radmin[]" id="radmin[]" size="10" multiple="multiple">';
		foreach ($row AS $field => $val) {
			if ($field != 'radminsuper' && ereg('radmin', $field)) {
				$sel = ($val) ? ' selected="selected"' : '';
				$field = substr($field,6);
				echo '<option value="'.$field.'"'.$sel.'>'.$field.'</option>';
			}
		}
		$sel = ($row['radminsuper']) ? ' checked="checked"' : '';
		echo '</select><br />
		<label class="ulog" for="radminsuper">'._SUPERUSER.'</label>
		<input type="checkbox" name="radminsuper" id="radminsuper" value="1"'.$sel.' title="'._SUPERWARNING.'" /><br />';
	}
	echo '
	<label class="ulog" for="chng_pwd">'._PASSWORD.'</label>
	<input type="password" name="chng_pwd" id="chng_pwd" size="20" maxlength="40" /><br />
	<label class="ulog" for="chng_pwd2">'._RETYPEPASSWD.'</label>
	<input type="password" name="chng_pwd2" id="chng_pwd2" size="20" maxlength="40" /> <font class="tiny">'._FORCHANGES.'</font><br /><br />
	<input type="submit" value="'._SAVECHANGES.'" />'.
	close_form();
	CloseTable();
}
else {
	require('header.php');
	GraphicAdmin('_AMENU2');
	OpenTable();
	echo '<center><font class="option"><strong>'._EDITADMINS.'</strong></font></center><br />
  <table border="0" width="100%">
	<tr bgcolor="'.$bgcolor2.'"><td align="center"><strong>'._NAME.'</strong></td><td align="center"><strong>'._PERMISSIONS.'</strong></td><td align="center"><strong>'._FUNCTIONS.'</strong></td></tr>';
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_admins');
	$bgcolor = $bgcolor3;
	while ($row = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$bgcolor = ($bgcolor == '') ? ' bgcolor="'.$bgcolor3.'"' : '';
		echo "<tr$bgcolor><td>$row[aid]</td><td>";
		if ($row['radminsuper']) {
			echo _SUPERUSER;
		} else {
			$radmin = array();
			for($i=0; $i<count($adminops); $i++) {
				if ($row[('radmin'.$adminops[$i])]) $radmin[] = $adminops[$i];
			}
			echo implode(', ', $radmin);
		}
		echo '</td><td>';
		if (can_admin() || $row['aid'] == is_admin()) echo '<a href="'.adminlink('&amp;modify='.$row['admin_id']).'">'._MODIFYINFO.'</a>';
		if (can_admin() && $row['aid'] != is_admin()) echo ' / <a href="'.adminlink('&amp;del_aid='.$row['admin_id']).'">'._DELAUTHOR.'</a>';
		echo '</td></tr>';
	}
	echo '</table>';
	CloseTable();
	if (can_admin()) {
		OpenTable();
		echo open_form(adminlink('admins&amp;mode=add'), false, _ADDAUTHOR).'
		<label class="ulog" for="add_aid">'._NICKNAME.'</label>
		<input type="text" name="add_aid" id="add_aid" size="31" maxlength="30" /> <font class="tiny">'._REQUIRED.'</font><br />
		<label class="ulog" for="add_email">'._EMAIL.'</label>
		<input type="text" name="add_email" id="add_email" size="31" maxlength="60" /> <font class="tiny">'._REQUIRED.'</font><br />
		<label class="ulog" for="radmin[]">'._PERMISSIONS.'</label>
		<select name="radmin[]" id="radmin[]" size="10" multiple="multiple">';
		for ($i=0; $i<count($adminops); $i++) {
			echo '<option value="'.$adminops[$i].'">'.$adminops[$i].'</option>';
		}
		echo '</select><br />
		<label class="ulog" for="radminsuper">'._SUPERUSER.'</label>
		<input type="checkbox" name="radminsuper" id="radminsuper" value="1" title="'._SUPERWARNING.'" /><br />
		<label class="ulog" for="add_pwd">'._PASSWORD.'</label>
		<input type="password" name="add_pwd" id="add_pwd" size="20" maxlength="40" /> <font class="tiny">'._REQUIRED.'</font><br /><br />
		<input type="submit" value="'._ADDAUTHOR2.'" />'.
		close_form();
		CloseTable();
	}
}