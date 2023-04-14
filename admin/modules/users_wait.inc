<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/users_wait.inc,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2007/04/23 10:33:58 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if ($CPG_SESS['admin']['page'] != 'users') {
	cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
}
$wait = $_POST['wait'];
if (empty($wait) || !isset($_POST['members'])) {
	cpg_error(sprintf(_ERROR_NOT_SET, 'Member'), _SEC_ERROR);
}

function edit_details($userinfo) {
	global $db, $user_prefix, $allowusertheme;
	$result = $db->sql_query('SELECT * FROM '.$user_prefix.'_users_fields WHERE visible > 0 ORDER BY section, fid');
	if ($db->sql_numrows($result) > 0) {
		echo '<tr><td class="row1" colspan="2">'._MA_ITEMS_REQUIRED.'</td></tr>';
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['type'] == 7 && !$allowusertheme) continue;
			$info = $row['langdef'];
			if (defined($info)) $info = constant($info);
			$info .= ($row['visible'] == 2) ? ': *' : ':';
			$align = ($row['type'] == 2) ? ' valign="top"' : '';
			echo '<tr><td class="row1"'.$align.' width="40%"><span class="gen">'.$info.'</span>';
			if (defined($row['langdef'].'MSG') != '') echo '<br />'.constant($row['langdef'].'MSG');
			echo '</td><td class="row2">';
			$field = $row['field'];
			if ($row['type'] == 0) {
				$name = ($field == 'name')?'realname':$field;
				echo '<input type="text" name="'.$name.'" value="'.$userinfo[$field].'" class="post" style="width: 200px"  size="25" maxlength="'.$row['size'].'" />';
			} else if ($row['type'] == 1) {
				$sel = ($userinfo[$field]) ? array(' checked="checked"', '') : array('', ' checked="checked"');
				echo '<input type="radio" name="'.$field.'" value="1"'.$sel[0].' /><span class="gen">'._YES.'</span>&nbsp;&nbsp;
		<input type="radio" name="'.$field.'" value="0"'.$sel[1].' /><span class="gen">'._NO.'</span>';
			} else if ($row['type'] == 2) {
				echo '<textarea name="'.$field.'" style="width: 300px" rows="6" cols="30" class="post">'.$userinfo[$field].'</textarea>';
			} else if ($row['type'] == 3) {
				echo '<select name="'.$field.'">';
				for ($i=-12; $i<13; $i++) {
					if ($i == 0) { $dummy = 'GMT'; }
					else {
						if (!ereg('-', $i)) { $i = "+$i"; }
						$dummy = "GMT $i "._HOURS;
					}
					$sel = ($userinfo[$field] == $i) ? 'selected="selected"' : '';
					echo "<option value=\"$i\" $sel>$dummy</option>";
				}
				echo '</select>';
			} else if ($row['type'] == 4) {
				echo '<input type="text" name="'.$row['field'].'" value="'.$userinfo[$field].'" class="post" style="width: 100px" size="15" maxlength="'.$row['size'].'" />';
			} else if ($row['type'] == 5) {
				$sel = ($userinfo[$field] == 'm') ? array(' checked="checked"', '') : array('', ' checked="checked"');
				echo '<input type="radio" name="'.$row['field'].'" value="m"'.$sel[0].' /><span class="gen">'._MALE.'</span>&nbsp;&nbsp;
		<input type="radio" name="'.$row['field'].'" value="f"'.$sel[1].' /><span class="gen">'._FEMALE.'</span>';
			} else if ($row['type'] == 6) {
				echo '<input type="text" name="'.$row['field'].'" value="'.date_short($userinfo[$field]).'" class="post" style="width: 100px" size="15" maxlength="10" /> 10/24/1980';
			} else if ($row['type'] == 7) {
				$handle=opendir('themes');
				$themelist = array();
				while ($file = readdir($handle)) {
					if ( (!ereg('[.]',$file) && $file != 'CVS' && file_exists("themes/$file/theme.php")) ) {
						$themelist[] = "$file";
					}
				}
				closedir($handle);
				sort($themelist);
				echo '<select name="'.$row['field'].'">';
				for ($i=0; $i < count($themelist); $i++) {
					if($themelist[$i]!='') {
						echo "<option value=\"$themelist[$i]\" ";
						if((($userinfo['theme']=="") && ($themelist[$i]=="$Default_Theme")) || ($userinfo['theme']==$themelist[$i])) echo 'selected="selected"';
						echo ">$themelist[$i]</option>\n";
					}
				}
				echo '</select>';
			}
			echo '</td></tr>';
		}
	}
}

