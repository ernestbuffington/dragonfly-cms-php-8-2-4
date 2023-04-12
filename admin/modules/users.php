<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/admin/modules/users.php,v $
  $Revision: 9.15 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:58 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('members')) { die('Access Denied'); }
get_lang('Your_Account', __FILE__, __LINE__);
$pagetitle .= ' '._BC_DELIM.' '._EDITUSERS;

if (PHPVERS >= 43) { // version_compare()
	extract($MAIN_CFG['member'], EXTR_OVERWRITE | EXTR_REFS);
} else {
	extract($MAIN_CFG['member'], EXTR_OVERWRITE);
}

require('modules/Your_Account/functions.php');

function showheader() {
	global $pagetitle;
	require('header.php');
	GraphicAdmin('_AMENU2');
	OpenTable();
	echo '<table border="0" width="100%"><tr><td valign="top">';
}
function showfooter() {
	echo '</td><td valign="top" align="right" width="150">
	<a href="'.adminlink('&amp;mode=edit').'">'._EDITUSER.'</a><br />
	<a href="'.adminlink('&amp;mode=add').'">'._ADDUSER.'</a>
	</td></tr></table>';
	CloseTable();
}

function main() {
	global $db, $user_prefix, $pagetitle, $CLASS, $bgcolor2;
	if (isset($_GET['show'])) {
		if ($_GET['show'] == 'tmpusers') { $pagetitle .= ' '._BC_DELIM.' '._WAITINGUSERS; }
		else if ($_GET['show'] == 'sususers') { $pagetitle .= ' '._BC_DELIM.' '._SUSPENDUSERS; }
		else if ($_GET['show'] == 'delusers') { $pagetitle .= ' '._BC_DELIM.' '._DELETEUSERS; }
	}
	list($tmprows) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix.'_users_temp', SQL_NUM);
	list($usrrows) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix.'_users WHERE user_level>0 AND user_id>1', SQL_NUM);
	list($susrows) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix.'_users WHERE user_level=0 AND user_id>1', SQL_NUM);
	list($delrows) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$user_prefix.'_users WHERE user_level<0 AND user_id>1', SQL_NUM);
	showheader();
	echo '<table>
	<tr><td>'.(($tmprows>0) ? '<a href="'.adminlink('&amp;show=tmpusers').'">'._WAITINGUSERS.'</a>' : _WAITINGUSERS).'</td><td>: '.$tmprows.'</td></tr>
	<tr><td>'._ACTIVEUSERS.'</td><td>: '.$usrrows.'</td></tr>';
	if ($susrows > 0) echo '<tr><td><a href="'.adminlink('&amp;show=sususers').'">'._SUSPENDUSERS.'</a></td><td>: '.$susrows.'</td></tr>';
	if ($delrows > 0) echo '<tr><td><a href="'.adminlink('&amp;show=delusers').'">'._DELETEUSERS.'</a></td><td>: '.$delrows.'</td></tr>';
	echo '</table><br />';
	if (isset($_GET['show']) && !($CLASS['member']->demo)) {
	if ($_GET['show'] == 'tmpusers') {
		/* Begin List Waiting Users */
		echo open_form(adminlink(),'waitingusers', _WAITINGUSERS, ' class="title"');
		echo '<table width="100%">
		<tr bgcolor="'.$bgcolor2.'"><td width="20"></td><td>'._NICKNAME.'</td><td>'._EMAIL.'</td></tr>';
		$result = $db->sql_query("SELECT user_id, username, user_email FROM ".$user_prefix."_users_temp ORDER BY username");
		while ($row = $db->sql_fetchrow($result)) {
			echo '<tr><td><input name="members[]" type="checkbox" value="'.$row['user_id'].'" /></td><td>'.$row['username'].'</td><td>'.$row['user_email'].'</td></tr>';
		}
		echo '<tr><td colspan="4"><select name="wait">
		<option value="approve">'._APPROVE.'</option>
		<option value="deny">'._DENY.'</option>
		<option value="resendMail">'._RESEND.'</option>
		<option value="modify">'._MODIFYINFO.'</option>
		</select>
		<input type="submit" value="'._OK.'" /></td></tr>';
		/* End List Waiting Users */
	} else if ($_GET['show'] == 'sususers') {
		/* Begin List Suspended Users */
		echo open_form(adminlink(), 'suspendedusers', _SUSPENDUSERS, ' class="title"');
		echo'<table width="100%">
		<tr bgcolor="'.$bgcolor2.'"><td width="20">'._RESTORE.'</td><td>'._NICKNAME.'</td><td>'._EMAIL.'</td><td>'._SUSPENDREASON.'</td></tr>';
		$result = $db->sql_query("SELECT user_id, username, user_email, susdel_reason FROM ".$user_prefix."_users WHERE user_level=0 AND user_id>1 ORDER BY username");
		while ($row = $db->sql_fetchrow($result)) {
			echo '<tr><td><input name="members[]" type="checkbox" value="'.$row['user_id'].'" /></td><td><a href="'.adminlink("&amp;mode=edit&amp;edit=profile&amp;id=$row[user_id]").'">'.$row['username'].'</a></td><td>'.$row['user_email'].'</td><td>'.$row['susdel_reason'].'</td></tr>';
		}
		echo '<tr><td colspan="4"><input type="hidden" name="susdel" value="restoreUser" /><input type="submit" value="'._RESTORE.'" /></td></tr>';
		/* End List Suspended Users */
	} else if ($_GET['show'] == 'delusers') {
		/* Begin List Deleted Users */
		echo open_form(adminlink(), 'deletedusers' ,_DELETEUSERS, ' class="title"');
		echo '<tr bgcolor="'.$bgcolor2.'"><td width="20"></td><td>'._NICKNAME.'</td><td>'._EMAIL.'</td><td>'._DELETEREASON.'</td></tr>';
		$result = $db->sql_query("SELECT user_id, username, user_email, susdel_reason FROM ".$user_prefix."_users WHERE user_level<0 AND user_id>1 ORDER BY username");
		while ($row = $db->sql_fetchrow($result)) {
			echo '<tr><td><input name="members[]" type="checkbox" value="'.$row['user_id'].'" /></td><td><a href="'.adminlink("&amp;mode=edit&amp;edit=profile&amp;id=$row[user_id]").'">'.$row['username'].'</a></td><td>'.$row['user_email'].'</td><td>'.$row['susdel_reason'].'</td></tr>';
		}
		echo '<tr><td colspan="4"><select name="susdel">
		<option value="removeUser">'._REMOVE.'</option>
		</select>
		<input type="submit" value="'._OK.'" /></td></tr>';
		/* End List Deleted Users */
	}
	echo '</table></form></fieldset>';
	}
	showfooter();
}

