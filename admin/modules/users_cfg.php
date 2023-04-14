<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/admin/modules/users_cfg.php,v $
  $Revision: 9.19 $
  $Author: nanocaiordo $
  $Date: 2007/08/27 03:08:26 $
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin()) { cpg_error('Access Denied'); }
get_lang('Your_Account', __FILE__, __LINE__);
require_once(CORE_PATH.'nbbcode.php');

$mode = $_GET['mode'] ?? '';
if (isset($_POST['addfield'])) $mode = 'addfield';
$save = (isset($_POST['save']) && $mode != 'addfield') ? $_POST['save'] : '';

if ($save == 'member') {
	foreach ($MAIN_CFG['member'] AS $key => $val) {
		if (isset($_POST[$key]))
			$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".Fix_Quotes($_POST[$key])."' WHERE cfg_field='$key' AND cfg_name='member'");
	}
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink('users_cfg'));
}
else if ($save == 'avatar') {
	foreach ($MAIN_CFG['avatar'] AS $key => $val) {
		if (isset($_POST[$key])) {
			$db->sql_query('UPDATE '.$prefix."_config_custom SET cfg_value='".Fix_Quotes($_POST[$key])."' WHERE cfg_field='$key' AND cfg_name='avatar'");
		}
	}
	Cache::array_delete('MAIN_CFG');
	url_redirect(adminlink('users_cfg&mode=avatar'));
}
else if ($save == 'add_field') {
	$fieldname = Fix_Quotes(preg_replace('# #m','_', $_POST['fieldname']), 1);
	if (!preg_match('#^[_0-9a-z\-]+$#m',$fieldname)) {
		cpg_error("Fieldname '$fieldname' not allowed");
	}
	$sql = '';
	$fieldtype = intval($_POST['fieldtype']);
	$fieldsize = intval($_POST['fieldsize']);
	$section = intval($_POST['section']);
	if ($fieldtype == 1) {
		if ($fieldsize > 1 || $fieldsize < 0) { $fieldsize = 1; }
		$sql .= 'INT1(1) NOT NULL';
	} else if ($fieldtype == 4) {
		$sql .= 'INT4('.$fieldsize.')';
	} else if ($fieldtype == 5) {
		$sql .= 'CHAR(1)';
	} else if ($fieldtype == 8) {
		$sql .= 'INT1 NOT NULL';
	} else {
		if ($fieldsize > 255 || $fieldsize < 1) {
			cpg_error("Fieldsize not allowed");
		}
		$sql .= 'VARCHAR('.$fieldsize.') NOT NULL';
	}
	$fieldlang = Fix_Quotes($_POST['fieldlang'], 1);
	$db->sql_query('ALTER TABLE '.$user_prefix."_users ADD $fieldname $sql");
	$db->sql_query('ALTER TABLE '.$user_prefix."_users_temp ADD $fieldname $sql");
	$db->sql_query('INSERT INTO '.$user_prefix."_users_fields (fid, field, section, visible, type, size, langdef) VALUES (DEFAULT, '$fieldname', '$section', 1, $fieldtype, $fieldsize, '$fieldlang')");
	url_redirect(adminlink('users_cfg&mode=fields'));
}
else if ($save == 'fieldcfg') {
	$result = $db->sql_query('SELECT field, visible FROM '.$user_prefix.'_users_fields');
	while (list($field, $visible) = $db->sql_fetchrow($result)) {
		$val = intval($_POST[$field]);
		if ($visible != $val) {
			$db->sql_query("UPDATE ".$user_prefix."_users_fields SET visible= '$val' WHERE field='$field'");
		}
	}
	url_redirect(adminlink('users_cfg&mode=fields'));
}
else if (isset($_GET['delfield'])) {
	$fieldname = Fix_Quotes($_GET['delfield'], 1);
	$result = $db->sql_query('SELECT * FROM '.$user_prefix."_users_fields WHERE (section=2 OR section=3) AND field='$fieldname'");
	if ($db->sql_numrows($result) == 1) {
		$db->sql_query("DELETE FROM ".$user_prefix."_users_fields WHERE field='$fieldname'");
		$db->sql_query("ALTER TABLE ".$user_prefix."_users DROP $fieldname");
		$db->sql_query("ALTER TABLE ".$user_prefix."_users_temp DROP $fieldname");
	}
	url_redirect(adminlink('users_cfg&mode=fields'));
}

