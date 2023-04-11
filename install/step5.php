<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Setup the first administrator account
**********************************************/
if (!defined('INSTALL')) { exit; }

\Dragonfly::getKernel()->L10N->load('main');

# Pick a timezone
$tz_select = '<select name="timezone" class="formfield">';
foreach (timezone_identifiers_list() as $tz) {
	$sel = ('UTC' == $tz) ? ' selected="selected"' : '';
	$tz_select .= '<option value="'.$tz.'"'.$sel.'>'.$tz.'</option>';
}
$tz_select .= '</select>';
if (!$db->count('admins')) {
	if (!isset($_POST['pwd'])) {
		inst_header();
		echo _NOADMINYET.'<br />'.$instlang['s3_warning'].'<br /><br />'
		.'<table>'
		.'<tr><td>'._NICKNAME.'</td><td><input type="text" name="name" size="30" maxlength="25" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_nick2'].'</span></i></td></tr>'
		.'<tr><td>'._EMAIL.'</td><td><input type="text" name="email" size="30" maxlength="255" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_email2'].'</span></i></td></tr>'
		.'<tr><td>'._PASSWORD.'</td><td><input type="password" name="pwd" size="20" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_pass2'].'</span></i></td></tr>'
		.'<tr><td colspan="2"><br/><br/>'._CREATEUSERDATA.' <input type="radio" name="user_new" value="1" checked="checked" />'._YES.'&nbsp;&nbsp;<input type="radio" name="user_new" value="0" />'._NO.'<br /><br />'
		.'<tr><td>'.$instlang['s3_timezone'].'</td><td>'.$tz_select.' <i class="infobox"><span>'.$instlang['s3_timezone2'].'</span></i></td></tr>'
		.'<tr><td colspan="2"><br/><br/><input type="hidden" name="step" value="5" /><input type="submit" value="'._SUBMIT.'" class="formfield" /></td></tr>'
		.'</table>';
	} else if (\Dragonfly\Admin\Login::isValidPassword($_POST['pwd'])) {
		$cookie = unserialize(base64_decode($_COOKIE['installtest']));
		$name  = $_POST['name'];
		$email = $_POST['email'];
		$db->TBL->admins->insert(array(
			'admin_id' => 1,
			'aid'   => $name,
			'email' => $email,
			'pwd'   => \Poodle\Auth::hashPassword($_POST['pwd']),
			'radminsuper' => 1
		));
		if (!empty($_POST['user_new'])) {
			$user_id = $db->TBL->users->insert(array(
				'username'         => $_POST['name'],
				'user_nickname_lc' => mb_strtolower($_POST['name']),
				'user_email'       => $_POST['email'],
				'user_avatar'      => 'gallery/blank.png',
				'user_regdate'     => time(),
				'theme'            => '',
				'user_level'       => 2,
				'user_timezone'    => $_POST['timezone'],
			), 'user_id');
			\Poodle\Identity\Search::byID($user_id)->updateAuth(1, $_POST['name'], $_POST['pwd']);
			\Dragonfly\Identity\Cookie::set($user_id);
		}
		\Dragonfly\Admin\Cookie::set(1);
		setcookie('installtest','',-1,trim($cookie['cookiepath']),trim($cookie['cookiedom'])); //, int secure
		$images[5] = 'checked';
		inst_header();
		echo $instlang['s3_finnish'];
	} else {
		$tz_select = str_replace(
			Array('<option value="UTC" selected="selected">UTC</option>', '<option value="'.$_POST['timezone'].'">'.$_POST['timezone'].'</option>'),
			Array('<option value="UTC">UTC</option>', '<option value="'.$_POST['timezone'].'" selected="selected">'.$_POST['timezone'].'</option>'),
			$tz_select);
		$user_new_yes = $_POST['user_new'] ? ' checked="checked"' : '';
		$user_new_no = $_POST['user_new'] ? '' : ' checked="checked"';

		inst_header();
		echo '<b>'._ERROR.': '.$instlang['s3_warning'].'</b><br /><br />'
		.'<table>'
		.'<tr><td>'._NICKNAME.'</td><td><input type="text" name="name" size="30" maxlength="25" value="'.$_POST['name'].'" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_nick2'].'</span></i></td></tr>'
		.'<tr><td>'._EMAIL.'</td><td><input type="text" name="email" size="30" maxlength="255" value="'.$_POST['email'].'" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_email2'].'</span></i></td></tr>'
		.'<tr><td>'._PASSWORD.'</td><td><input type="password" name="pwd" size="20" class="formfield" /> <i class="infobox"><span>'.$instlang['s3_pass2'].'</span></i></td></tr>'
		.'<tr><td colspan="2"><br/><br/>'._CREATEUSERDATA.'  <input type="radio" name="user_new" value="1"'.$user_new_yes.' />'._YES.'&nbsp;&nbsp;<input type="radio" name="user_new" value="0"'.$user_new_no.' />'._NO.'</td></tr>'
		.'<tr><td>'.$instlang['s3_timezone'].'</td><td>'.$tz_select.' <i class="infobox"><span>'.$instlang['s3_timezone2'].'</span></i></td></tr>'
		.'<tr><td colspan="2"><br/><br/><input type="hidden" name="step" value="5" /><input type="submit" value="'._SUBMIT.'" class="formfield" /></td></tr>'
		.'</table>';
  }
}
