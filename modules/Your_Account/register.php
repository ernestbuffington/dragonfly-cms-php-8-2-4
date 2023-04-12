<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Your_Account/register.php,v $
  $Revision: 9.33 $
  $Author: djmaze $
  $Date: 2007/12/16 22:13:16 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pagetitle = _Your_AccountLANG;
require_once("modules/$module_name/functions.php");
require_once(CORE_PATH.'nbbcode.php');
//OpenTable();
if (is_user()) { cpg_error(_YOUAREREGISTERED); }

$user_cfg = $MAIN_CFG['member'];

if (!$user_cfg['allowuserreg']) { cpg_error(_ACTDISABLED); }

if (Security::check_post()) {
	if (isset($_POST['op']) && $_POST['op'] == 'finish') {
		$pagetitle = _ACCOUNTCREATED;
		require_once('header.php');
		register_finish();
	} else {
		$pagetitle = _USERFINALSTEP;
		require_once('header.php');
		register_check();
	}
} else if (isset($_GET['activate'])) {
	activate(intval($_GET['activate']), Fix_Quotes($_GET['check_num']));
} else if (!isset($_GET['agreed']) && !isset($_POST['agreed']) && $user_cfg['show_registermsg'] ) {
	$pagetitle = _MA_REGISTRATION;
	require_once('header.php');
	OpenTable();
	echo '<table width="80%" cellspacing="2" cellpadding="2" border="0" align="center">
	  <tr>
		<td><span class="genmed"><br />'.$user_cfg['registermsg'].'<br /><br />'._BOUNDREGISTRATION.'<br /><br /></span><div align="center">
		  <a href="'.getlink("&amp;file=register&amp;agreed=1").'" class="genmed">'._MA_AGREE_OVER_13.'</a><br /><br />
		  <a href="'.getlink("&amp;file=register&amp;agreed=1&amp;coppa=1").'" class="genmed">'._MA_AGREE_UNDER_13.'</a><br /><br />
		  <a href="'.$mainindex.'" class="genmed">'._MA_DO_NOT_AGREE.'</a></div><br /></td>
	  </tr>
	</table>';
	CloseTable();
} else {
	$pagetitle = _REGISTRATIONSUB;
	require_once('header.php');
	register_form();
}

// start register form
function register_form() {
	global $db, $user_prefix, $CPG_SESS, $user_cfg, $userinfo, $MAIN_CFG;
	$coppa = (empty($_GET['coppa'])) ? 0 : true;

	$registerinfo['username']['text'] = _USERNAME;
	$registerinfo['username']['length'] = 25;
	$registerinfo['username']['type'] = 'text';
	$registerinfo['email']['text'] = _EMAILADDRESS;
	$registerinfo['email']['length'] = 255;
	$registerinfo['email']['type'] = 'text';
	$registerinfo['password']['text'] = _PASSWORD;
	$registerinfo['password']['msg'] = '<br />'._BLANKFORAUTO;
	$registerinfo['password']['length'] = 20;
	$registerinfo['password']['type'] = 'password';
	$registerinfo['password_confirm']['text'] = _CONFIRMPASSWORD;
	$registerinfo['password_confirm']['length'] = 20;
	$registerinfo['password_confirm']['type'] = 'password';

	echo '<form action="'.getlink("&amp;file=register").'" method="post" enctype="multipart/form-data" accept-charset="utf-8">
<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
  <tr>
	<td class="row2" colspan="2"><span class="gensmall">'._MA_ITEMS_REQUIRED.'</span></td>
  </tr>';
	while (list($field, $info) = each($registerinfo)) {
		echo '<tr>
	<td class="row1" width="38%"><span class="gen">'.$info['text'].': *</span>'.(isset($info['msg']) ? $info['msg'] : '').'</td>
	<td class="row2"><input type="'.$info['type'].'" class="post" style="width:200px" name="'.$field.'" size="25" maxlength="'.$info['length'].'" /></td>
  </tr>';
	}
	// Add the additional fields to form if activated
	$result = $db->sql_query("SELECT * FROM ".$user_prefix."_users_fields WHERE visible > 0 ORDER BY section");
	if ($db->sql_numrows($result)) {
		$settings = 0;
		while ($row = $db->sql_fetchrow($result)) {
			if ($row['type'] == 7 && !$user_cfg['allowusertheme']) continue;
			if ($row['field'] == 'user_lang' && !$MAIN_CFG['global']['multilingual']) continue;
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
			if (defined($row['langdef'].'MSG') != '') echo "<br />".constant($row['langdef']."MSG");
			if ($row['field'] == 'user_timezone') {
				echo '<br /><br /><span class="gen">Daylight Saving Time</span> (<a href="http://webexhibits.org/daylightsaving/" target="_blank">'.strtolower(_TB_INFO).'</a>):';
			}
			echo '</td>
	<td class="row2">'.ma_formfield($row['type'], $row['field'], $row['size'], $userinfo).'</td>
  </tr>';
		}
	}
	echo '<tr>
	<td class="catBottom" colspan="2" align="center" height="28">
	  <input type="hidden" name="agreed" value="1" />
	  <input type="hidden" name="coppa" value="'.$coppa.'" />
	  <input type="submit" name="submit" value="'._SUBMIT.'" class="mainoption" />&nbsp;&nbsp;
	  <input type="reset" value="'._RESET.'" name="reset" class="liteoption" /></td>
  </tr>
</table>
</form>
';
} // end register form