if ($CLASS['member']->demo) {
	main();
}

if (isset($_POST['wait'])) {
	include('admin/modules/users_wait.inc');
} else if (isset($_POST['susdel'])) {
	include('admin/modules/users_susdel.inc');
} else if (isset($_POST['avatargallery']) || isset($_GET['avatargallery'])) {
	$pagetitle .= ' '._BC_DELIM.' '._EDITUSER;
	showheader();
	require('modules/Your_Account/avatars.php');
	if(!($memberinfo = getusrdata($_GET['id']))) {
		echo _NOINFOFOR.' <strong>'.$_GET['id'].'</strong>';
	} else {
		display_avatar_gallery($memberinfo);
	}
	showfooter();
} else if (isset($_POST['save'])) {
	if ($CPG_SESS['admin']['page'] != 'users') {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}
	if(!($memberinfo = getusrdata($_POST['id']))) {
		echo _NOINFOFOR.' <strong>'.$username.'</strong>';
		showfooter();
	} else {
		$module_name = 'Your_Account';
		require('modules/Your_Account/edit_profile.php');
		saveuser($memberinfo);
	}
} else if (isset($_GET['mode'])) {
  if ($_GET['mode'] == 'edit') {
	$pagetitle .= ' '._BC_DELIM.' '._EDITUSER;
	showheader();
	if (isset($_GET['edit'])) {
		if(!($memberinfo = getusrdata($_GET['id']))) {
			echo _NOINFOFOR.' <strong>'.$_GET['id'].'</strong>';
		} else {
			require('modules/Your_Account/edit_profile.php');
			edituser($memberinfo);
		}
	} else {
	echo open_form(adminlink(),'post', 0,' style="border:none"');
	echo '
	<table cellspacing="1" cellpadding="4" border="0" align="center" class="forumline">
	<tr><th class="thHead" align="center">'._SELECTAUSER.'</th></tr>
	<tr><td class="row1" align="center"><input type="text" class="post" name="username" maxlength="50" size="20" />
	<input type="hidden" name="mode" value="edit" />
	<input type="submit" name="submituser" value="Look up user" class="mainoption" />
	<input type="submit" name="usersubmit" value="Find a username" class="liteoption" onclick="window.open(\''.getlink('Forums&amp;file=search&amp;mode=searchuser&popup=1&menu=1').'\', \'_phpbbsearch\', \'height=150,resizable=yes,width=300\');return false;" /></td></tr>
</table></form></fieldset>';
	}
	showfooter();
  }
  else if ($_GET['mode'] == 'add') {
	$pagetitle .= ' '._BC_DELIM.' '._ADDUSER;
	showheader();
	$registerinfo['username']['text'] = _USERNAME;
	$registerinfo['username']['length'] = 25;
	$registerinfo['username']['type'] = 'text';
	$registerinfo['email']['text'] = _EMAILADDRESS;
	$registerinfo['email']['length'] = 255;
	$registerinfo['email']['type'] = 'text';
	$registerinfo['password']['text'] = _PASSWORD;
	$registerinfo['password']['length'] = 20;
	$registerinfo['password']['type'] = 'password';
	$registerinfo['password_confirm']['text'] = _CONFIRMPASSWORD;
	$registerinfo['password_confirm']['length'] = 20;
	$registerinfo['password_confirm']['type'] = 'password';
	echo open_form(adminlink(), 'adduser', 0,' style="border:none"');
	echo '<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">';
	echo '<input type="hidden" name="mode" value="addConf" />
  <tr>
	<th class="thHead" colspan="2" height="25" valign="middle">'._ADDUSER.'</th>
  </tr>';
	foreach ($registerinfo as $field => $info) {
     echo '<tr>
	<td class="row1" width="38%"><span class="gen">'.$info['text'].':</span>'.($info['msg'] ?? '').'</td>
	<td class="row2"><input type="'.$info['type'].'" class="post" style="width:200px" name="'.$field.'" size="25" maxlength="'.$info['length'].'" /></td>
  </tr>';
 }
	// Add the additional fields to form if activated
	$result = $db->sql_query("SELECT * FROM ".$user_prefix."_users_fields WHERE visible > 0 ORDER BY section");
	if ($db->sql_numrows($result) > 0) {
		$settings = 0;
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['type'] == 7 && !$allowusertheme) continue;
			if ($row['field'] == 'name') $row['field'] = 'realname';
			if ($row['section'] == 3 && !$settings) {
				$settings = 3;
				echo '<tr><th class="thSides" colspan="2" height="25" valign="middle">'._MA_PRIVATE.'</th></tr>';
			} else if ($row['section'] == 5 && $settings != 5) {
				$settings = 5;
				echo '<tr><th class="thSides" colspan="2" height="25" valign="middle">'._MA_PREFERENCES.'</th></tr>';
			}
			$info = $row['langdef'];
			if (defined($info)) $info = constant($info);
			$info .= ($row['visible'] == 2) ? ': *' : ':';
			echo '<tr>
	<td class="row1"><span class="gen">'.$info.'</span>';
			if (defined($row['langdef']."MSG") != '') echo "<br />".constant($row['langdef']."MSG");
			echo '</td>
	<td class="row2">';
			if ($row['type'] == 0) {
				echo '<input type="text" name="'.$row['field'].'" class="post" style="width: 200px"  size="25" maxlength="'.$row['size'].'" />';
			} else if ($row['type'] == 1) {
				$sel = ($row['size']>0) ? array(' checked="checked"', '') : array('', ' checked="checked"');
				echo '<input type="radio" name="'.$row['field'].'" value="1"'.$sel[0].' /><span class="gen">'._YES.'</span>&nbsp;&nbsp;
		<input type="radio" name="'.$row['field'].'" value="0"'.$sel[1].' /><span class="gen">'._NO.'</span>';
			} else if ($row['type'] == 2) {
				echo '<textarea name="'.$row['field'].'" style="width: 300px" rows="6" cols="30" class="post"></textarea>';
			} else if ($row['type'] == 3) {
				echo '<select name="'.$row['field'].'">';
				for ($i=-12; $i<13; $i++) {
					if ($i == 0) { $dummy = "GMT"; }
					else {
						if (!preg_match('#\-#m', $i)) { $i = "+$i"; }
						$dummy = "GMT $i "._HOURS;
					}
					$sel = ($userinfo['user_timezone'] == $i) ? 'selected="selected"' : '';
					echo "<option value=\"$i\" $sel>$dummy</option>";
				}
				echo '</select>';
			} else if ($row['type'] == 4) {
				echo '<input type="text" name="'.$row['field'].'" class="post" style="width: 100px" size="15" maxlength="'.$row['size'].'" />';
			} else if ($row['type'] == 5) {
				echo '<input type="radio" name="'.$row['field'].'" value="m" checked="checked" /><span class="gen">'._MALE.'</span>&nbsp;&nbsp;
		<input type="radio" name="'.$row['field'].'" value="f" /><span class="gen">'._FEMALE.'</span>';
			} else if ($row['type'] == 6) {
				echo '<input type="text" name="'.$row['field'].'" class="post" style="width: 100px" size="15" maxlength="10" /> 10/24/1980';
			} else if ($row['type'] == 7) {
				$handle=opendir('themes');
				$themelist = array();
				while ($file = readdir($handle)) {
					if ( (!preg_match('#[\.]#m',$file) && file_exists("themes/$file/theme.php")) ) {
						$themelist[] = "$file";
					}
				}
				closedir($handle);
				sort($themelist);
				echo '<select name="'.$row['field'].'">';
				for ($i=0; $i < count($themelist); $i++) {
					if($themelist[$i]!='') {
						echo "<option value=\"$themelist[$i]\" ";
						if((($userinfo['theme']=='') && ($themelist[$i]==$Default_Theme)) || ($userinfo['theme']==$themelist[$i])) echo 'selected="selected"';
						echo ">$themelist[$i]</option>\n";
					}
				}
				echo '</select>';
			} else if ($row['type'] == 8) {
				if ($row['field'] == 'user_lang') {
					echo lang_selectbox($MAIN_CFG['global']['language'], $row['field'], false);
				}
				/* possible integration of array list custom fields
				else {
					get_lang('custom');
					global $fieldlist;
					echo select_box($row['field'], $fieldlist[$row['field']]['default'], $fieldlist[$row['field']]['values']);
				}
				*/
			}
			echo '</td>
  </tr>';
		}
	}
	echo '<tr>
	<td class="catBottom" colspan="2" align="center" height="28">
	  <input type="submit" name="submit" value="'._SUBMIT.'" class="mainoption" />&nbsp;&nbsp;
	  <input type="reset" value="'._RESET.'" name="reset" class="liteoption" /></td>
  </tr>