$pagetitle .= ' '._BC_DELIM.' '._USERSCONFIG;
include('header.php');
GraphicAdmin('_AMENU2');
OpenTable();
echo (empty($mode) ? '<strong>Main</strong>' : '<a href="'.adminlink('users_cfg').'">Main</a>').' |
	'.(($mode == 'avatar') ? '<strong>Avatars</strong>' : '<a href="'.adminlink('users_cfg&amp;mode=avatar').'">Avatars</a>').' |
	'.(($mode == 'fields') ? '<strong>Fields</strong>' : '<a href="'.adminlink('users_cfg&amp;mode=fields').'">Fields</a>').'
	<hr/><div align="center">';
if ($mode == 'avatar') {
	echo '
	<table><form action="'.adminlink('users_cfg').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<tr>
		<th class="thHead" colspan="2">'._AVATAR_SETTINGS.'</th>
	</tr><tr>
		<td class="row1">'._AV_ALLOW_LOCAL.'</td>
		<td class="row2">'.yesno_option('allow_local', $MAIN_CFG['avatar']['allow_local']).'</td>
	</tr><tr>
		<td class="row1">'._AV_ALLOW_REMOTE.'<br /><span class="gensmall">'._AV_ALLOW_REMOTE_INFO.'</span></td>
		<td class="row2">'.yesno_option('allow_remote', $MAIN_CFG['avatar']['allow_remote']).'</td>
	</tr><tr>
		<td class="row1">'._AV_ALLOW_UPLOAD.'</td>
		<td class="row2">'.yesno_option('allow_upload', $MAIN_CFG['avatar']['allow_upload']).'</td>
	</tr><tr>
		<td class="row1">'._AV_ALLOW_ANIMATED.'</td>
		<td class="row2">'.yesno_option('animated', $MAIN_CFG['avatar']['animated']).'</td>
	</tr><tr>
		<td class="row1">'._AV_MAX_FILESIZE.'<br /><span class="gensmall">'._AV_MAX_FILESIZE_INFO.'</span></td>
		<td class="row2"><input class="post" type="text" size="7" maxlength="10" name="filesize" value="'.$MAIN_CFG['avatar']['filesize'].'" /> Bytes</td>
	</tr><tr>
		<td class="row1">'._AV_MAX_AVATAR_SIZE.'<br /><span class="gensmall">'._AV_MAX_AVATAR_SIZE_INFO.'</span></td>
		<td class="row2"><input class="post" type="text" size="4" maxlength="3" name="max_height" value="'.$MAIN_CFG['avatar']['max_height'].'" /> x <input class="post" type="text" size="4" maxlength="3" name="max_width" value="'.$MAIN_CFG['avatar']['max_width'].'"></td>
	</tr><tr>
		<td class="row1">'._AV_AVATAR_STORAGE_PATH.'<br /><span class="gensmall">'._AV_AVATAR_STORAGE_PATH_INFO.'</span></td>
		<td class="row2"><input class="post" type="text" size="20" maxlength="255" name="path" value="'.$MAIN_CFG['avatar']['path'].'" /></td>
	</tr><tr>
		<td class="row1">'._AV_AVATAR_GALLERY_PATH.'<br /><span class="gensmall">'._AV_AVATAR_GALLERY_PATH_INFO.'</span></td>
		<td class="row2"><input class="post" type="text" size="20" maxlength="255" name="gallery_path" value="'.$MAIN_CFG['avatar']['gallery_path'].'" /></td>
	</tr><tr>
		<td class="row1">'._AV_DEFAULT.'<br /><span class="gensmall">'._AV_DEFAULT_INFO.'</span></td>
		<td class="row2"><input class="post" type="text" size="20" maxlength="255" name="default" value="'.$MAIN_CFG['avatar']['default'].'" /></td>
	</tr>
	<tr><td align="center" colspan="2" class="catbottom">
	<input type="hidden" name="save" value="avatar" />
	<input type="submit" value="'._SAVECHANGES.'" class="mainoption" /></td></tr>
	</form></table>';
} else if ($mode == 'fields') {
	echo '
	<table border="0"><form action="'.adminlink('users_cfg').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<tr><th class="thSides" colspan="3" height="25" valign="middle">'._MA_PROFILE_INFO.'</th></tr>';
	//<tr><td class="row1"><b>Field</b></td><td class="row2"><b>On registration</b></td><td></td></tr>';
	$result = $db->sql_query("SELECT * FROM ".$user_prefix."_users_fields ORDER BY section");
	$settings = 0;
	while ($row = $db->sql_fetchrow($result)) {
		if ($row['section'] == 2 && !$settings) {
			$settings = 2;
			echo '<tr><th class="thSides" colspan="3" height="25" valign="middle">'._MA_ADDITIONAL.'</th></tr>';
		}
		if ($row['section'] == 3 && $settings != 3) {
			$settings = 3;
			echo '<tr><th class="thSides" colspan="3" height="25" valign="middle">'._MA_PRIVATE.'</th></tr>';
		}
		if ($row['section'] == 5 && $settings != 5) {
			$settings = 5;
			echo '<tr><th class="thSides" colspan="3" height="25" valign="middle">'._MA_PREFERENCES.'</th></tr>';
		}
		$info = $row['langdef'];
		if (defined($info)) $info = constant($info);
		echo '<tr><td class="row1">'.$info.'</td><td class="row2"><select name="'.$row['field'].'">';
		$sel = array('','','');
		$sel[$row['visible']] = 'selected="selected"';
		echo "<option value=\"0\" $sel[0]>"._MA_HIDDEN
			."<option value=\"1\" $sel[1]>"._MA_VISIBLE;
		if ($row['type'] != 1 && $row['type'] != 3) {
			echo "<option value=\"2\" $sel[2]>"._MA_REQUIRED;
		}
		echo '</select></td>';
		if ($row['section'] == 2 || $row['section'] == 3) {
			echo '<td class="row2"><a href="'.adminlink("users_cfg&amp;delfield=$row[field]").'">'._DELETE.'</a>';
		} else {
			echo '<td class="row2"></td>';
		}
		echo '</tr>';
	}
	echo '<tr><td class="catbottom" align="center" colspan="3">
	<input type="hidden" name="save" value="fieldcfg" />
	<input type="submit" value="'._SAVECHANGES.'" class="mainoption" /> <input type="submit" name="addfield" value="Add custom field" class="liteoption" /></td></tr>
	</form></table>';
} else if ($mode == 'addfield') {
	echo '<table>
	<tr><th class="thHead" colspan="2">Add custom userinfo field</th></tr>
	<form action="'.adminlink('users_cfg').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<tr><td class="row1">Fieldname</td><td class="row2"><input type="text" name="fieldname" size="25" maxlength="25" /> ("my_age" for example)
	<tr><td class="row1">Type</td><td class="row2"><select name="fieldtype">
	<option value="0">Text</option>
	<option value="1">'._YES.'/'._NO.'</option>
	<option value="2">Textarea</option>
	<option value="4">Number</option>
	<option value="5">Gender</option>
	'./*<option value="8">List</option>*/'
	</select></td></tr>
	<tr><td class="row1">Private</td><td class="row2">
	<select name="section">
	<option value="3">'._YES.'</option>
	<option value="2">'._NO.'</option>
	</select></td></tr>
  <tr><td class="row1">Size</td><td class="row2"><input type="text" name="fieldsize" size="10" maxlength="3" /> Between 0 and 255</td></tr>
  <tr><td class="row1">Description</td><td class="row2"><input type="text" name="fieldlang" size="50" maxlength="255" /> ("My age" for example)
  <tr><td class="catBottom" align="center" colspan="2"><input type="hidden" name="save" value="add_field" /><input type="submit" value="'._ADD.'" class="mainoption" />&nbsp;&nbsp;<input type="reset" value="'._RESET.'" class="liteoption" /></td></tr>
</form></table>';
} else {
	if ($MAIN_CFG['global']['admin_help']) {
		echo '
<script language="JavaScript" type="text/javascript">
<!--'."
maketip('my_headlines','"._MYHEADLINES."','Allow members to fetch RSS headlines from other sites, as well as choose from a pre-selected list of headline sites set by me');
maketip('user_news','"._USERSHOMENUM."','Allow members to change the number of news articles displayed on the homepage');
maketip('allowusertheme','"._ACTALLOWTHEME."','Allow members to choose a different theme');
maketip('allowmailchange','"._ACTALLOWMAIL."','Allow members to change their email address');
maketip('allowuserdelete','"._ACTALLOWDELETE."','Allow members to delete themselves');
maketip('minpass','"._PASSWDLEN."','The minimum length a password can be on new user registration');
maketip('allowuserreg','"._ACTALLOWREG."','Allow new user registrations');
maketip('useactivate','"._USEACTIVATE."','Use email activation for new user registrations, in order to prevent fraudulent registrations');
maketip('requireadmin','"._REQUIREADMIN."','Require the approval of an administrator before adding a new user to the database');
maketip('sendaddmail','"._ACTNOTIFYADD."','Send a notification email to the site owner upon new user registration');
maketip('senddeletemail','"._ACTNOTIFYDELETE."','Send a notification email to the site owner upon user self-deletion');
maketip('show_registermsg','"._USEREGISTERMSG."','Require members to accept an agreement before continuing with the registration process');
maketip('send_welcomepm','"._SENDWELCOMEPM."','Send a welcome private message to a new user upon registration');
".'// -->
</script>';
	}
	echo '
  <table align="center" border="0" width="60%"><form name="config" action="'.adminlink('users_cfg').'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
	<tr><th class="thHead" colspan="2">' ._USER_CONFIG.'</th></tr>
	<tr'.show_tooltip('my_headlines')    .'><td class="row1">'._MYHEADLINES    .'</td><td class="row2">'.yesno_option('my_headlines', $MAIN_CFG['member']['my_headlines']).'</td></tr>
	<tr'.show_tooltip('user_news')       .'><td class="row1">'._USERSHOMENUM   .'</td><td class="row2">'.yesno_option('user_news', $MAIN_CFG['member']['user_news']).'</td></tr>
	<tr'.show_tooltip('allowusertheme')  .'><td class="row1">'._ACTALLOWTHEME  .'</td><td class="row2">'.yesno_option('allowusertheme', $MAIN_CFG['member']['allowusertheme']).'</td></tr>
	<tr'.show_tooltip('allowmailchange') .'><td class="row1">'._ACTALLOWMAIL   .'</td><td class="row2">'.yesno_option('allowmailchange', $MAIN_CFG['member']['allowmailchange']).'</td></tr>';
	//<tr'.show_tooltip('allowuserdelete') .'><td class="row1">'._ACTALLOWDELETE .'</td><td class="row2">'.yesno_option('allowuserdelete', $MAIN_CFG['member']['allowuserdelete']).'</td></tr>
	//<tr'.show_tooltip('senddeletemail')  .'><td class="row1">'._ACTNOTIFYDELETE.'</td><td class="row2">'.yesno_option('senddeletemail', $MAIN_CFG['member']['senddeletemail']).'</td></tr>
	echo '
	<tr'.show_tooltip('minpass')         .'><td class="row1">'._PASSWDLEN      .'</td><td class="row2">'.select_option('minpass', $MAIN_CFG['member']['minpass'], array('3', '5', '8', '10')).'</td></tr>
	<tr'.show_tooltip('allowuserreg')    .'><td class="row1">'._ACTALLOWREG    .'</td><td class="row2">'.yesno_option('allowuserreg', $MAIN_CFG['member']['allowuserreg']).'</td></tr>
	<tr'.show_tooltip('useactivate')     .'><td class="row1">'._USEACTIVATE    .'</td><td class="row2">'.yesno_option('useactivate', $MAIN_CFG['member']['useactivate']).'</td></tr>
	<tr'.show_tooltip('requireadmin')    .'><td class="row1">'._REQUIREADMIN   .'</td><td class="row2">'.yesno_option('requireadmin', $MAIN_CFG['member']['requireadmin']).'</td></tr>
	<tr'.show_tooltip('sendaddmail')     .'><td class="row1">'._ACTNOTIFYADD   .'</td><td class="row2">'.yesno_option('sendaddmail', $MAIN_CFG['member']['sendaddmail']).'</td></tr>
	<tr'.show_tooltip('show_registermsg').'><td class="row1">'._USEREGISTERMSG .'</td><td class="row2">'.yesno_option('show_registermsg', $MAIN_CFG['member']['show_registermsg']).'</td></tr>
	<tr><td class="row1" colspan="2" valign="top">'._MA_REGISTRATION.'<br /><textarea name="registermsg" cols="63" rows="20">'.$MAIN_CFG['member']['registermsg'].'</textarea></td></tr>
	<tr><td class="spaceRow" height="5" colspan="2"><img src="images/spacer.gif" alt="" width="5" height="5" /></td></tr>
	<tr'.show_tooltip('send_welcomepm')  .'><td class="row1">'._SENDWELCOMEPM.'</td><td class="row2">'.yesno_option('send_welcomepm', $MAIN_CFG['member']['send_welcomepm']).'</td></tr>
	<tr><td class="row1" colspan="2" valign="top">'._WELCOMEPMBODY.'<br />'.bbcode_table('welcomepm_msg', 'config', 1).'<textarea name="welcomepm_msg" cols="63" rows="20">'.$MAIN_CFG['member']['welcomepm_msg'].'</textarea></td></tr>
	<tr><td align="center" colspan="2" class="catbottom"><input type="hidden" name="save" value="member" />
	<input type="submit" value="'._SAVECHANGES.'" class="mainoption" /></td></tr>
  </form></table>';
}
echo '</div>';
CloseTable();