/* Begin Waiting Users */
if ($wait == 'approve') {
	$pagetitle .= ' '._BC_DELIM.' '._APPROVEUSER;
	$members = implode(',', $_POST['members']);
	$result = $db->sql_query('SELECT username FROM '.$user_prefix."_users_temp WHERE user_id IN ($members)");
	showheader();
	echo '<center><table border="0">
	<form method="post" action="'.adminlink('users').'" enctype="multipart/form-data" accept-charset="utf-8">
	<input type="hidden" name="wait" value="approveConf" />
	<input type="hidden" name="members" value="'.$members.'" />
	<tr><td>'._SURE2APPROVE.':<br />';
	while ($row = $db->sql_fetchrow($result)) {
		echo $row['username'].'<br />';
	}
	echo '</td></tr>
	<tr><td align="center"><input type="submit" value="'._APPROVEUSER.'" /></td><tr>
	<tr><td align="center">'._GOBACK.'</td><tr>
	</form>
	</table></center>';
	showfooter();
} else if ($wait == 'approveConf') {
	$members = explode(',', $_POST['members']);
	$result = $db->sql_query('SELECT * FROM '.$user_prefix."_users_temp WHERE user_id IN ($_POST[members])");
	while ($newuser = $db->sql_fetchrow($result, SQL_ASSOC)) {
		$message = _SORRYTO." $sitename "._HASAPPROVE;
		send_mail($dummy, $message,0,_ACCTAPPROVE,$newuser['user_email']);
		$fields = '';
		$values = '';
		foreach($newuser AS $field => $value) {
			if ($field != 'user_id' && $field != 'check_num' && $field != 'time' && !is_numeric($field)) {
				$fields .= "$field,";
				$values .= "'".Fix_Quotes($value)."',";
			}
		}
		$db->sql_query("INSERT INTO ".$user_prefix."_users ($fields user_level, user_avatar) VALUES ($values 1, '')");
	}
	$db->sql_query("DELETE FROM ".$user_prefix."_users_temp WHERE user_id IN ($_POST[members])");
	url_redirect(adminlink('users'));
}
else if ($wait == 'deny') {
	$pagetitle .= ' '._BC_DELIM.' '._DENYUSER;
	$members = implode(',', $_POST['members']);
	$result = $db->sql_query('SELECT username FROM '.$user_prefix."_users_temp WHERE user_id IN ($members)");
	showheader();
	echo '<center><table border="0">
	<form method="post" action="'.adminlink('users').'" enctype="multipart/form-data" accept-charset="utf-8">
	<input type="hidden" name="wait" value="denyConf" />
	<input type="hidden" name="members" value="'.$members.'" />
	<tr><td>'._SURE2DENY.':<br />';
	while ($row = $db->sql_fetchrow($result)) {
		echo $row['username'].'<br />';
	}
	echo '</td></tr>
	<tr><td align="center">'._DENYREASON.'<br /><textarea name="denyreason" rows="5" cols="40" wrap="virtual"></textarea></td></tr>
	<tr><td align="center"><input type="submit" value="'._DENYUSER.'"></td><tr>
	<tr><td align="center">'._GOBACK.'</td><tr>
	</form></table></center>';
	showfooter();
} else if ($wait == 'denyConf') {
	$result = $db->sql_query('SELECT user_email FROM '.$user_prefix."_users_temp WHERE user_id IN ($_POST[members])");
	while ($newuser = $db->sql_fetchrow($result)) {
		$message = isset($_POST['denyreason']) ? "\n\n"._DENYREASON."\n".$_POST['denyreason'] : '';
		send_mail($dummy, _SORRYTO." $sitename "._HASDENY.$message, 0, _ACCTDENY, $newuser['user_email']);
	}
	$db->sql_query('DELETE FROM '.$user_prefix."_users_temp WHERE user_id IN ($_POST[members])");
	url_redirect(adminlink('users'));
}
else if ($wait == 'resendMail') {
	$pagetitle .= ' '._BC_DELIM.' '._RESENDMAIL;
	$members = implode(',', $_POST['members']);
	$result = $db->sql_query('SELECT username FROM '.$user_prefix."_users_temp WHERE user_id IN ($members)");
	showheader();
	echo '<center><table border="0">
	<form method="post" action="'.adminlink('users').'" enctype="multipart/form-data" accept-charset="utf-8">
	<input type="hidden" name="wait" value="resendMailConf" />
	<input type="hidden" name="members" value="'.$members.'" />
	<tr><td>'._SURE2RESEND.':<br />';
	while ($row = $db->sql_fetchrow($result)) {
		echo $row['username'].'<br />';
	}
	echo '</td></tr>
	<tr><td align="center"><input type="submit" value="'._RESENDMAIL.'"></td><tr>
	<tr><td align="center">'._GOBACK.'</td><tr>
	</form></table></center>';
	showfooter();
} else if ($wait == 'resendMailConf') {
	$result = $db->sql_query('SELECT user_id, user_email, check_num FROM '.$user_prefix."_users_temp WHERE user_id IN ($_POST[members])");
	while ($row = $db->sql_fetchrow($result)) {
		$finishlink = getlink("Your_Account&file=register&activate=$row[user_id]&check_num=$row[check_num]", true, true);
		$message = _WELCOMETO." $sitename!\n\n"._YOUUSEDEMAIL." ($row[user_email]) "._TOREGISTER." $sitename.\n\n "._TOFINISHUSER."\n\n $finishlink";
		send_mail($dummy, $message, 0, _ACTIVATIONSUB, $row['user_email']);
		$db->sql_query('UPDATE '.$user_prefix."_users_temp SET time='".gmtime()."' WHERE user_id='$row[user_id]'");
	}
	url_redirect(adminlink('users'));
}
else if ($wait == 'modify') {
	$pagetitle .= ' '._BC_DELIM.' '._MODIFYINFO;
	$member = intval($_POST['members'][0]);
	$info = $db->sql_ufetchrow('SELECT * FROM '.$user_prefix."_users_temp WHERE user_id=$member", SQL_ASSOC);
	showheader();
	echo '<center><form method="post" action="'.adminlink('users').'" enctype="multipart/form-data" accept-charset="utf-8">
  <table border="0">
	<input type="hidden" name="wait" value="modifyConf" />
	<tr><td>'._USERNAME.':</td><td>'.$info['username'].'</td></tr>
	<tr><td>'._EMAILADDRESS.':</td><td><input type="text" name="email" value="'.$info['user_email'].'" size="30" maxlength="60"></td></tr>
	<tr><th class="thSides" colspan="2" height="25" valign="middle">'._MA_PROFILE_INFO.'</th></tr>';
	edit_details($info);
	echo '
	<tr><td align="center" colspan="2"><input type="submit" value="'._SAVECHANGES.'" /></td></tr>
  </table>
  <input type="hidden" name="members" value="'.$info['user_id'].'"></form></center>';
	showfooter();
} else if ($wait == 'modifyConf') {
	$fieldlist = '';
	$result = $db->sql_query("SELECT * FROM ".$user_prefix."_users_fields WHERE visible > 0");
	while ($row = $db->sql_fetchrow($result)) {
		$var = ($row['field'] == 'name')?'realname':$row['field'];
		$info = $row['langdef'];
		if ($info[0] == '_' && defined($info)) $info = constant($info);
		if (empty($_POST[$var]) && $row['visible'] == 2) {
			cpg_error('Required field "'.$info.'" can\'t be empty');
		} else {
			$val = Fix_Quotes($_POST[$var], 1);
			if (strlen($val) > 0) {
				if ($row['type'] == 1 || $row['type'] == 4) $val = intval($val);
				else $val = substr($val,0,$row['size']);
				$fieldlist .= ", ".$row['field']."='$val'";
			}
		}
	}
	$db->sql_query('UPDATE '.$user_prefix."_users_temp SET user_email='$_POST[email]', time='".gmtime()."'$fieldlist WHERE user_id=".intval($_POST['members']));
	url_redirect(adminlink('users'));
}
/* End Waiting Users */