</form></fieldset></table>';
	showfooter();
  }
  else if ($_GET['mode'] == 'promote') {
	if (can_admin()) {
		$pagetitle .= ' '._BC_DELIM.' '._PROMOTEUSER;
		showheader();
		list($uname, $email, $upass) = $db->sql_ufetchrow("SELECT username, user_email, user_password FROM ".$user_prefix."_users WHERE user_id='$chng_uid'",SQL_NUM);
	echo '<center>'._SURE2PROMOTE.' <b>'.$uname.'</b>?
	<form action="'.adminlink().'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<table border="0">
	<tr><td>'._USERNAME.':</td><td><input type="text" name="aid" size="30" maxlength="30" value="'.$uname.'" /></td></tr>
	<tr><td>'._EMAIL.':</td><td><input type="text" name="email" size="30" maxlength="60" value="'.$email.'" /></td></tr>
	<tr><td>'._PERMISSIONS.':</td><td><select name="radmin[]" size="10" multiple="multiple">';
	foreach ($CLASS['member']->admin AS $field => $val) {
		if ($field != 'radminsuper' && preg_match('#radmin#m', $field)) {
			echo "<option value=\"$field\">".substr($field,6).'</option>';
		}
	}
	echo '</select><br />
	<input type="checkbox" name="radminsuper" value="1" /> <strong>'._SUPERUSER.'</strong><br />
	<font class="tiny"><i>'._SUPERWARNING.'</i></font></td>
  </tr><tr></table><br />
	<center><input type="submit" value="'._PROMOTEUSER.'" />
  <input type="hidden" name="mode" value="promoteConf" /><input type="hidden" name="password" value="'.$upass.'" /></form></center>';
		showfooter();
	}
  }
} else if (isset($_POST['mode'])) {
  if ($_POST['mode'] == 'edit') {
	$pagetitle .= ' '._BC_DELIM.' '._EDITUSER;
	showheader();
	if(!($memberinfo = getusrdata($_POST['username']))) {
		echo _NOINFOFOR.' <strong>'.$_POST['username'].'</strong>';
	} else {
		require('modules/Your_Account/edit_profile.php');
		edituser($memberinfo);
	}
	showfooter();
  }
  else if ($_POST['mode'] == 'addConf') {
	if ($CPG_SESS['admin']['page'] != 'users') {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}
	$username = Fix_Quotes($_POST['username'],1);
	$email = Fix_Quotes($_POST['email'],1);
	$password = Fix_Quotes($_POST['password'],1);
	if ($password != Fix_Quotes($_POST['password_confirm'],1)) {
		cpg_error(_PASSDIFFERENT);
	} else if (strlen($password) < $MAIN_CFG['member']['minpass'] && $password != '') {
		cpg_error(_YOUPASSMUSTBE.' <b>'.$MAIN_CFG['member']['minpass'].'</b> '._CHARLONG);
	}
	userCheck($_POST['username'], $_POST['email']);
	$fieldlist = $valuelist = '';
	check_fields($fieldlist, $valuelist, $fields);
	$result = $db->sql_query('INSERT INTO '.$user_prefix.'_users (username, user_email, user_password, user_regdate, user_avatar'.$fieldlist.') '
		."VALUES ('$username', '$email', '".md5($password)."', '".gmtime()."', '".$MAIN_CFG['avatar']['default']."'".$valuelist.')');
	$message = _WELCOMETO." $sitename!\n\n"._YOUUSEDEMAIL." ($email) "._TOREGISTER." $sitename.\n\n "._FOLLOWINGMEM."\n"._USERNAME.": $username\n"._PASSWORD.": $password";
	send_mail($dummy, $message, 0, _ACTIVATIONSUB, $email, $username);
	url_redirect(adminlink());
  }
  else if ($_POST['mode'] == 'promoteConf') {
	if ($CPG_SESS['admin']['page'] != 'users') {
		cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
	}
	if (can_admin()) {
		list($num) = $db->sql_ufetchrow("SELECT COUNT(*) FROM ".$prefix."_admins WHERE aid='$_POST[aid]'",SQL_NUM);
		if ($num > 0) {
			cpg_error(_NAMEERROR);
		} else {
			$fields = 'aid, email, pwd';
			$values = "'$_POST[aid]', '$_POST[email]', '$_POST[password]'";
			foreach ($CLASS['member']->admin AS $field => $val) {
				if (preg_match('#radmin#m', $field)) {
					$rafields[$field] = 0;
				}
			}
			if ($_POST['radminsuper']) {
				$rafields['radminsuper'] = 1;
			} else {
				foreach($_POST['radmin'] AS $table) {
					$rafields['radmin'.$table] = 1;
				}
			}
			foreach($rafields AS $key => $val) {
				$fields .= ", $key";
				$values .= ', '.intval($val);
			}
			$db->sql_query('INSERT INTO '.$prefix."_admins ($fields) VALUES ($values)");
			$message = _SORRYTO." $sitename "._HASPROMOTE;
			$subject = _ACCTPROMOTE;
			send_mail($mailer_message, $message, 0, _ACCTPROMOTE, $add_email);
			cpg_error(_USERPROMOTED, _PROMOTEUSER, adminlink());
		}
	}
  }
} else {
	main();
}
