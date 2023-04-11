<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2009 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }

if ($_SESSION['CPG_SESS']['admin']['page'] != 'users') {
	cpg_error(_ERROR_BAD_LINK, _SEC_ERROR);
}

$wait = $_POST['wait'];
if (!$wait || empty($_POST['waiting_users'])) {
	cpg_error(sprintf(_ERROR_NOT_SET, 'Member'), _SEC_ERROR);
}

$K = Dragonfly::getKernel();
$SQL = $K->SQL;
$CFG = $K->CFG;

function displayWaitList($title, $mode)
{
	$TPL = \Dragonfly::getKernel()->OUT;
	$SQL = \Dragonfly::getKernel()->SQL;
	$members = $_POST['waiting_users'];
	$qmembers = implode(',', array_map(array($SQL,'quote'), $members));
	\Dragonfly\Page::title($title, false);
	$TPL->waiting_ids   = implode(',', $members);
	$TPL->waiting_users = $SQL->query("SELECT user_nickname AS nickname FROM {$SQL->TBL->users_request} WHERE request_type = 0 AND request_key IN ({$qmembers})");
	$TPL->display("admin/users/wait_{$mode}");
}

if ('approve' == $wait)
{
	displayWaitList(_APPROVEUSER, $wait);
}
else if ('approveConf' == $wait)
{
	$qmembers = implode(',', array_map(array($SQL,'quote'), explode(',', $_POST['waiting_users'])));
	$fields = array();
	$result = $SQL->query("SELECT field FROM {$SQL->TBL->users_fields} WHERE visible > 0");
	while ($row = $result->fetch_row()) {
		$fields[] = $row[0];
	}

	$result = $SQL->query("SELECT
		request_key,
		user_nickname,
		user_password,
		user_email,
		user_details
	FROM {$SQL->TBL->users_request}
	WHERE request_type = 0 AND request_key IN ({$qmembers})");
	while ($row = $result->fetch_assoc()) {
		$data = array(
			'username' => $row['user_nickname'],
			'user_email' => mb_strtolower($row['user_email']),
			'user_avatar' => $CFG->avatar->default,
			'user_regdate' => time(),
			'user_lastvisit' => time(),
		);
		$row['user_details'] = json_decode($row['user_details'], true);
		foreach ($fields as $field) {
			if (isset($row['user_details'][$field])) {
				$data[$field] = $row['user_details'][$field];
			}
		}
		$data['user_level'] = 1;
		$data['user_nickname_lc'] = mb_strtolower($data['username']);
		$identity_id = $SQL->TBL->users->insert($data, 'user_id');

		$c = new \Poodle\Auth\Credentials($identity_id, $row['user_nickname'], $row['user_password']);
		$c->hash_password = false;
		\Poodle\Auth::update(1, $c);

		\Poodle\Identity\Request::removeAccount($row['request_key']);
		\Dragonfly\Identity\Create::sendWelcomePM($identity_id);

		$message = _SORRYTO.' '.$CFG->global->sitename.' '._HASAPPROVE;
		\Dragonfly\Email::send($dummy, _ACCTAPPROVE, $message, $row['user_email']);
	}
	URL::redirect(URL::admin('users'));
}

else if ('deny' == $wait)
{
	displayWaitList(_DENYUSER, $wait);
}
else if ('denyConf' == $wait)
{
	$qmembers = implode(',', array_map(array($SQL,'quote'), explode(',', $_POST['waiting_users'])));
	$result = $SQL->query("SELECT user_email FROM {$SQL->TBL->users_request} WHERE request_type = 0 AND request_key IN ({$qmembers})");
	while ($newuser = $result->fetch_row()) {
		$message = isset($_POST['denyreason']) ? "\n\n"._DENYREASON."\n".$_POST['denyreason'] : '';
		\Dragonfly\Email::send($dummy, _ACCTDENY, _SORRYTO.' '.$CFG->global->sitename.' '._HASDENY.$message, $newuser[0]);
	}
	$SQL->query("DELETE FROM {$SQL->TBL->users_request} WHERE request_type = 0 AND request_key IN ({$qmembers})");
	URL::redirect(URL::admin('users'));
}

else if ('resendMail' == $wait)
{
	displayWaitList(_RESENDMAIL, 'resend');
}
else if ('resendMailConf' == $wait)
{
	$qmembers = implode(',', array_map(array($SQL,'quote'), explode(',', $_POST['waiting_users'])));
	$result = $SQL->query("SELECT request_key, user_email FROM {$SQL->TBL->users_request} WHERE request_type = 0 AND request_key IN ({$qmembers})");
	while ($row = $result->fetch_assoc()) {
		$finishlink = URL::index("Your_Account&file=register&activate={$row['request_key']}", true, true);
		$message = _WELCOMETO.' '.$CFG->global->sitename."!\n\n"._YOUUSEDEMAIL.' '._TOREGISTER.' '.$CFG->global->sitename.".\n\n "._TOFINISHUSER."\n\n ".$finishlink;
		\Dragonfly\Email::send($dummy, _ACTIVATIONSUB, $message, $row['user_email']);
		$SQL->query("UPDATE {$SQL->TBL->users_request} SET request_time=".time()." WHERE request_type = 0 AND request_key = '{$row['request_key']}'");
	}
	URL::redirect(URL::admin('users'));
}

else if ('modify' == $wait)
{
	\Dragonfly\Page::title(_MODIFYINFO, false);
	$TPL = Dragonfly::getKernel()->OUT;

	$user_info = $SQL->uFetchAssoc("SELECT
		request_key,
		user_nickname nickname,
		user_email email,
		user_details
	FROM {$SQL->TBL->users_request} WHERE request_type = 0 AND request_key = {$SQL->quote($_POST['waiting_users'][0])}");
	if (!$user_info) {
		cpg_error(404);
	}
	$user_info = array_merge($user_info, json_decode($user_info['user_details'], true));
	$TPL->user_info = $user_info;

	// Add the additional fields to add user form if activated
	$fields = array();
	$result = $SQL->query("SELECT * FROM {$SQL->TBL->users_fields} WHERE visible > 0 ORDER BY section, fid");
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc())
		{
			if ($row['type'] == 7 && !$CFG->member->allowusertheme) continue;
			if ($row['field'] == 'name') $row['field'] = 'realname';

			$field = array(
				'label' => defined($row['langdef']) ? constant($row['langdef']) : $row['langdef'],
				'note'  => defined($row['langdef'].'MSG') ? constant($row['langdef'].'MSG') : '',
				'data'  => ''
			);

			// Text
			if ($row['type'] == 0)
			{
				$field['data'] = '<input type="text" name="'.$row['field'].'" maxlength="'.$row['size'].'" value="'.$TPL->user_info[$row['field']].'"/>';
			}
			// Boolean
			else if ($row['type'] == 1)
			{
				$sel = ($TPL->user_info[$row['field']]) ? array(' checked="checked"', '') : array('', ' checked="checked"');
				$field['data'] = '<input type="radio" name="'.$row['field'].'" value="1"'.$sel[0].' /><span>'._YES.'</span>&nbsp;&nbsp;
					<input type="radio" name="'.$row['field'].'" value="0"'.$sel[1].' /><span>'._NO.'</span>';
			}
			// Textarea
			else if ($row['type'] == 2)
			{
				$field['data'] = '<textarea name="'.$row['field'].'" style="width: 300px" rows="6" cols="30" class="post">'.$TPL->user_info[$row['field']].'</textarea>';
			}
			// Timezone
			else if ($row['type'] == 3)
			{
				$ctz = $TPL->user_info[$row['field']];
				$field['data'] = '<select name="'.$row['field'].'">';
				foreach (timezone_identifiers_list() as $tz) {
					$field['data'] .= '<option value="'.$tz.'"'.(($ctz == $tz) ? ' selected="selected"' : '').'>'.$tz.'</option>';
				}
				$field['data'] .= '</select>';
			}
			// Number
			else if ($row['type'] == 4)
			{
				$field['data'] = '<input type="number" name="'.$row['field'].'" value="'.$TPL->user_info[$row['field']].'" maxlength="'.$row['size'].'" />';
			}
			// Gender
			else if ($row['type'] == 5)
			{
				$sel = ($TPL->user_info[$row['field']] == 'm') ? array(' checked="checked"', '') : array('', ' checked="checked"');
				$field['data'] = '<input type="radio" name="'.$row['field'].'" value="m"'.$sel[0].' /><span>'._MALE.'</span>&nbsp;&nbsp;
					<input type="radio" name="'.$row['field'].'" value="f"'.$sel[1].' /><span>'._FEMALE.'</span>';
			}
			// Date
			else if ($row['type'] == 6)
			{
				$field['data'] = '<input type="date" name="'.$row['field'].'" value="'.$TPL->user_info[$row['field']].'"/>';
			}
			// Theme
			else if ($row['type'] == 7)
			{
				$field['data'] = '<select name="'.$row['field'].'">';
				foreach (\Poodle\TPL::getThemes() as $theme) {
					$field['data'] .= "<option value=\"{$theme}\" ";
					if ((($TPL->user_info['theme']=="") && ($theme==$CFG->global->Default_Theme)) || ($TPL->user_info['theme']==$theme)) {
						$field['data'] .= 'selected="selected"';
					}
					$field['data'] .= ">{$theme}</option>\n";
				}
				$field['data'] .= '</select>';
			}
			$fields[] = $field;
		}
	}
	$TPL->userinfo_fields = $fields;
	$TPL->display('admin/users/wait_modify');
}
else if ('modifyConf' == $wait)
{
	$fieldlist = array();
	$result = $SQL->query("SELECT * FROM {$SQL->TBL->users_fields} WHERE visible > 0");
	while ($row = $result->fetch_assoc()) {
		$var = ($row['field'] == 'name')?'realname':$row['field'];
		if (2 == $row['visible'] && (!isset($_POST[$var]) || !strlen($_POST[$var]))) {
			$info = $row['langdef'];
			if ($info[0] == '_' && defined($info)) $info = constant($info);
			cpg_error('Required field "'.$info.'" can\'t be empty');
		} else {
			$val = strip_tags($val);
			if (strlen($val)) {
				if ($row['type'] == 1 || $row['type'] == 4) $val = intval($val);
				else $val = substr($val,0,$row['size']);
				$fieldlist[$row['field']] = $val;
			}
		}
	}
	$fieldlist['request_time'] = time();
	$fieldlist['user_email'] = $_POST['email'];
	$SQL->TBL->users_request->update($fieldlist, "request_type = 0 AND request_key = {$SQL->quote($_POST['waiting_users'])}");
	URL::redirect(URL::admin('users'));
}
