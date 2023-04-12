<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Groups/index.php,v $
  $Revision: 9.17 $
  $Author: phoenix $
  $Date: 2007/08/30 12:06:43 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
global $pagetitle;
define('IN_PHPBB', true);
$phpbb_root_path = 'modules/Forums/';
include($phpbb_root_path.'common.php');
$pagetitle .= $module_title;

function generate_user_info(&$row, $date_format, $group_mod, &$from, &$posts, &$joined, &$profile_img, &$profile, &$search_img, &$search, &$pm_img, &$pm, &$email_img, &$email, &$www_img, &$www)
{
	global $lang, $images, $board_config, $MAIN_CFG;
	static $ranksrow;
	if (!is_array($ranksrow)) {
		global $db;
		$ranksrow = $db->sql_ufetchrowset("SELECT * FROM ".RANKS_TABLE." ORDER BY rank_special, rank_min",SQL_ASSOC);
	}
	$from = (!empty($row['user_from'])) ? $row['user_from'] : '&nbsp;';
	$joined = formatDateTime($row['user_regdate'], _DATESTRING2);
	$posts = ($row['user_posts']) ? $row['user_posts'] : 0;
	$email_img = $email = '';
	for ($j = 0; $j < count($ranksrow); $j++) {
		if (($row['user_rank'] && $row['user_rank'] == $ranksrow[$j]['rank_id'] && $ranksrow[$j]['rank_special']) ||
		    (!$row['user_rank'] && $row['user_posts'] >= $ranksrow[$j]['rank_min'] && !$ranksrow[$j]['rank_special'])) {
			$email = $ranksrow[$j]['rank_title'];
			$email_img = ($ranksrow[$j]['rank_image']) ? '<img src="'.$ranksrow[$j]['rank_image'].'" alt="'.$email.'" title="'.$email.'" style="border:0;" />' : '';
		}
	}

	$temp_url = getlink("Your_Account&amp;profile=".$row['user_id']);
	$profile_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_profile'].'" alt="'.$lang['Read_profile'].'" title="'.$lang['Read_profile'].'" /></a>';
	$profile = '<a href="'.$temp_url.'">'.$lang['Read_profile'].'</a>';

	if (is_user() && is_active('Private_Messages')) {
		$temp_url = getlink("Private_Messages&amp;mode=post&amp;".POST_USERS_URL."=".$row['user_id']);
		$pm_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_pm'].'" alt="'.$lang['Send_private_message'].'" title="'.$lang['Send_private_message'].'" style="border:0;" /></a>';
		$pm = '<a href="'.$temp_url.'">'.$lang['Send_private_message'].'</a>';
	} else {
		$pm = $pm_img = '';
	}

	if ($row['user_website'] == 'http:///' || $row['user_website'] == 'http://'){
		$row['user_website'] =	'';
	}
	if ($row['user_website'] != '' && !str_starts_with($row['user_website'], 'http://')) {
		$row['user_website'] = 'http://'.$row['user_website'];
	}
	$www_img = ( $row['user_website'] ) ? '<a href="'.$row['user_website'].'" target="_userwww"><img src="'.$images['icon_www'].'" alt="'.$lang['Visit_website'].'" title="'.$lang['Visit_website'].'" style="border:0;" /></a>' : '';
	$www = ( $row['user_website'] ) ? '<a href="'.$row['user_website'].'" target="_userwww">'.$lang['Visit_website'].'</a>' : '';

	$temp_url = getlink("Forums&amp;file=search&amp;search_author=".urlencode($row['user_id'])."&amp;showresults=posts");
	$search_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_search'].'" alt="'.$lang['Search_user_posts'].'" title="'.$lang['Search_user_posts'].'" style="border:0;" /></a>';
	$search = '<a href="'.$temp_url.'">'.$lang['Search_user_posts'].'</a>';

	return;
}
//
// --------------------------
if (isset($_POST['cancel'])) {
	url_redirect(getlink());
}

//
// Start session management
//
init_userprefs($userinfo);
//
// End session management
//

if (isset($_GET['g']) || isset($_POST['g'])) {
	$group_id = (isset($_POST['g']) ? intval($_POST['g']) : intval($_GET['g']));
} else {
	$group_id = false;
}