function register_check() {
	global $db, $user_cfg, $sec_code, $MAIN_CFG;
	$username = Fix_Quotes($_POST['username'],1);
	$email = strtolower(Fix_Quotes($_POST['email'],1));
	$password = Fix_Quotes($_POST['password'],1);
	if ($password != Fix_Quotes($_POST['password_confirm'],1)) {
		cpg_error(_PASSDIFFERENT);
	} else if (strlen($password) < $MAIN_CFG['member']['minpass'] && $password != '') {
		cpg_error(_YOUPASSMUSTBE.' <b>'.$MAIN_CFG['member']['minpass'].'</b> '._CHARLONG);
	}
	$fields['username'] = $username;
	$fields['email'] = $email;
	$fields['password'] = $password;
	$fields['coppa'] = $_POST['coppa'];

	// Check the additional activated fields
	$fieldlist = $valuelist = '';
	$content = check_fields($fieldlist, $valuelist, $fields);

	userCheck($username, $email);
	echo '<form action="'.getlink('&amp;file=register').'" method="post">
<table border="0" cellpadding="3" cellspacing="1" width="100%" class="forumline">
  <tr>
	<td class="row1" align="center">
	  '.$username.', '._USERCHECKDATA.'<br /><br />
	  <table border="0" cellpadding="1" cellspacing="4">
	  <tr><td><b>'._USERNAME.':</b></td><td>'.$username.'</td></tr>
	  <tr><td><b>'._EMAILADDRESS.':</b></td><td>'.$email.'</td></tr>
	  <tr><td><b>'._PASSWORD.':</b></td><td><i>'._MA_HIDDEN.'</i></td></tr>'.$content;
	if ($sec_code & 4) {
		echo '<tr>
	<td class="row1"><span class="gen">'._SECURITYCODE.':</span></td>
	<td class="row2">'.generate_secimg().'</td></tr>
  <tr>
	<td class="row1"><span class="gen">'._TYPESECCODE.':</span></td>
	<td class="row2"><input type="text" name="gfx_check" size="7" maxlength="6" /></td>
  </tr>';
	}
	echo '</table><br />';
	if (!$user_cfg['requireadmin']) {
		echo $user_cfg['useactivate'] ? _YOUWILLRECEIVE : _YOUWILLRECEIVE2;
	} else {
		echo _WAITAPPROVAL;
	}
	$_SESSION['REGISTER'] = $fields;
	echo '<input type="hidden" name="op" value="finish" /><br /><br />
	<input type="submit" value="'._FINISH.'" /> <a href="javascript:history.go(-1);"><input type="button" value="Back" onclick="history.go(-1)" /></a>
	</td>
  </tr>
</table>
</form>';
}

