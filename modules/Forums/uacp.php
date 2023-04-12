<?php
/***************************************************************************
 *								   uacp.php
 *							  -------------------
 *	 begin				  : Oct 30, 2002
 *	 copyright			  : (C) 2002 Meik Sievertsen
 *	 email				  : acyd.burn@gmx.de
 *
 *	 $Id: uacp.php,v 9.10 2007/12/12 12:54:25 nanocaiordo Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/
if (!defined('CPG_NUKE')) { exit; }

/**
 * User Attachment Control Panel
 *
 * From this 'Control Panel' the user is able to view/delete his Attachments.
 */

require_once('modules/'.$module_name.'/nukebb.php');

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_PROFILE);
init_userprefs($userdata);
//
// End session management
//

//
// Obtain initial var settings
//
if(isset($_GET[POST_USERS_URL]) || isset($_POST[POST_USERS_URL])) {
	$user_id = (isset($_POST[POST_USERS_URL])) ? $_POST[POST_USERS_URL] : $_GET[POST_USERS_URL];
} else {
	message_die(GENERAL_MESSAGE, $lang['No_user_id_specified']);
}

$user_id = ($user_id == '-1') ? ANONYMOUS : intval($user_id);

$profiledata = getusrdata($user_id);

if ($user_id == ANONYMOUS) {
	$profiledata['user_id'] = ANONYMOUS;
	$profiledata['username'] = $lang['Guest'];
} else {
	$profiledata['user_id'] = intval($profiledata['user_id']);
}

if ($profiledata['user_id'] != $userdata['user_id'] && $userdata['user_level'] != ADMIN) {
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}

$page_title = $lang['User_acp_title'];
require_once("includes/phpBB/page_header.php");

$start = (isset($_GET['start'])) ? $_GET['start'] : 0;

if(isset($_POST['order'])) {
	$sort_order = ($_POST['order'] == 'ASC') ? 'ASC' : 'DESC';
} else if(isset($_GET['order'])) {
	$sort_order = ($_GET['order'] == 'ASC') ? 'ASC' : 'DESC';
} else {
	$sort_order = '';
}

if (isset($_GET['mode']) || isset($_POST['mode'])) {
	$mode = (isset($_POST['mode'])) ? $_POST['mode'] : $_GET['mode'];
} else {
	$mode = '';
}

$mode_types_text = array($lang['Sort_Filename'], $lang['Sort_Comment'], $lang['Sort_Extension'], $lang['Sort_Size'], $lang['Sort_Downloads'], $lang['Sort_Posttime'], /*$lang['Sort_Posts']*/);
$mode_types = array('real_filename', 'comment', 'extension', 'filesize', 'downloads', 'post_time'/*, 'posts'*/);

if (empty($mode)) {
	$mode = 'real_filename';
	$sort_order = 'ASC';
}

//
// Pagination ?
//
$do_pagination = TRUE;

//
// Set Order
//
$order_by = '';

switch($mode)
{
	case 'filename':
		$order_by = 'ORDER BY a.real_filename '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	case 'comment':
		$order_by = 'ORDER BY a.comment '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	case 'extension':
		$order_by = 'ORDER BY a.extension '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	case 'filesize':
		$order_by = 'ORDER BY a.filesize '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	case 'downloads':
		$order_by = 'ORDER BY a.download_count '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	case 'post_time':
		$order_by = 'ORDER BY a.filetime '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
	default:
		$mode = 'a.real_filename';
		$sort_order = 'ASC';
		$order_by = 'ORDER BY a.real_filename '.$sort_order.' LIMIT '.$start.', '.$board_config['topics_per_page'];
		break;
}

//
// Set select fields
//
if (count($mode_types_text) > 0) {
	$select_sort_mode = '<select name="mode">';
	for($i = 0; $i < count($mode_types_text); $i++) {
		$selected = ($mode == $mode_types[$i]) ? ' selected="selected"' : '';
		$select_sort_mode .= '<option value="'.$mode_types[$i].'"'.$selected.'>'.$mode_types_text[$i].'</option>';
	}
	$select_sort_mode .= '</select>';
}