if (isset($_POST['mode']) || isset($_GET['mode'])) {
	$mode = htmlprepare($_POST['mode'] ?? $_GET['mode']);
} else {
	$mode = '';
}

//
// Default var values
//
$is_moderator = can_admin($module_name);
$groupurl = getlink("&amp;g=$group_id", true, true);

if (isset($_POST['groupstatus']) && $group_id) {
	if (!is_user()) { url_redirect(getlink('Your_Account'), true); }
	$row = $db->sql_ufetchrow("SELECT group_moderator FROM ".GROUPS_TABLE." WHERE group_id = $group_id");
	if ($row['group_moderator'] != $userinfo['user_id'] && !$is_moderator) {
		cpg_error($lang['Not_group_moderator'].'<br /><br />'.sprintf($lang['Click_return_group'], '<a href="'.$groupurl.'">', '</a>'), GENERAL_MESSAGE, $groupurl);
	}
	$db->sql_query("UPDATE ".GROUPS_TABLE." SET group_type = ".intval($_POST['group_type'])." WHERE group_id = $group_id");
	cpg_error($lang['Group_type_updated'].'<br /><br />'.sprintf($lang['Click_return_group'], '<a href="'.$groupurl.'">', '</a>'), GENERAL_MESSAGE, $groupurl);
}
else if (isset($_POST['joingroup']) && $group_id)
{
	//
	// Join a group
	//
	if (!is_user()) {
		url_redirect(getlink('Your_Account'), true);
	}
	list($ingroup) = $db->sql_ufetchrow('SELECT COUNT(*) FROM '.$prefix.'_bbuser_group WHERE group_id='.$group_id.' AND user_id='.$userinfo['user_id']);
	if ($ingroup) {
		cpg_error($lang['Already_member_group'], GENERAL_MESSAGE, $groupurl);
	}
	$row = $db->sql_ufetchrow("SELECT group_type FROM ".GROUPS_TABLE." WHERE group_id = $group_id AND group_type < 2");
	if (!empty($row)) {
		if ($row['group_type'] > 0) { cpg_error($lang['This_closed_group'], GENERAL_MESSAGE, $groupurl); }
	} else {
		cpg_error($lang['No_groups_exist'], GENERAL_MESSAGE);
	}

	$db->sql_query('INSERT INTO '.USER_GROUP_TABLE." (group_id, user_id, user_pending) VALUES ('$group_id', ".$userinfo['user_id'].', 1)');
	$message = 'A user has requested to join a group on '.$sitename.'.
To approve or deny this request for group membership please visit the following link:

'.getlink("&g=$group_id&validate=true", true, true);
	//12/29/2004 8:30PM
	list($row) = $db->sql_ufetchrow("SELECT group_moderator FROM ".GROUPS_TABLE." WHERE group_id = $group_id");
	list($moderator_email) = $db->sql_ufetchrow("SELECT user_email FROM ".USERS_TABLE." WHERE user_id = $row");
	// 12/29/2004 8:30PM
	send_mail($dummy, $message, 0, 'A request to join your group has been made.', $moderator_email, '', $userinfo['user_email'], $userinfo['username']);
	cpg_error($lang['Group_joined'], 'Joined group', $groupurl);
}
else if ( isset($_POST['unsub']) || isset($_POST['unsubpending']) && $group_id )
{
	//
	// Unsubscribe from a group
	//
	if (!is_user()) {
		url_redirect(getlink('Your_Account'), true);
	}

	if (isset($_POST['confirm'])) {
		$db->sql_query("DELETE FROM ".USER_GROUP_TABLE." WHERE user_id=".$userinfo['user_id']." AND group_id=$group_id");
		cpg_error($lang['Unsub_success'], 'Unsubscribed', getlink());
	} else {
		$unsub_msg = ( isset($_POST['unsub']) ) ? $lang['Confirm_unsub'] : $lang['Confirm_unsub_pending'];
		$hidden_fields = '<input type="hidden" name="g" value="'.$group_id.'" /><input type="hidden" name="unsub" value="1" />';
		cpg_delete_msg(getlink(), $unsub_msg, $hidden_fields);
	}
}
else if ( $group_id )
//shows the given groups members to moderator allows moderator to add or approve members
{
	//
	// Did the group moderator get here through an email?
	// If so, check to see if they are logged in.
	//
	if (isset($_GET['validate']) && !is_user()) {
		url_redirect(getlink('Your_Account'), true);
	}

	//
	// For security, get the ID of the group moderator.
	//
	$result = $db->sql_query("SELECT group_moderator, group_type FROM ".GROUPS_TABLE." WHERE group_id = $group_id");

	if ($group_info = $db->sql_fetchrow($result)) {
		$group_moderator = $group_info['group_moderator'];

		//
		// Handle Additions, removals, approvals and denials
		//
		if (!empty($_POST['add']) || !empty($_POST['remove']) || isset($_POST['approve']) || isset($_POST['deny'])) {
			if (!is_user()) {
				url_redirect(getlink('Your_Account'), true);
			}

			if (!$is_moderator && $group_moderator != $userinfo['user_id']) {
				url_refresh(getlink());
				$message = $lang['Not_group_moderator'].'<br /><br />'.sprintf($lang['Click_return_index'], '<a href="'.getlink().'">', '</a>');
				message_die(GENERAL_MESSAGE, $message);
			}

			if (isset($_POST['add'])) {
				$username = ( isset($_POST['username']) ) ? htmlprepare($_POST['username']) : '';
				$sql = "SELECT user_id, user_email, user_lang FROM ".USERS_TABLE." WHERE username = '".Fix_Quotes($username)."'";
				$result = $db->sql_query($sql);

				if ( !($row = $db->sql_fetchrow($result)) ) {
					url_refresh(getlink("&".POST_GROUPS_URL."=$group_id"));
					$message = $lang['Could_not_add_user']."<br /><br />".sprintf($lang['Click_return_group'], "<a href=\"".getlink("&amp;".POST_GROUPS_URL."=$group_id")."\">", "</a>")."<br /><br />".sprintf($lang['Click_return_index'], "<a href=\"".getlink()."\">", "</a>");
					message_die(GENERAL_MESSAGE, $message);
				}

				if ( $row['user_id'] == ANONYMOUS ) {
					url_refresh(getlink("&".POST_GROUPS_URL."=$group_id"));
					$message = $lang['Could_not_anon_user'].'<br /><br />'.sprintf($lang['Click_return_group'], '<a href="'.getlink("&amp;".POST_GROUPS_URL."=$group_id").'">', '</a>').'<br /><br />'.sprintf($lang['Click_return_index'], '<a href="'.getlink().'">', '</a>');
					message_die(GENERAL_MESSAGE, $message);
				}

				$sql = "SELECT ug.user_id FROM ".USER_GROUP_TABLE." ug, ".USERS_TABLE." u
					WHERE u.user_id = ".$row['user_id']."
						AND ug.user_id = u.user_id
						AND ug.group_id = $group_id";
				$result = $db->sql_query($sql);

				if (!$db->sql_numrows($result)) {
					$db->sql_query("INSERT INTO ".USER_GROUP_TABLE." (user_id, group_id, user_pending) VALUES (".$row['user_id'].", $group_id, 0)");

					//
					// Get the group name
					// Email the user and tell them they're in the group
					//
					$result = $db->sql_query("SELECT group_name FROM ".GROUPS_TABLE." WHERE group_id = $group_id");

					$group_name_row = $db->sql_fetchrow($result);

					$group_name = $group_name_row['group_name'];

					require_once("includes/phpBB/emailer.php");
					$emailer = new emailer();

					$emailer->from($board_config['board_email']);
					$emailer->replyto($board_config['board_email']);

					$emailer->use_template('group_added', $row['user_lang']);
					$emailer->email_address($row['user_email']);
					$emailer->set_subject($lang['Group_added']);

					$emailer->assign_vars(array(
						'SITENAME' => $board_config['sitename'],
						'GROUP_NAME' => $group_name,
						'EMAIL_SIG' => (!empty($board_config['board_email_sig'])) ? str_replace('<br />', "\n", "-- \n".$board_config['board_email_sig']) : '',

						'U_GROUPCP' => getlink('&'.POST_GROUPS_URL."=$group_id", true, true)
						)
					);
					$emailer->send();
					$emailer->reset();
				} else {
					url_refresh(getlink("&".POST_GROUPS_URL."=$group_id"));
					$message = $lang['User_is_member_group'].'<br /><br />'.sprintf($lang['Click_return_group'], '<a href="'.getlink("&amp;".POST_GROUPS_URL."=$group_id").'">', '</a>').'<br /><br />'.sprintf($lang['Click_return_index'], '<a href="'.getlink().'">', '</a>');
					message_die(GENERAL_MESSAGE, $message);
				}
			} else {
				if ( ( ( isset($_POST['approve']) || isset($_POST['deny']) ) && isset($_POST['pending_members']) ) || ( isset($_POST['remove']) && isset($_POST['members']) ) ) {
					$members = ( isset($_POST['approve']) || isset($_POST['deny']) ) ? $_POST['pending_members'] : $_POST['members'];

					$sql_in = '';
					for($i = 0; $i < (is_countable($members) ? count($members) : 0); $i++) {
						$sql_in .= ( ( $sql_in != '' ) ? ', ' : '' ).intval($members[$i]);
					}

					if ( isset($_POST['approve']) ) {
						$sql = "UPDATE ".USER_GROUP_TABLE."
							SET user_pending = 0
							WHERE user_id IN ($sql_in)
								AND group_id = $group_id";
						$sql_select = "SELECT user_email
							FROM ". USERS_TABLE."
							WHERE user_id IN ($sql_in)";
					} else if ( isset($_POST['deny']) || isset($_POST['remove']) ) {
						$sql = "DELETE FROM ".USER_GROUP_TABLE." WHERE user_id IN ($sql_in) AND group_id = $group_id";
					}

					$db->sql_query($sql);

					//
					// Email users when they are approved
					//
					if ( isset($_POST['approve']) ) {
						$result = $db->sql_query($sql_select);

						$bcc_list = array();
						while ($row = $db->sql_fetchrow($result)) {
							$bcc_list[] = $row['user_email'];
						}

						//
						// Get the group name
						//
						$result = $db->sql_query("SELECT group_name FROM ".GROUPS_TABLE." WHERE group_id = $group_id");

						$group_name_row = $db->sql_fetchrow($result);
						$group_name = $group_name_row['group_name'];

						require_once("includes/phpBB/emailer.php");
						$emailer = new emailer();

						$emailer->from($board_config['board_email']);
						$emailer->replyto($board_config['board_email']);

						for ($i = 0; $i < count($bcc_list); $i++) {
							$emailer->bcc($bcc_list[$i]);
						}

						$emailer->use_template('group_approved');
						$emailer->set_subject($lang['Group_approved']);

						$emailer->assign_vars(array(
							'SITENAME' => $board_config['sitename'],
							'GROUP_NAME' => $group_name,
							'EMAIL_SIG' => (!empty($board_config['board_email_sig'])) ? str_replace('<br />', "\n", "-- \n".$board_config['board_email_sig']) : '',

							'U_GROUPCP' => getlink('&'.POST_GROUPS_URL."=$group_id", true, true)
							)
						);
						$emailer->send();
						$emailer->reset();
					}
				}
			}
		}
		//
		// END approve or deny
		//
	} else {
		message_die(GENERAL_MESSAGE, $lang['No_groups_exist']);
	}

	//
	// Get group details
	//
	$result = $db->sql_query("SELECT * FROM ".GROUPS_TABLE." WHERE group_id = $group_id AND group_single_user = 0");

	if (!($group_info = $db->sql_fetchrow($result))) {
		message_die(GENERAL_MESSAGE, $lang['Group_not_exist']);
	}

	//
	// Get moderator details for this group
	//
	$sql = "SELECT username, user_id, user_rank, user_posts, user_regdate, user_from, user_website, user_icq, user_aim, user_yim, user_msnm
		FROM ".USERS_TABLE."
		WHERE user_id = ".$group_info['group_moderator'];
	$result = $db->sql_query($sql);

	$group_moderator = $db->sql_fetchrow($result);

	//
	// Get user information for this group
	//
	$sql = "SELECT u.username, u.user_id, u.user_rank, u.user_posts, u.user_regdate, u.user_from, u.user_website, u.user_icq, u.user_aim, u.user_yim, u.user_msnm, ug.user_pending
		FROM ".USERS_TABLE." u, ".USER_GROUP_TABLE." ug
		WHERE ug.group_id = $group_id
			AND u.user_id = ug.user_id
			AND ug.user_pending = 0
			AND ug.user_id <> '".$group_moderator['user_id']."'
		ORDER BY u.username";
	$group_members = $db->sql_ufetchrowset($sql);
	$members_count = is_countable($group_members) ? count($group_members) : 0;
	$db->sql_freeresult($result);

	$sql = "SELECT u.username, u.user_id, u.user_rank, u.user_posts, u.user_regdate, u.user_from, u.user_website, u.user_icq, u.user_aim, u.user_yim, u.user_msnm
		FROM ".GROUPS_TABLE." g, ".USER_GROUP_TABLE." ug, ".USERS_TABLE." u
		WHERE ug.group_id = $group_id
			AND g.group_id = ug.group_id
			AND ug.user_pending = 1
			AND u.user_id = ug.user_id
		ORDER BY u.username";
	$modgroup_pending_list = $db->sql_ufetchrowset($sql);
	$modgroup_pending_count = is_countable($modgroup_pending_list) ? count($modgroup_pending_list) : 0;

	$is_group_member = 0;
	if ( $members_count ) {
		for($i = 0; $i < $members_count; $i++) {
			if ($group_members[$i]['user_id'] == $userinfo['user_id'] && is_user()) {
				$is_group_member = TRUE;
				break;
			}
		}
	}

	$is_group_pending_member = 0;
	if (!$is_group_member && $modgroup_pending_count ) {
		for($i = 0; $i < $modgroup_pending_count; $i++) {
			if ($modgroup_pending_list[$i]['user_id'] == $userinfo['user_id'] && is_user()) {
				$is_group_pending_member = TRUE;
				break;
			}
		}
	}

	$pagetitle .= ' '._BC_DELIM.' '.($group_info['group_name'] ?? $lang['Group_member_join']);
	define('HEADER_INC', TRUE);
	require_once("header.php");
	OpenTable();

	if ($userinfo['user_id'] == $group_info['group_moderator']) {
		$is_moderator = TRUE;
		$group_details =  $lang['Are_group_moderator'];
		$s_hidden_fields = '<input type="hidden" name="'.POST_GROUPS_URL.'" value="'.$group_id.'" />';
	} else if ($is_group_member || $is_group_pending_member) {
		$template->assign_block_vars('switch_unsubscribe_group_input', array());
		$group_details = ($is_group_pending_member) ? $lang['Pending_this_group'] : $lang['Member_this_group'];
		$s_hidden_fields = '<input type="hidden" name="'.POST_GROUPS_URL.'" value="'.$group_id.'" />';
	} else if ($userinfo['user_id'] < 2) {
		$group_details =  $lang['Login_to_join'];
		$s_hidden_fields = '';
	} else {
		if ($group_info['group_type'] == GROUP_OPEN) {
			$template->assign_block_vars('switch_subscribe_group_input', array());
			$group_details =  $lang['This_open_group'];
			$s_hidden_fields = '<input type="hidden" name="'.POST_GROUPS_URL.'" value="'.$group_id.'" />';
		} else if ($group_info['group_type'] == GROUP_CLOSED) {
			$group_details =  $lang['This_closed_group'];
			$s_hidden_fields = '';
		} else if ($group_info['group_type'] == GROUP_HIDDEN) {
			$group_details =  $lang['This_hidden_group'];
			$s_hidden_fields = '';
		}
	}

	//
	// Load templates
	//
	$template->set_filenames(array('info' => 'groups/info_body.html'));

	//
	// Add the moderator
	//
	generate_user_info($group_moderator, $board_config['default_dateformat'], $is_moderator, $from, $posts, $joined, $profile_img, $profile, $search_img, $search, $pm_img, $pm, $email_img, $email, $www_img, $www);

	$s_hidden_fields .= '';

	$template->assign_vars(array(
		'L_GROUP_INFORMATION' => $lang['Group_Information'],
		'L_GROUP_NAME' => $lang['Group_name'],
		'L_GROUP_DESC' => $lang['Group_description'],
		'L_GROUP_TYPE' => $lang['Group_type'],
		'L_GROUP_MEMBERSHIP' => $lang['Group_membership'],
		'L_SUBSCRIBE' => $lang['Subscribe'],
		'L_UNSUBSCRIBE' => $lang['Unsubscribe'],
		'L_JOIN_GROUP' => $lang['Join_group'],
		'L_UNSUBSCRIBE_GROUP' => $lang['Unsubscribe'],
		'L_GROUP_OPEN' => $lang['Group_open'],
		'L_GROUP_CLOSED' => $lang['Group_closed'],
		'L_GROUP_HIDDEN' => $lang['Group_hidden'],
		'L_UPDATE' => $lang['Update'],
		'L_GROUP_MODERATOR' => $lang['Group_Moderator'],
		'L_GROUP_MEMBERS' => $lang['Group_Members'],
		'L_PENDING_MEMBERS' => $lang['Pending_members'],
		'L_SELECT_SORT_METHOD' => $lang['Select_sort_method'],
		'L_PM' => _BPM,
		'L_USERNAME' => $lang['Username'],
		'L_EMAIL' => $lang['Ranks'],
		'L_RANK' => $lang['Ranks'],
		'L_POSTS' => $lang['Posts'],
		'L_WEBSITE' => $lang['Website'],
		'L_FROM' => $lang['Location'],
		'L_ORDER' => $lang['Order'],
		'L_SORT' => $lang['Sort'],
		'L_SUBMIT' => $lang['Sort'],
		'L_SELECT' => $lang['Select'],
		'L_REMOVE_SELECTED' => $lang['Remove_selected'],
		'L_ADD_MEMBER' => $lang['Add_member'],
		'L_FIND_USERNAME' => $lang['Find_username'],

		'GROUP_NAME' => $group_info['group_name'],
		'GROUP_DESC' => $group_info['group_description'],
		'GROUP_DETAILS' => $group_details,
		'MOD_ROW_COLOR' => $bgcolor2,
		'MOD_ROW_CLASS' => 'row1',
		'MOD_USERNAME' => $group_moderator['username'],
		'MOD_FROM' => $from,
		'MOD_JOINED' => $joined,
		'MOD_POSTS' => $posts,
		'MOD_PROFILE_IMG' => $profile_img,
		'MOD_PROFILE' => $profile,
		'MOD_SEARCH_IMG' => $search_img,
		'MOD_SEARCH' => $search,
		'MOD_PM_IMG' => $pm_img,
		'MOD_PM' => $pm,
		'MOD_EMAIL_IMG' => $email_img,
		'MOD_EMAIL' => $email,
		'MOD_WWW_IMG' => $www_img,
		'MOD_WWW' => $www,

		'U_MOD_VIEWPROFILE' => getlink('Your_Account&amp;profile='.$group_moderator['user_id']),
		'U_SEARCH_USER' => getlink('Forums&amp;file=search&amp;mode=searchuser&amp;popup=1', true, true),
		'S_PENDING_USERS' => false,
		'S_GROUP_OPEN_TYPE' => GROUP_OPEN,
		'S_GROUP_CLOSED_TYPE' => GROUP_CLOSED,
		'S_GROUP_HIDDEN_TYPE' => GROUP_HIDDEN,
		'S_GROUP_OPEN_CHECKED' => ($group_info['group_type'] == GROUP_OPEN) ? ' checked="checked"' : '',
		'S_GROUP_CLOSED_CHECKED' => ($group_info['group_type'] == GROUP_CLOSED) ? ' checked="checked"' : '',
		'S_GROUP_HIDDEN_CHECKED' => ($group_info['group_type'] == GROUP_HIDDEN) ? ' checked="checked"' : '',
		'S_HIDDEN_FIELDS' => $s_hidden_fields,
		'S_GROUPCP_ACTION' => getlink("&g=$group_id"))
	);

	//
	// Dump out the remaining users
	//
	$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
	for ($i = $start; $i < min($board_config['topics_per_page'] + $start, $members_count); $i++) {

		generate_user_info($group_members[$i], $board_config['default_dateformat'], $is_moderator, $from, $posts, $joined, $profile_img, $profile, $search_img, $search, $pm_img, $pm, $email_img, $email, $www_img, $www);

		if ( $group_info['group_type'] != GROUP_HIDDEN || $is_group_member || $is_moderator ) {
			$row_color = (!($i % 2)) ? $bgcolor2 : $bgcolor1;
			$row_class = (!($i % 2)) ? 'row1' : 'row2';

			$template->assign_block_vars('member_row', array(
				'ROW_COLOR' => $row_color,
				'ROW_CLASS' => $row_class,
				'USERNAME' => $group_members[$i]['username'],
				'FROM' => $from,
				'JOINED' => $joined,
				'POSTS' => $posts,
				'USER_ID' => $group_members[$i]['user_id'],
				'PROFILE_IMG' => $profile_img,
				'PROFILE' => $profile,
				'SEARCH_IMG' => $search_img,
				'SEARCH' => $search,
				'PM_IMG' => $pm_img,
				'PM' => $pm,
				'EMAIL_IMG' => $email_img,
				'EMAIL' => $email,
				'WWW_IMG' => $www_img,
				'WWW' => $www,

				'U_VIEWPROFILE' => getlink('Your_Account&amp;profile='.$group_members[$i]['user_id']))
			);

			if ( $is_moderator ) {
				$template->assign_block_vars('member_row.switch_mod_option', array());
			}
		}
	}

	if (!$members_count) {
		//
		// No group members
		//
		$template->assign_block_vars('switch_no_members', array());
		$template->assign_vars(array('L_NO_MEMBERS' => $lang['No_group_members']));
	}

	$current_page = (!$members_count) ? 1 : ceil($members_count / $board_config['topics_per_page']);

	$template->assign_vars(array(
		'PAGINATION' => generate_pagination("&amp;".POST_GROUPS_URL."=$group_id", $members_count, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), $current_page ),

		'L_GOTO_PAGE' => $lang['Goto_page'])
	);

	if ($group_info['group_type'] == GROUP_HIDDEN && !$is_group_member && !$is_moderator) {
		//
		// No group members
		//
		$template->assign_block_vars('switch_hidden_group', array());
		$template->assign_vars(array('L_HIDDEN_MEMBERS' => $lang['Group_hidden_members']));
	}

	//
	// We've displayed the members who belong to the group, now we
	// do that pending memebers...
	//
	if ($is_moderator && $modgroup_pending_count) {
		//
		// Users pending in ONLY THIS GROUP (which is moderated by this user)
		//
		for($i = 0; $i < $modgroup_pending_count; $i++) {

			generate_user_info($modgroup_pending_list[$i], $board_config['default_dateformat'], $is_moderator, $from, $posts, $joined, $profile_img, $profile, $search_img, $search, $pm_img, $pm, $email_img, $email, $www_img, $www);

			$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
			$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

			$user_select = '<input type="checkbox" name="member[]" value="'.$modgroup_pending_list[$i]['user_id'].'">';

			$template->assign_block_vars('pending_members_row', array(
				'ROW_CLASS' => $row_class,
				'ROW_COLOR' => $row_color,
				'USERNAME' => $modgroup_pending_list[$i]['username'],
				'FROM' => $from,
				'JOINED' => $joined,
				'POSTS' => $posts,
				'USER_ID' => $modgroup_pending_list[$i]['user_id'],
				'PROFILE_IMG' => $profile_img,
				'PROFILE' => $profile,
				'SEARCH_IMG' => $search_img,
				'SEARCH' => $search,
				'PM_IMG' => $pm_img,
				'PM' => $pm,
				'EMAIL_IMG' => $email_img,
				'EMAIL' => $email,
				'WWW_IMG' => $www_img,
				'WWW' => $www,

				'U_VIEWPROFILE' => getlink('Your_Account&amp;profile='.$modgroup_pending_list[$i]['user_id']))
			);
		}

		$template->assign_block_vars('switch_pending_members', array() );

		$template->assign_vars(array(
			'S_PENDING_USERS' => true,
			'L_SELECT' => $lang['Select'],
			'L_APPROVE_SELECTED' => $lang['Approve_selected'],
			'L_DENY_SELECTED' => $lang['Deny_selected'])
		);
	}

	if ($is_moderator) {
		$template->assign_block_vars('switch_mod_option', array());
		$template->assign_block_vars('switch_add_member', array());
	}

	$template->display('info');
}
else
{
	//
	// Show the main Groups screen where the user can select a group.
	//
	// Select all group that the user is a member of or where the user has
	// a pending membership.
	//
	$in_group = array();
	$s_member_groups_opt = $s_pending_groups_opt = '';
	$s_pending_groups = $s_member_groups = '';
	if (is_user()) {
		$sql = "SELECT g.group_id, g.group_name, g.group_type, ug.user_pending
			FROM ".GROUPS_TABLE." g, ".USER_GROUP_TABLE." ug
			WHERE ug.user_id = ".$userinfo['user_id']."
				AND ug.group_id = g.group_id
				AND g.group_single_user <> ".TRUE."
			ORDER BY g.group_name, ug.user_id";
		$result = $db->sql_query($sql);

		if ($row = $db->sql_fetchrow($result)) {
			do {
				$in_group[] = $row['group_id'];
				if ( $row['user_pending'] ) {
					$s_pending_groups_opt .= '<option value="'.$row['group_id'].'">'.$row['group_name'].'</option>';
				} else {
					$s_member_groups_opt .= '<option value="'.$row['group_id'].'">'.$row['group_name'].'</option>';
				}
			}
			while( $row = $db->sql_fetchrow($result) );

			$s_pending_groups = '<select name="g">'.$s_pending_groups_opt.'</select>';
			$s_member_groups = '<select name="g">'.$s_member_groups_opt.'</select>';
		}
	}

	//
	// Select all other groups i.e. groups that this user is not a member of
	//
	$ignore_group_sql = (count($in_group)) ? "AND group_id NOT IN (".implode(', ', $in_group).")" : '';
	$result = $db->sql_query("SELECT group_id, group_name, group_type FROM ".GROUPS_TABLE." WHERE group_single_user=0 $ignore_group_sql ORDER BY group_name");

	$s_group_list_opt = '';
	while( $row = $db->sql_fetchrow($result) ) {
		if ($row['group_type'] != GROUP_HIDDEN || $is_moderator) {
			$s_group_list_opt .='<option value="'.$row['group_id'].'">'.$row['group_name'].'</option>';
		}
	}
	$s_group_list = '<select name="g">'.$s_group_list_opt.'</select>';

	if ($s_group_list_opt != '' || $s_pending_groups_opt != '' || $s_member_groups_opt != '') {
		//
		// Load and process templates
		//
		$pagetitle .= ' '._BC_DELIM.' '.$lang['Group_member_join'];
		define('HEADER_INC', TRUE);
		require_once("header.php");
		OpenTable();

		if ($s_pending_groups_opt != '' || $s_member_groups_opt != '') {
			$template->assign_block_vars('switch_groups_joined', array() );
		}
		if ($s_member_groups_opt != '') {
			$template->assign_block_vars('switch_groups_joined.switch_groups_member', array());
		}
		if ($s_pending_groups_opt != '') {
			$template->assign_block_vars('switch_groups_joined.switch_groups_pending', array());
		}
		if ($s_group_list_opt != '') {
			$template->assign_block_vars('switch_groups_remaining', array() );
		}

		$s_hidden_fields = '';

		$template->assign_vars(array(
			'L_GROUP_MEMBERSHIP_DETAILS' => $lang['Group_member_details'],
			'L_JOIN_A_GROUP' => $lang['Group_member_join'],
			'L_YOU_BELONG_GROUPS' => $lang['Current_memberships'],
			'L_SELECT_A_GROUP' => $lang['Non_member_groups'],
			'L_PENDING_GROUPS' => $lang['Memberships_pending'],
			'L_SUBSCRIBE' => $lang['Subscribe'],
			'L_UNSUBSCRIBE' => $lang['Unsubscribe'],
			'L_VIEW_INFORMATION' => $lang['View_Information'],

			'S_USERGROUP_ACTION' => getlink(),
			'S_HIDDEN_FIELDS' => $s_hidden_fields,

			'GROUP_LIST_SELECT' => $s_group_list,
			'GROUP_PENDING_SELECT' => $s_pending_groups,
			'GROUP_MEMBER_SELECT' => $s_member_groups)
		);

		$template->set_filenames(array('user' => 'groups/user_body.html'));
		$template->display('user');
	} else {
		message_die(GENERAL_MESSAGE, $lang['No_groups_exist']);
	}
}
CloseTable();