function welcome_pm() {
	global $db, $MAIN_CFG, $prefix, $sitename, $userinfo, $user_prefix;
	$privmsgs_to_userid = $db->sql_nextid('user_id');
	$welcome_msg = Fix_Quotes(encode_bbcode($MAIN_CFG['member']['welcomepm_msg']));
	$welcome = Fix_Quotes(_WELCOMETO.' '.$sitename.'!');
	$sql = "INSERT INTO ".$prefix."_bbprivmsgs (privmsgs_type, privmsgs_subject, privmsgs_from_userid, privmsgs_to_userid, privmsgs_date, privmsgs_ip, privmsgs_enable_html, privmsgs_enable_bbcode, privmsgs_enable_smilies, privmsgs_attach_sig) VALUES (1, '$welcome', 2, '$privmsgs_to_userid', ".gmtime().", ".$userinfo['user_ip'].", 0, 1, 1, 0)";
	if (!$db->sql_query($sql)) {
		cpg_error('Could not insert private message sent info.');
	}
	$privmsg_text_id = $db->sql_nextid('privmsgs_id');
	$sql = "INSERT INTO ".$prefix."_bbprivmsgs_text (privmsgs_text_id, privmsgs_text) VALUES ($privmsg_text_id, '$welcome_msg')";
	if (!$db->sql_query($sql)) {
		cpg_error('Could not insert private message sent text.');
	}
	$db->sql_query("UPDATE ".$user_prefix."_users SET user_new_privmsg=1 WHERE user_id=$privmsgs_to_userid");
}

function register_finish() {
	global $db, $user_cfg, $user_prefix, $sitename, $sec_code, $CPG_SESS, $userinfo, $MAIN_CFG;
	if ($sec_code & 4) {
		if (!validate_secimg()) { cpg_error(_SECCODEINCOR); }
	}

	$fields = $_SESSION['REGISTER'];
	if (empty($fields['username'])) { cpg_error('session gone...'); }
	$random = empty($fields['password']);
	if ($random) { $fields['password'] = make_pass(8, 5); }
	$user_email = $fields['email'];
	$fieldlist = $valuelist = '';
	check_fields($fieldlist, $valuelist, $fields, false);
	$username = $fields['username'];
	$password = ($random ? "\n"._PASSWORD.': '.$fields['password'] : '');

	mt_srand ((double)microtime()*1000000);
	$check_num = mt_rand(0, 1000000);
	$check_num = md5($check_num);
	$new_password = md5($fields['password']);
	$user_regdate = gmtime();
	if ($user_cfg['useactivate'] || $user_cfg['requireadmin']) {
		$result = $db->sql_query("INSERT INTO ".$user_prefix."_users_temp (username, user_email, user_password, user_regdate, check_num, time".$fieldlist.") VALUES ('$username', '$user_email', '$new_password', '$user_regdate', '$check_num', $user_regdate $valuelist)");
	} else {
		$result = $db->sql_query("INSERT INTO ".$user_prefix."_users (username, user_email, user_password, user_regdate, user_lastvisit, user_avatar $fieldlist) VALUES ('$username', '$user_email', '$new_password', '$user_regdate', $user_regdate, '{$MAIN_CFG['avatar']['default']}' $valuelist)");
		if ($user_cfg['send_welcomepm']) { welcome_pm(); }
	}
	$uid = $db->sql_nextid('user_id');
	$finishlink = getlink("&file=register&activate=$uid&check_num=$check_num", true, true);

	$message = _WELCOMETO." $sitename!\n\n"._YOUUSEDEMAIL." ($user_email) ";
	if ($fields['coppa']) {
//		$message = $lang['COPPA'];
//		$email_template = 'coppa_welcome_inactive';
		$message .= _TOAPPLY." $sitename.\n\n"._WAITAPPROVAL."\n\n"._FOLLOWINGMEM."\n"._USERNAME.": $username$password";
		$subject = _APPLICATIONSUB;
		OpenTable();
		echo "<center><b>"._ACCOUNTRESERVED."</b><br /><br />"._YOUAREPENDING."<br /><br />"._THANKSAPPL." $sitename!</center>";
	} else if (!$user_cfg['requireadmin']) {
		$message .= _TOREGISTER." $sitename.\n\n";
		OpenTable();
		echo "<center><b>"._ACCOUNTCREATED."</b><br /><br />"._YOUAREREGISTERED."<br /><br />";
		if ($user_cfg['useactivate']) {
			echo _FINISHUSERCONF;
			$message .= _TOFINISHUSER."\n\n $finishlink\n\n"; //<- Is the activation link in email. DJMaze
			$subject = _ACTIVATIONSUB;
		} else {
			echo _FINISHUSERCONF2.'<a href="'.getlink().'">'._FINISHUSERCONF3.'</a>.';
			$subject = _REGISTRATIONSUB;
		}
		echo '<br /><br />'._THANKSUSER." $sitename!</center>";
		$message .= _FOLLOWINGMEM."\n"._USERNAME.": $username$password";
	} else {
		$message .= _TOAPPLY." $sitename.\n\n"._WAITAPPROVAL."\n\n"._FOLLOWINGMEM."\n"._USERNAME.": $username$password";
		$subject = _APPLICATIONSUB;
		OpenTable();
		echo '<center><b>'._ACCOUNTRESERVED.'</b><br /><br />'._YOUAREPENDING.'<br /><br />'._THANKSAPPL." $sitename!</center>";
	}
	$from = 'noreply@'.ereg_replace('www.', '', $MAIN_CFG['server']['domain']);
	if (!send_mail($mailer_message,$message,0,$subject,$user_email,$username,$from)) {
		echo 'Member mail: '.$mailer_message;
	}
	if ($user_cfg['sendaddmail']) {
		if ($user_cfg['requireadmin']) { $subject = "$sitename - "._MEMAPL; }
		else { $subject = "$sitename - "._MEMADD; }
		$message = "$username has been added to $sitename.\n\nUser IP: ".decode_ip($userinfo['user_ip'])."\n--------------------------------------------------------\nDo not reply to this message!!";
		if(!send_mail($mailer_message,$message,0,$subject)) {
			echo "Admin mail: ".$mailer_message;
		}
	}
	CloseTable();
	unset($_SESSION['REGISTER']);
}