if (!empty($sort_order)) {
	$select_sort_order = '<select name="order">';
	if($sort_order == 'ASC') {
		$select_sort_order .= '<option value="ASC" selected="selected">'.$lang['Sort_Ascending'].'</option><option value="DESC">'.$lang['Sort_Descending'].'</option>';
	} else {
		$select_sort_order .= '<option value="ASC">'.$lang['Sort_Ascending'].'</option><option value="DESC" selected="selected">'.$lang['Sort_Descending'].'</option>';
	}
	$select_sort_order .= '</select>';
}

$delete = ( isset($_POST['delete']) ) ? TRUE : FALSE;
$delete_id_list = ( isset($_POST['delete_id_list']) ) ?	 $_POST['delete_id_list'] : array();

$confirm = ( isset($_POST['confirm']) ) ? TRUE : FALSE;

if ( ($confirm) && (count($delete_id_list) > 0) ) {
	$attachments = array();
	for ($i = 0; $i < count($delete_id_list); $i++) {
		$result = $db->sql_query("SELECT post_id FROM ".ATTACHMENTS_TABLE." WHERE attach_id = ".$delete_id_list[$i]);
		if ($result) {
			$row = $db->sql_fetchrow($result);
			if ($row['post_id'] != 0) {
				delete_attachment(-1, $delete_id_list[$i]);
			} else {
				delete_attachment(-1, $delete_id_list[$i], PAGE_PRIVMSGS, intval($profiledata['user_id']));
			}
		}
	}


} else if ($delete && count($delete_id_list) > 0) {
	// Not confirmed, show confirmation message
	$hidden_fields = '<input type="hidden" name="view" value="'.$view.'" />';
	$hidden_fields .= '<input type="hidden" name="mode" value="'.$mode.'" />';
	$hidden_fields .= '<input type="hidden" name="order" value="'.$sort_order.'" />';
	$hidden_fields .= '<input type="hidden" name="'.POST_USERS_URL.'" value="'.$profiledata['user_id'].'" />';
	$hidden_fields .= '<input type="hidden" name="start" value="'.$start.'" />';
	for($i = 0; $i < count($delete_id_list); $i++) {
		$hidden_fields .= '<input type="hidden" name="delete_id_list[]" value="'.$delete_id_list[$i].'" />';
	}
	$template->assign_vars(array(
		'MESSAGE_TITLE' => $lang['Confirm'],
		'MESSAGE_TEXT' => $lang['Confirm_delete_attachments'],

		'L_YES' => $lang['Yes'],
		'L_NO' => $lang['No'],

		'S_CONFIRM_ACTION' => getlink('&amp;file=uacp'),
		'S_HIDDEN_FIELDS' => $hidden_fields)
	);
	$template->set_filenames(array('body' => 'confirm_body.html'));
	require_once('includes/phpBB/page_tail.php');
	exit;
}

$hidden_fields = '';

$total_rows = 0;

$username = $profiledata['username'];

$s_hidden = '<input type="hidden" name="'.POST_USERS_URL.'" value="'.$profiledata['user_id'].'" />';

