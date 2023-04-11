<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

/* Applied rules:
 * TernaryToNullCoalescingRector
 */
 
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('settings')) { cpg_error('Access Denied'); }
Dragonfly::getKernel()->L10N->load('Your_Account');

abstract class Dragonfly_Admin_Users_Config
{

	public static function GET()
	{
		\Dragonfly\Page::title(_USERSCONFIG, false);

		$SQL = \Dragonfly::getKernel()->SQL;
		$TPL = \Dragonfly::getKernel()->OUT;

		if (isset($_GET['add_field'])) {
			$TPL->display('admin/users/add_field');
		}
		else {
			$labels = array(
				1 => _MA_PROFILE_INFO,
				2 => _MA_ADDITIONAL,
				3 => _MA_PRIVATE,
				5 => _MA_PREFERENCES,
			);
			$sid = 0;
			$sections = array();
			$result = $SQL->query("SELECT * FROM {$SQL->TBL->users_fields} ORDER BY section");
			while ($row = $result->fetch_assoc()) {
				if ($row['section'] != $sid) {
					$sid = $row['section'];
					$sections[$sid] = array(
						'label' => $labels[$sid],
						'fields' => array()
					);
				}

				$options = array(
					array('value' => 0, 'label' => _MA_HIDDEN,  'selected' => 0==$row['visible']),
					array('value' => 1, 'label' => _MA_VISIBLE, 'selected' => 1==$row['visible'])
				);
				if ($row['type'] != 1 && $row['type'] != 3) {
					$options[] = array('value' => 2, 'label' => _MA_REQUIRED, 'selected' => 2==$row['visible']);
				}

				$sections[$sid]['fields'][] = array(
					'label' => defined($row['langdef']) ? constant($row['langdef']) : $row['langdef'],
					'name' => $row['field'],
					'options' => $options,
					'del_uri' => (2==$sid||3==$sid) ? URL::admin('users_cfg&delfield='.$row['field']) : null
				);
			}

			$CFG = Dragonfly::getKernel()->CFG;
			$TPL->DeniedUserNames = explode('|',$CFG->member->DeniedUserNames);
			\Dragonfly\Output\Js::add('themes/admin/javascript/user_cfg.js');

			\Dragonfly\BBCode::pushHeaders();

			\Dragonfly\Output\Js::add('includes/poodle/javascript/wysiwyg.js');
			\Dragonfly\Output\Css::add('wysiwyg');

			$TPL->groups_upload_quota = $SQL->query("SELECT
				group_id           id,
				group_name         name,
				CASE WHEN group_upload_quota > 0 THEN group_upload_quota ELSE {$CFG->member->upload_quota} END quota
			FROM {$SQL->TBL->bbgroups}
			WHERE group_single_user=0
			ORDER BY group_name");

			$TPL->field_sections = $sections;
			$TPL->display('admin/users/config');
		}
	}

	public static function POST()
	{
		$SQL = \Dragonfly::getKernel()->SQL;

		if (isset($_GET['add_field']))
		{
			$fieldname = str_replace(' ','_', $_POST['fieldname']);
			if (!preg_match("#^[a-z][a-z0-9_]+$#",$fieldname)) {
				cpg_error("Fieldname '{$fieldname}' not allowed");
			}
			$type = '';
			$fieldtype = intval($_POST['fieldtype']);
			$fieldsize = intval($_POST['fieldsize']);
			if ($fieldtype == 1) {
				if ($fieldsize > 1 || $fieldsize < 0) { $fieldsize = 1; }
				$type .= 'TINYINT NOT NULL';
			} else if ($fieldtype == 4) {
				$type .= 'INT';
			} else if ($fieldtype == 5) {
				$type .= 'CHAR(1)';
			} else if ($fieldtype == 8) {
				$type .= 'TINYINT NOT NULL';
			} else {
				min(255,max(1,$fieldsize));
				$type .= 'VARCHAR('.$fieldsize.') NOT NULL';
			}
			$SQL->query("ALTER TABLE {$SQL->TBL->users} ADD {$fieldname} {$type}");
			$SQL->TBL->users_fields->insert(array(
				'field'   => $fieldname,
				'section' => intval($_POST['section']),
				'visible' => 1,
				'type'    => $fieldtype,
				'size'    => $fieldsize,
				'langdef' => $_POST['fieldlang'],
			));
			URL::redirect(URL::admin('users_cfg').'#fields');
		}

		else if (isset($_GET['delfield']))
		{
			$fieldname = Fix_Quotes($_GET['delfield'], 1);
			$result = $SQL->query("SELECT * FROM {$SQL->TBL->users_fields} WHERE (section=2 OR section=3) AND field='{$fieldname}'");
			if ($result->num_rows) {
				$SQL->query("DELETE FROM {$SQL->TBL->users_fields} WHERE field='{$fieldname}'");
				$SQL->query("ALTER TABLE {$SQL->TBL->users} DROP {$fieldname}");
			}
			URL::redirect(URL::admin('users_cfg').'#fields');
		}

		else
		{
			$CFG = Dragonfly::getKernel()->CFG;

			$checkboxes = array(
				'avatar' => array('allow_local','allow_remote','allow_upload','animated'),
				'member' => array(
					'user_news','allowusertheme','allowmailchange','allowuserreg',
					'useactivate','requireadmin','sendaddmail','show_registermsg','send_welcomepm',
					'private_profile')
			);
			foreach ($checkboxes as $s => $keys) {
				foreach ($keys as $k) { $CFG->set($s, $k, $_POST[$s][$k] ?? false); }
			}

			$CFG->set('member', 'minpass', max(5,$_POST->uint('member','minpass')));
			$CFG->set('member', 'registermsg', $_POST->html('member','registermsg'));
			$CFG->set('member', 'welcomepm_msg', $_POST->txt('member','welcomepm_msg'));
			$CFG->set('member', 'upload_quota', max(0,$_POST->uint('member','upload_quota')));

			$CFG->set('identity', 'passwd_minlength', $CFG->member->minpass);
			$CFG->set('identity', 'nick_minlength', max(4,$_POST->uint('identity','nick_minlength')));
			$CFG->set('identity', 'nick_invalidchars', $_POST['identity']['nick_invalidchars']);

			$CFG->set('avatar', 'filesize',     $_POST->uint('avatar','filesize'));
			$CFG->set('avatar', 'max_height',   $_POST->uint('avatar','max_height'));
			$CFG->set('avatar', 'max_width',    $_POST->uint('avatar','max_width'));
			$CFG->set('avatar', 'path',         $_POST->txt('avatar','path'));
			$CFG->set('avatar', 'gallery_path', $_POST->txt('avatar','gallery_path'));
			$CFG->set('avatar', 'default',      $_POST->txt('avatar','default'));

			$list = array();
			foreach ($_POST['DeniedUserNames'] as $word) {
				$word = mb_strtolower(trim($word));
				if ($word) { $list[$word] = $word; }
			}
			$list = array_unique($list);
			natcasesort($list);
			$CFG->set('member', 'DeniedUserNames', implode('|',$list));
//			$CFG->set('identity', 'nick_deny', $CFG->member->DeniedUserNames);

			foreach ($_POST['fields'] as $key => $val) {
				$val = intval($val);
				$SQL->query("UPDATE {$SQL->TBL->users_fields} SET visible={$val} WHERE field={$SQL->quote($key)}");
			}

			URL::redirect(URL::admin('users_cfg'));
		}
	}
}

Dragonfly_Admin_Users_Config::{$_SERVER['REQUEST_METHOD']}();