function activate($uid, $check_num) {
	global $db, $user_prefix, $user_cfg, $MAIN_CFG;
	if (!$user_cfg['requireadmin']) {
		$db->sql_query('DELETE FROM '.$user_prefix.'_users_temp WHERE time < '.(gmtime()-86400));
	}
	$result = $db->sql_query('SELECT * FROM '.$user_prefix."_users_temp WHERE user_id=$uid");
	if ($db->sql_numrows($result) == 1) {
		$row = $db->sql_fetchrow($result);
		if ($check_num == $row['check_num']) {
			$fieldlist = $valuelist = '';
			$result = $db->sql_uquery('SELECT field FROM '.$user_prefix.'_users_fields WHERE visible > 0');
			while (list($field) = $db->sql_fetchrow($result)) {
				$val = Fix_Quotes($row[$field]);
				if (strlen($val) > 0) {
					$fieldlist .= ", $field";
					$valuelist .= ", '$val'";
				} else {
					$fieldlist .= ", $field";
					$valuelist .= ", ''";
				}
				if ($field == 'user_timezone') {
					$fieldlist .= ', user_dst';
					$valuelist .= ', '.$row['user_dst'];
				}
			}
			$db->sql_query('INSERT INTO '.$user_prefix."_users (username, user_email, user_password, user_avatar, user_regdate, user_lastvisit $fieldlist) VALUES ('$row[username]', '$row[user_email]', '$row[user_password]', '".$MAIN_CFG['avatar']['default']."', '$row[user_regdate]', '$row[time]' $valuelist)");
			if ($user_cfg['send_welcomepm']) {
				welcome_pm();
			}
			$db->sql_query('DELETE FROM '.$user_prefix."_users_temp WHERE user_id=$uid");
			$pagetitle = _ACTIVATIONYES;
			$msg = "<center><b>$row[username]:</b> "._ACTMSG.'</center>';
		} else {
			$pagetitle = _ACTIVATIONERROR;
			$msg = '<center>'._ACTERROR1.'</center>';
		}
	} else {
		$pagetitle = _ACTIVATIONERROR;
		$msg = '<center>'._ACTERROR2.'</center>';
	}
	require_once('header.php');
	OpenTable();
	echo $msg;;
	CloseTable();
}