//
// Assign Template Vars
//
$template->assign_vars(array(
	'L_SUBMIT' => $lang['Submit'],
	'L_UACP' => $lang['UACP'],
	'L_SELECT_SORT_METHOD' => $lang['Select_sort_method'],
	'L_ORDER' => $lang['Order'],
	'L_FILENAME' => $lang['File_name'],
	'L_FILECOMMENT' => $lang['File_comment'],
	'L_EXTENSION' => $lang['Extension'],
	'L_SIZE' => $lang['Filesize'],
	'L_DOWNLOADS' => $lang['Downloaded'],
	'L_POST_TIME' => $lang['Sort_Time'],
	'L_POSTED_IN_TOPIC' => $lang['Sort_Topic_Title'],
	'L_DELETE' => $lang['Delete'],
	'L_DELETE_MARKED' => $lang['Delete_marked'],
	'L_MARK_ALL' => $lang['Mark_all'],
	'L_UNMARK_ALL' => $lang['Unmark_all'],

	'USERNAME' => $profiledata['username'],

	'S_USER_HIDDEN' => $s_hidden,
	'S_MODE_ACTION' => getlink('&amp;file=uacp'),
	'S_MODE_SELECT' => $select_sort_mode,
	'S_ORDER_SELECT' => $select_sort_order)
);

$result = $db->sql_query("SELECT attach_id FROM ".ATTACHMENTS_TABLE."
WHERE user_id_1 = ".$profiledata['user_id']." OR user_id_2 = ".$profiledata['user_id']."
GROUP BY attach_id");

$attach_ids = $db->sql_fetchrowset($result);
$num_attach_ids = $db->sql_numrows($result);
$total_rows = $num_attach_ids;

if ($num_attach_ids > 0) {
	$attach_id = array();
	for ($j = 0; $j < $num_attach_ids; $j++) {
		$attach_id[] = $attach_ids[$j]['attach_id'];
	}
	$result = $db->sql_query("SELECT a.* FROM ".ATTACHMENTS_DESC_TABLE." a
		WHERE a.attach_id IN (".implode(', ', $attach_id).") ".$order_by);
	$attachments = $db->sql_fetchrowset($result);
	$num_attach = $db->sql_numrows($result);
} else {
	$attachments = array();
}

if (count($attachments) > 0) {
	for ($i = 0; $i < count($attachments); $i++) {
		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';

		//
		// Is the Attachment assigned to more than one post ?
		// If it's not assigned to any post, it's an private message thingy. ;)
		//
		$post_titles = array();

		$result = $db->sql_query("SELECT * FROM ".ATTACHMENTS_TABLE." WHERE attach_id = ".$attachments[$i]['attach_id']);

		$ids = $db->sql_fetchrowset($result);
		$num_ids = $db->sql_numrows($result);

		for ($j = 0; $j < $num_ids; $j++) {
			if ($ids[$j]['post_id'] != 0) {
				$result = $db->sql_query("SELECT t.topic_title FROM ".TOPICS_TABLE." t, ".POSTS_TABLE." p
				WHERE p.post_id = ".$ids[$j]['post_id']." AND p.topic_id = t.topic_id
				GROUP BY t.topic_id, t.topic_title");
				$row = $db->sql_fetchrow($result);
				$post_title = htmlunprepare($row['topic_title']);

				if (strlen($post_title) > 32) {
					$post_title = substr($post_title, 0, 30).'...';
				}
				$view_topic = getlink('&amp;file=viewtopic&amp;'.POST_POST_URL.'='.$ids[$j]['post_id']).'#'.$ids[$j]['post_id'];
				$post_titles[] = '<a href="'.$view_topic.'" class="gen" target="_blank">'.htmlprepare($post_title).'</a>';
			} else {
				$desc = '';

				$result = $db->sql_query("SELECT privmsgs_type, privmsgs_to_userid, privmsgs_from_userid
				FROM ".PRIVMSGS_TABLE."
				WHERE privmsgs_id = ".$ids[$j]['privmsgs_id']);

				if ($db->sql_numrows($result) != 0) {
					$row = $db->sql_fetchrow($result);
					$privmsgs_type = $row['privmsgs_type'];
					if (($privmsgs_type == PRIVMSGS_READ_MAIL) || ($privmsgs_type == PRIVMSGS_NEW_MAIL) || ($privmsgs_type == PRIVMSGS_UNREAD_MAIL)) {
						if ($row['privmsgs_to_userid'] == $profiledata['user_id']) {
							$desc = $lang['Private_Message'].' ('.$lang['Inbox'].')';
						}
					} else if ($privmsgs_type == PRIVMSGS_SENT_MAIL) {
						if ($row['privmsgs_from_userid'] == $profiledata['user_id']){
							$desc = $lang['Private_Message'].' ('.$lang['Sentbox'].')';
						}
					} else if ( ($privmsgs_type == PRIVMSGS_SAVED_OUT_MAIL) ) {
						if ($row['privmsgs_from_userid'] == $profiledata['user_id']) {
							$desc = $lang['Private_Message'].' ('.$lang['Savebox'].')';
						}
					} else if ( ($privmsgs_type == PRIVMSGS_SAVED_IN_MAIL) ) {
						if ($row['privmsgs_to_userid'] == $profiledata['user_id']) {
							$desc = $lang['Private_Message'].' ('.$lang['Savebox'].')';
						}
					}
					if ($desc != '') {
						$post_titles[] = $desc;
					}
				}
			}
		}

		// Iron out those Attachments assigned to us, but not more controlled by us. ;) (PM's)
		if (count($post_titles) > 0) {
			$delete_box = '<input type="checkbox" name="delete_id_list[]" value="'.$attachments[$i]['attach_id'].'" />';

			for ($j = 0; $j < count($delete_id_list); $j++) {
				if ($delete_id_list[$j] == $attachments[$i]['attach_id']) {
					$delete_box = '<input type="checkbox" name="delete_id_list[]" value="'.$attachments[$i]['attach_id'].'" checked="checked" />';
					break;
				}
			}

			$post_titles = implode('<br />', $post_titles);

			$hidden_field = '<input type="hidden" name="attach_id_list[]" value="'.$attachments[$i]['attach_id'].'">';

			$template->assign_block_vars('attachrow', array(
				'ROW_NUMBER' => $i + ( $_GET['start'] + 1 ),
				'ROW_COLOR' => $row_color,
				'ROW_CLASS' => $row_class,

				'FILENAME' => $attachments[$i]['real_filename'],
				'COMMENT' => nl2br(htmlprepare($attachments[$i]['comment'])),
				'EXTENSION' => $attachments[$i]['extension'],
				'SIZE' => round(($attachments[$i]['filesize'] / MEGABYTE), 2),
				'DOWNLOAD_COUNT' => $attachments[$i]['download_count'],
				'POST_TIME' => create_date($board_config['default_dateformat'], $attachments[$i]['filetime']),
				'POST_TITLE' => $post_titles,

				'S_DELETE_BOX' => $delete_box,
				'S_HIDDEN' => $hidden_field,
				'U_VIEW_ATTACHMENT' => getlink('Forums&amp;file=download&amp;id='.$attachments[$i]['attach_id'])
	// PM error ?
	//			  'U_VIEW_POST' => ($attachments[$i]['post_id'] != 0) ? getlink("&amp;file=viewtopic&amp;".POST_POST_URL."=".$attachments[$i]['post_id']."#".$attachments[$i]['post_id']) : ''
				)
			);
		}
	}
}

//
// Generate Pagination
//
if ( ($do_pagination) && ($total_rows > $board_config['topics_per_page']) ) {
	$pagination = generate_pagination('&amp;file=uacp&amp;mode='.$mode.'&amp;order='.$sort_order.'&amp;'.POST_USERS_URL.'='.$profiledata['user_id'], $total_rows, $board_config['topics_per_page'], $start).'&nbsp;';
	$template->assign_vars(array(
		'PAGINATION' => $pagination,
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $total_rows / $board_config['topics_per_page'] )),

		'L_GOTO_PAGE' => $lang['Goto_page'])
	);
}

$template->set_filenames(array('body' => 'forums/uacp_body.html'));

require_once('includes/phpBB/page_tail.php');