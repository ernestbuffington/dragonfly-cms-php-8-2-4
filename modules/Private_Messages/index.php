<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Original copyright : (C) 2001 The phpBB Group

  $Source: /cvs/html/modules/Private_Messages/index.php,v $
  $Revision: 9.26 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:27 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
require_once('modules/'.$module_name.'/init.inc');
$pagetitle .= _Private_MessagesLANG;
global $SESS;
if (isset($_GET['folder'])){
	switch($_GET['folder']){
		case 'inbox':
			$pagetitle .= ' '._BC_DELIM.' '.$lang['Inbox'];
			break;
		case 'sentbox':
			$pagetitle .= ' '._BC_DELIM.' '.$lang['Sentbox'];
			break;
		case 'outbox':
			$pagetitle .= ' '._BC_DELIM.' '.$lang['Outbox'];
			break;
		case 'savebox':
			$pagetitle .= ' '._BC_DELIM.' '.$lang['Savebox'];
			break;
		default:
	}
}

// Start main
//
if ( $mode == 'newpm' ) {
	// PM popup
	$gen_simple_header = TRUE;
	$page_title .= $lang['Private_Messaging'];
	require_once('includes/phpBB/page_header.php');
	if (is_user()) {
		if ($userdata['user_new_privmsg']) {
			$l_new_message = ( $userdata['user_new_privmsg'] == 1 ) ? $lang['You_new_pm'] : $lang['You_new_pms'];
		} else {
			$l_new_message = $lang['You_no_new_pm'];
		}
		$l_new_message .= '<br /><br />'.sprintf($lang['Click_view_privmsg'], '<a href="'.getlink('Private_Messages&amp;folder=inbox').'" onclick="jump_to_inbox();return false;" target="_blank">', '</a>');
	} else {
		$l_new_message = $lang['Login_check_pm'];
	}
	$template->assign_vars(array(
		'L_CLOSE_WINDOW' => $lang['Close_window'],
		'L_MESSAGE' => $l_new_message)
	);
	$template->set_filenames(array('body' => 'private_msgs/popup.html'));
	require_once('includes/phpBB/page_tail.php');
} else if ( $mode == 'read' ) {
	require_once('modules/'.$module_name.'/read.php');
} else if ( ( $delete && $mark_list ) || $delete_all ) {
	require_once('modules/'.$module_name.'/delete.php');
} else if ( $save && $mark_list && $folder != 'savebox' && $folder != 'outbox' ) {
	if (sizeof($mark_list)) {
		require_once('modules/'.$module_name.'/save.php');
	}
} else if ($submit || $refresh || $mode != '') {
	if (!$board_config['allow_html']) {
		$html_on = 0;
	} else {
		$html_on = intval(($submit || $refresh) ? empty($_POST['disable_html']) : $userdata['user_allowhtml']);
	}
	if (!$board_config['allow_bbcode']) {
		$bbcode_on = 0;
	} else {
		$bbcode_on = intval(($submit || $refresh) ? empty($_POST['disable_bbcode']) : $userdata['user_allowbbcode']);
	}
	if (!$board_config['allow_smilies']) {
		$smilies_on = 0;
	} else {
		$smilies_on = intval(($submit || $refresh) ? empty($_POST['disable_smilies']) : $userdata['user_allowsmile']);
	}
	$attach_sig = ($submit || $refresh) ? intval(!empty($_POST['attach_sig'])) : $userdata['user_attachsig'];
	$user_sig = ($userdata['user_sig'] != '' && $board_config['allow_sig']) ? $userdata['user_sig'] : '';

	if ( $submit && $mode != 'edit' ) {
		// Flood control
		list($last_post_time) = $db->sql_ufetchrow("SELECT MAX(privmsgs_date) FROM ".$prefix."_bbprivmsgs
		WHERE privmsgs_from_userid=".$userdata['user_id'], SQL_NUM);
		if ((gmtime() - $last_post_time) < $board_config['flood_interval']) {
			message_die(GENERAL_MESSAGE, $lang['Flood_Error']);
		}
	}

	if ($submit) {
		if ( !empty($_POST['username']) ) {
			$to_username = $_POST['username'];
			$sql = "SELECT user_id, user_notify_pm, user_email, user_lang, user_active FROM ".$user_prefix."_users
				WHERE username = '".Fix_Quotes($to_username)."' AND user_id > 1";
			$result = $db->sql_query($sql);
			if ($db->sql_numrows($result)<1) {
				$error = TRUE;
				$error_msg = $lang['No_such_user'];
			}
			$to_userdata = $db->sql_fetchrow($result);
		} else {
			$error = TRUE;
			$error_msg .= ( ( !empty($error_msg) ) ? '<br />' : '' ).$lang['No_to_user'];
		}
		$privmsg_subject = trim(strip_tags($_POST['subject']));
		if ( empty($privmsg_subject) ) {
			$error = TRUE;
			$error_msg .= ( ( !empty($error_msg) ) ? '<br />' : '' ).$lang['Empty_subject'];
		}
		if ( !empty($_POST['message']) ) {
			if ( !$error ) {
				$privmsg_message = message_prepare(((isset($_POST['quick_quote']) && !empty($_POST['quick_quote'])) ? $_POST['quick_quote'] : '').$_POST['message'], $html_on, $bbcode_on);
			}
		} else {
			$error = TRUE;
			$error_msg .= ( ( !empty($error_msg) ) ? '<br />' : '' ).$lang['Empty_message'];
		}
	}

	if ($submit && !$error)
	{
		//
		// Has admin prevented user from sending PM's?
		//
		if (!$userdata['user_allow_pm']) {
			$message = $lang['Cannot_send_privmsg'];
			message_die(GENERAL_MESSAGE, $message);
		}
		$msg_time = gmtime();
		if ($mode != 'edit') {
			//
			// See if recipient is at their inbox limit
			//
			$sql = "SELECT COUNT(privmsgs_id) AS inbox_items, MIN(privmsgs_date) AS oldest_post_time
				FROM ".$prefix."_bbprivmsgs
				WHERE ( privmsgs_type = ".PRIVMSGS_NEW_MAIL."
					 OR privmsgs_type = ".PRIVMSGS_READ_MAIL."
					 OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )
					AND privmsgs_to_userid = ".$to_userdata['user_id'];
			$result = $db->sql_query($sql);
			$sql_priority = ( SQL_LAYER == 'mysql' ) ? 'LOW_PRIORITY' : '';
			if ( $inbox_info = $db->sql_fetchrow($result) ) {
				if ( $inbox_info['inbox_items'] >= $board_config['max_inbox_privmsgs'] ) {
					$sql = "SELECT privmsgs_id FROM ".$prefix."_bbprivmsgs
						WHERE ( privmsgs_type = ".PRIVMSGS_NEW_MAIL."
							 OR privmsgs_type = ".PRIVMSGS_READ_MAIL."
							 OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL."  )
							AND privmsgs_date = ".$inbox_info['oldest_post_time']."
							AND privmsgs_to_userid = ".$to_userdata['user_id'];
					$result = $db->sql_query($sql);
					$old_privmsgs_id = $db->sql_fetchrow($result);
					$old_privmsgs_id = $old_privmsgs_id['privmsgs_id'];
					$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs
					WHERE privmsgs_id = $old_privmsgs_id");
					$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs_text
					WHERE privmsgs_text_id = $old_privmsgs_id");
				}
			}

			$db->sql_query("INSERT INTO ".$prefix."_bbprivmsgs (privmsgs_type, privmsgs_subject, privmsgs_from_userid, privmsgs_to_userid, privmsgs_date, privmsgs_ip, privmsgs_enable_html, privmsgs_enable_bbcode, privmsgs_enable_smilies, privmsgs_attach_sig)
				VALUES (".PRIVMSGS_NEW_MAIL.", '".Fix_Quotes($privmsg_subject)."', ".$userdata['user_id'].", ".$to_userdata['user_id'].", $msg_time, ".$userinfo['user_ip'].", $html_on, $bbcode_on, $smilies_on, $attach_sig)");
			$privmsg_sent_id = $db->sql_nextid('privmsgs_id');
			$db->sql_query("INSERT INTO ".$prefix."_bbprivmsgs_text (privmsgs_text_id, privmsgs_text)
			VALUES ($privmsg_sent_id, '".Fix_Quotes($privmsg_message)."')");
		} else {
			$db->sql_query("UPDATE ".$prefix."_bbprivmsgs
				SET privmsgs_type = ".PRIVMSGS_NEW_MAIL.", privmsgs_subject = '".Fix_Quotes($privmsg_subject)."', privmsgs_from_userid = ".$userdata['user_id'].", privmsgs_to_userid = ".$to_userdata['user_id'].", privmsgs_date = $msg_time, privmsgs_ip = '".$userinfo['user_ip']."', privmsgs_enable_html = $html_on, privmsgs_enable_bbcode = $bbcode_on, privmsgs_enable_smilies = $smilies_on, privmsgs_attach_sig = $attach_sig
				WHERE privmsgs_id = $privmsg_id");
			$db->sql_query("UPDATE ".$prefix."_bbprivmsgs_text
			SET privmsgs_text = '".Fix_Quotes($privmsg_message)."'
			WHERE privmsgs_text_id = $privmsg_id");
		}

		if ( $mode != 'edit' )
		{
			//
			// Add to the users new pm counter
			//
			$sql = "UPDATE ".$user_prefix."_users
				SET user_new_privmsg = user_new_privmsg + 1, user_last_privmsg = ".gmtime()."
				WHERE user_id = ".$to_userdata['user_id'];
			$status = $db->sql_query($sql);
			unset($_SESSION['CPG_USER']);

			if ( $to_userdata['user_notify_pm'] && !empty($to_userdata['user_email']) && $to_userdata['user_active'] )
			{
				require_once('includes/phpBB/emailer.php');
				$emailer = new emailer();

				$emailer->from($board_config['board_email']);
				$emailer->replyto($board_config['board_email']);

				$emailer->use_template('privmsg_notify', $to_userdata['user_lang']);
				$emailer->email_address($to_userdata['user_email']);
				$emailer->set_subject($lang['Notification_subject']);

				$emailer->assign_vars(array(
					'USERNAME' => $to_username,
					'SITENAME' => $board_config['sitename'],
					'EMAIL_SIG' => (!empty($board_config['board_email_sig'])) ? str_replace('<br />', "\n", "-- \n".$board_config['board_email_sig']) : '',

					'U_INBOX' => getlink('&amp;folder=inbox', true, true)
					)
				);

				$emailer->send();
				$emailer->reset();
			}
		}
		url_refresh(getlink('&folder=inbox'));
		$msg = $lang['Message_sent'].'<br /><br />'.sprintf($lang['Click_return_inbox'], '<a href="'.getlink('&amp;folder=inbox').'">', '</a> ').'<br /><br />'.sprintf($lang['Click_return_index'], '<a href="'.$mainindex.'">', '</a>');
		message_die(GENERAL_MESSAGE, $msg);
	}
	else if ($preview || $refresh || $error)
	{
		//
		// If we're previewing or refreshing then obtain the data
		// passed to the script, process it a little, do some checks
		// where neccessary, etc.
		//
		$to_username = ( isset($_POST['username']) ) ? trim(strip_tags($_POST['username'])) : '';
		$privmsg_subject = ( isset($_POST['subject']) ) ? trim(strip_tags($_POST['subject'])) : '';
		$privmsg_message = ( isset($_POST['message']) ) ? trim($_POST['message']) : '';
		$privmsg_message = preg_replace('#<textarea>#si', '&lt;textarea&gt;', $privmsg_message);

		//
		// Do mode specific things
		//
		if ($mode == 'post') {
			$page_title = $lang['Post_new_pm'];
			$user_sig = ( $userdata['user_sig'] != '' && $board_config['allow_sig'] ) ? $userdata['user_sig'] : '';
		}
		else if ($mode == 'reply') {
			$page_title = $lang['Post_reply_pm'];
			$user_sig = ( $userdata['user_sig'] != '' && $board_config['allow_sig'] ) ? $userdata['user_sig'] : '';
		}
		else if ($mode == 'edit') {
			$page_title = $lang['Edit_pm'];
			$sql = "SELECT u.user_id, u.user_sig
				FROM ".$prefix."_bbprivmsgs pm, ".$user_prefix."_users u
				WHERE pm.privmsgs_id = $privmsg_id
					AND u.user_id = pm.privmsgs_from_userid";
			$result = $db->sql_query($sql);
			if ($postrow = $db->sql_fetchrow($result) ) {
				if ( $userdata['user_id'] != $postrow['user_id'] ) {
					message_die(GENERAL_MESSAGE, $lang['Edit_own_posts']);
				}
				$user_sig = ( $postrow['user_sig'] != '' && $board_config['allow_sig'] ) ? $postrow['user_sig'] : '';
			}
		}
	}
	else
	{
		if ( !$privmsg_id && ( $mode == 'reply' || $mode == 'edit' || $mode == 'quote' ) ) {
			message_die(GENERAL_ERROR, $lang['No_post_id']);
		}

		if ( !empty($_GET[POST_USERS_URL]) )
		{
			$user_id = intval($_GET[POST_USERS_URL]);
			$result = $db->sql_query("SELECT username FROM ".$user_prefix."_users
			WHERE user_id = $user_id AND user_id > 1");
			if ( $row = $db->sql_fetchrow($result) ) {
				$to_username = $row['username'];
			} else {
				$error = TRUE;
				$error_msg = $lang['No_such_user'];
			}
		}

		if ( $mode == 'edit' )
		{
			$result = $db->sql_query("SELECT pm.*, pmt.privmsgs_text, u.username, u.user_id, u.user_sig
				FROM ".$prefix."_bbprivmsgs pm, ".$prefix."_bbprivmsgs_text pmt, ".$user_prefix."_users u
				WHERE pm.privmsgs_id = $privmsg_id
					AND pmt.privmsgs_text_id = pm.privmsgs_id
					AND pm.privmsgs_from_userid = ".$userdata['user_id']."
					AND ( pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL."
						OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )
					AND u.user_id = pm.privmsgs_to_userid");
			if (!($privmsg = $db->sql_fetchrow($result))) {
				url_redirect(getlink('&amp;folder='.$folder, true));
			}
			$privmsg_subject = $privmsg['privmsgs_subject'];
			$privmsg_message = $privmsg['privmsgs_text'];
			$privmsg_message = str_replace('<br />', "\n", $privmsg_message);
			$privmsg_message = preg_replace('#</textarea>#si', '&lt;/textarea&gt;', $privmsg_message);
			$user_sig = ( $board_config['allow_sig'] ) ? (($privmsg['privmsgs_type'] == PRIVMSGS_NEW_MAIL) ? $user_sig : $privmsg['user_sig']) : '';
			$to_username = $privmsg['username'];
			$to_userid = $privmsg['user_id'];
		}
		else if ( $mode == 'reply' || $mode == 'quote' )
		{
			$result = $db->sql_query("SELECT pm.privmsgs_subject, pm.privmsgs_date, pmt.privmsgs_text, u.username, u.user_id
				FROM ".$prefix."_bbprivmsgs pm, ".$prefix."_bbprivmsgs_text pmt, ".$user_prefix."_users u
				WHERE pm.privmsgs_id = $privmsg_id
					AND pmt.privmsgs_text_id = pm.privmsgs_id
					AND pm.privmsgs_to_userid = ".$userdata['user_id']."
					AND u.user_id = pm.privmsgs_from_userid");

			if (!($privmsg = $db->sql_fetchrow($result))) {
				url_redirect(getlink('&amp;folder='.$folder, true));
			}

			$privmsg_subject = ( ( !preg_match('/^Re:/', $privmsg['privmsgs_subject']) ) ? 'Re: ' : '' ).$privmsg['privmsgs_subject'];

			$to_username = $privmsg['username'];
			$to_userid = $privmsg['user_id'];

			if ( $mode == 'quote' )
			{
				$privmsg_message = $privmsg['privmsgs_text'];
				$privmsg_message = str_replace('<br />', "\n", $privmsg_message);
				$privmsg_message = preg_replace('#</textarea>#si', '&lt;/textarea&gt;', $privmsg_message);
				$msg_date =	 create_date($board_config['default_dateformat'], $privmsg['privmsgs_date']);
				$privmsg_message = '[quote="'.$to_username.'"]'.$privmsg_message.'[/quote]';
				$mode = 'reply';
			}
		}
	}

	//
	// Has admin prevented user from sending PM's?
	//
	if ( !$userdata['user_allow_pm'] && $mode != 'edit' ) {
		$message = $lang['Cannot_send_privmsg'];
		message_die(GENERAL_MESSAGE, $message);
	}

	//
	// Start output, first preview, then errors then post form
	//
	if ( $mode == 'post' ) {
		$pagetitle .= ' '._BC_DELIM.' '.$lang['Send_a_new_message'];
	} else if ( $mode == 'reply' ) {
		$pagetitle .= ' '._BC_DELIM.' '.$lang['Send_a_reply'];
	} else if ( $mode == 'edit' ) {
		$pagetitle .= ' '._BC_DELIM.' '.$lang['Edit_message'];
	}
	define('HEADER_INC', TRUE);
	require_once('header.php');
	OpenTable();

	if ($preview && !$error) {
		$orig_word = array();
		$replacement_word = array();
		obtain_word_list($orig_word, $replacement_word);

		$preview_message = message_prepare($privmsg_message, $html_on, $bbcode_on);
		$privmsg_message = preg_replace($html_entities_match, $html_entities_replace, $privmsg_message);

		//
		// Finalise processing as per viewtopic
		//
		if (!$html_on || !$board_config['allow_html'] || !$userdata['user_allowhtml']) {
			if ($user_sig != '') {
				$user_sig = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $user_sig);
			}
		}

		if ($attach_sig && $user_sig != '') {
			require_once('includes/nbbcode.php');
			$user_sig = decode_bbcode($user_sig, 1, false);
		}

		if ($bbcode_on) {
			$preview_message = decode_bbcode($preview_message, 1);
		}

		if ( $attach_sig && $user_sig != '' ) {
			$preview_message = $preview_message.'<br /><br />_________________<br />'.$user_sig;
		}
		if ( count($orig_word) ) {
			$preview_subject = preg_replace($orig_word, $replacement_word, $privmsg_subject);
			$preview_message = preg_replace($orig_word, $replacement_word, $preview_message);
		} else {
			$preview_subject = $privmsg_subject;
		}

		if ( $smilies_on ) {
			$preview_message = set_smilies($preview_message);
		}

		$preview_message = make_clickable($preview_message);

		$s_hidden_fields = '<input type="hidden" name="folder" value="'.$folder.'" />';
		$s_hidden_fields .= '<input type="hidden" name="mode" value="'.$mode.'" />';

		if ( isset($privmsg_id) ) {
			$s_hidden_fields .= '<input type="hidden" name="p" value="'.$privmsg_id.'" />';
		}

		$template->assign_vars(array(
			'TOPIC_TITLE' => $preview_subject,
			'POST_SUBJECT' => $preview_subject,
			'MESSAGE_TO' => $to_username,
			'MESSAGE_FROM' => $userdata['username'],
			'POST_DATE' => create_date($board_config['default_dateformat'], gmtime()),
			'PREVIEW_MESSAGE' => $preview_message,
			'MESSAGE' => $preview_message, // for old template system

			'S_HIDDEN_FIELDS' => $s_hidden_fields,

			'L_SUBJECT' => $lang['Subject'],
			'L_DATE' => $lang['Date'],
			'L_FROM' => $lang['From'],
			'L_TO' => $lang['To'],
			'L_PREVIEW' => $lang['Preview'],
			'L_POSTED' => $lang['Posted'])
		);
	}

	//
	// Start error handling
	//
	if ($error) {
		$template->assign_vars(array('ERROR_MESSAGE' => $error_msg));
	} else {
		$template->assign_vars(array('ERROR_MESSAGE' => ''));
	}

	//
	// Enable extensions in posting_body
	//
	$template->assign_block_vars('switch_privmsg', array());

	//
	// HTML toggle selection
	//
	if ( $board_config['allow_html'] ) {
		$html_status = $lang['HTML_is_ON'];
		$template->assign_block_vars('switch_html_checkbox', array());
	} else {
		$html_status = $lang['HTML_is_OFF'];
	}

	//
	// BBCode toggle selection
	//
	if ( $board_config['allow_bbcode'] ) {
		$bbcode_status = $lang['BBCode_is_ON'];
		$template->assign_block_vars('switch_bbcode_checkbox', array());
	} else {
		$bbcode_status = $lang['BBCode_is_OFF'];
	}

	//
	// Smilies toggle selection
	//
	if ( $board_config['allow_smilies'] ) {
		$smilies_status = $lang['Smilies_are_ON'];
		$template->assign_block_vars('switch_smilies_checkbox', array());
	} else {
		$smilies_status = $lang['Smilies_are_OFF'];
	}

	//
	// Signature toggle selection - only show if
	// the user has a signature
	//
	if ( $user_sig != '' ) {
		$template->assign_block_vars('switch_signature_checkbox', array());
	}

	if ( $mode == 'reply' ) { $mode = 'post'; }

	$s_hidden_fields = '<input type="hidden" name="folder" value="'.$folder.'" />';
	$s_hidden_fields .= '<input type="hidden" name="mode" value="'.$mode.'" />';
	if ( $mode == 'edit' ) {
		$s_hidden_fields .= '<input type="hidden" name="p" value="'.$privmsg_id.'" />';
	}

	//
	// Send smilies to template
	//
	generate_smilies('inline', '-10');

	$privmsg_subject = preg_replace($html_entities_match, $html_entities_replace, (isset($privmsg_subject)?$privmsg_subject:''));
	$privmsg_subject = str_replace('"', '&quot;', $privmsg_subject);

	$template->assign_vars(array(
		'S_PREVIEW_BOX' => ($preview && !$error),
		'SUBJECT' => $privmsg_subject,
		'USERNAME' => preg_replace($html_entities_match, $html_entities_replace, (isset($to_username)?$to_username:'')),
		'MESSAGE' => isset($privmsg_message)?$privmsg_message:'',
		'HTML_STATUS' => $html_status,
		'SMILIES_STATUS' => $smilies_status,
		'BBCODE_STATUS' => sprintf($bbcode_status, '<a href="'.getlink('Forums&amp;file=faq&amp;mode=bbcode').'" target="_phpbbcode">', '</a>'),
		'FORUM_NAME' => $lang['Private_Message'],
		'BBCODE_TABLE' => bbcode_table('message', 'post', 1),
		'SMILES_TABLE' => smilies_table('inline', 'message', 'post'),
		'L_EMOTICONS' => $lang['Emoticons'],

		'BOX_NAME' => $l_box_name,
		'INBOX_IMG' => $inbox_img,
		'SENTBOX_IMG' => $sentbox_img,
		'OUTBOX_IMG' => $outbox_img,
		'SAVEBOX_IMG' => $savebox_img,
		'INBOX' => $inbox_url,
		'SENTBOX' => $sentbox_url,
		'OUTBOX' => $outbox_url,
		'SAVEBOX' => $savebox_url,

		'L_USERNAME' => $lang['Username'],
		'L_GO' => $lang['Go'],
		'L_SUBJECT' => $lang['Subject'],
		'L_MESSAGE_BODY' => $lang['Message_body'],
		'L_OPTIONS' => $lang['Options'],
		'L_SPELLCHECK' => $lang['Spellcheck'],
		'L_PREVIEW' => $lang['Preview'],
		'L_SUBMIT' => $lang['Submit'],
		'L_CANCEL' => $lang['Cancel'],
		'L_POST_A' => $pagetitle,
		'L_FIND_USERNAME' => $lang['Find_username'],
		'L_FIND' => $lang['Find'],
		'L_DISABLE_HTML' => $lang['Disable_HTML_pm'],
		'L_DISABLE_BBCODE' => $lang['Disable_BBCode_pm'],
		'L_DISABLE_SMILIES' => $lang['Disable_Smilies_pm'],
		'L_ATTACH_SIGNATURE' => $lang['Attach_signature'],

		/*
		'L_BBCODE_B_HELP' => $lang['bbcode_b_help'],
		'L_BBCODE_I_HELP' => $lang['bbcode_i_help'],
		'L_BBCODE_U_HELP' => $lang['bbcode_u_help'],
		'L_BBCODE_Q_HELP' => $lang['bbcode_q_help'],
		'L_BBCODE_C_HELP' => $lang['bbcode_c_help'],
		'L_BBCODE_L_HELP' => $lang['bbcode_l_help'],
		'L_BBCODE_O_HELP' => $lang['bbcode_o_help'],
		'L_BBCODE_P_HELP' => $lang['bbcode_p_help'],
		'L_BBCODE_W_HELP' => $lang['bbcode_w_help'],
		'L_BBCODE_A_HELP' => $lang['bbcode_a_help'],
		'L_BBCODE_S_HELP' => $lang['bbcode_s_help'],
		'L_BBCODE_F_HELP' => $lang['bbcode_f_help'],
		*/
		'L_EMPTY_MESSAGE' => $lang['Empty_message'],
		/*

		'L_FONT_COLOR' => $lang['Font_color'],
		'L_COLOR_DEFAULT' => $lang['color_default'],
		'L_COLOR_DARK_RED' => $lang['color_dark_red'],
		'L_COLOR_RED' => $lang['color_red'],
		'L_COLOR_ORANGE' => $lang['color_orange'],
		'L_COLOR_BROWN' => $lang['color_brown'],
		'L_COLOR_YELLOW' => $lang['color_yellow'],
		'L_COLOR_GREEN' => $lang['color_green'],
		'L_COLOR_OLIVE' => $lang['color_olive'],
		'L_COLOR_CYAN' => $lang['color_cyan'],
		'L_COLOR_BLUE' => $lang['color_blue'],
		'L_COLOR_DARK_BLUE' => $lang['color_dark_blue'],
		'L_COLOR_INDIGO' => $lang['color_indigo'],
		'L_COLOR_VIOLET' => $lang['color_violet'],
		'L_COLOR_WHITE' => $lang['color_white'],
		'L_COLOR_BLACK' => $lang['color_black'],

		'L_FONT_SIZE' => $lang['Font_size'],
		'L_FONT_TINY' => $lang['font_tiny'],
		'L_FONT_SMALL' => $lang['font_small'],
		'L_FONT_NORMAL' => $lang['font_normal'],
		'L_FONT_LARGE' => $lang['font_large'],
		'L_FONT_HUGE' => $lang['font_huge'],

		'L_BBCODE_CLOSE_TAGS' => $lang['Close_Tags'],
		'L_STYLES_TIP' => $lang['Styles_tip'],
*/
		'S_HTML_CHECKED' => ( !$html_on ) ? ' checked="checked"' : '',
		'S_BBCODE_CHECKED' => ( !$bbcode_on ) ? ' checked="checked"' : '',
		'S_SMILIES_CHECKED' => ( !$smilies_on ) ? ' checked="checked"' : '',
		'S_SIGNATURE_CHECKED' => ( $attach_sig ) ? ' checked="checked"' : '',
		'S_HIDDEN_FORM_FIELDS' => $s_hidden_fields,
		'S_POST_ACTION' => getlink(),
		'S_FORM_ENCTYPE' => ' enctype="multipart/form-data" accept-charset="utf-8"',
		'U_SEARCH_USER' => getlink('Forums&amp;file=search&amp;mode=searchuser&amp;popup=1', true, true),
		'U_VIEW_FORUM' => getlink())
	);

	$template->set_filenames(array('body' => 'private_msgs/posting_body.html'));
	$template->display('body');

	CloseTable();
	return;
}

//
// Update unread status
//
$db->sql_query("UPDATE ".$user_prefix."_users
	SET user_unread_privmsg = user_unread_privmsg + user_new_privmsg, user_new_privmsg = 0, user_last_privmsg = ".$CPG_SESS['session_start']."
	WHERE user_id = ".$userdata['user_id']);

$db->sql_query("UPDATE ".$prefix."_bbprivmsgs
	SET privmsgs_type = ".PRIVMSGS_UNREAD_MAIL."
	WHERE privmsgs_type = ".PRIVMSGS_NEW_MAIL."
		AND privmsgs_to_userid = ".$userdata['user_id']);

//
// Reset PM counters
//
$userdata['user_new_privmsg'] = 0;
$userdata['user_unread_privmsg'] = ( $userdata['user_new_privmsg'] + $userdata['user_unread_privmsg'] );
unset($_SESSION['CPG_USER']);

//
// Generate page
//

if ($mode == '') {
	define('HEADER_INC', TRUE);
	require_once('header.php');
	OpenTable();
}

$orig_word = array();
$replacement_word = array();
obtain_word_list($orig_word, $replacement_word);

//
// New message
//
$post_new_mesg_url = '<a href="'.getlink('&amp;mode=post').'"><img src="'.$images['post_new'].'" alt="'.$lang['Send_a_new_message'].'" /></a>';

//
// General SQL to obtain messages
//
$sql_tot = "SELECT COUNT(privmsgs_id) AS total FROM ".$prefix."_bbprivmsgs ";
$sql = "SELECT pm.privmsgs_type, pm.privmsgs_id, pm.privmsgs_date, pm.privmsgs_subject, u.user_id, u.username
	FROM ".$prefix."_bbprivmsgs pm, ".$user_prefix."_users u ";
switch ($folder)
{
	case 'inbox':
		$sql_tot .= 'WHERE privmsgs_to_userid = '.$userdata['user_id'].'
			AND ( privmsgs_type =  '.PRIVMSGS_NEW_MAIL.'
				OR privmsgs_type = '.PRIVMSGS_READ_MAIL.'
				OR privmsgs_type = '.PRIVMSGS_UNREAD_MAIL.' )';

		$sql .= 'WHERE pm.privmsgs_to_userid = '.$userdata['user_id'].'
			AND u.user_id = pm.privmsgs_from_userid
			AND ( pm.privmsgs_type =  '.PRIVMSGS_NEW_MAIL.'
				OR pm.privmsgs_type = '.PRIVMSGS_READ_MAIL.'
				OR privmsgs_type = '.PRIVMSGS_UNREAD_MAIL.' )';
		break;

	case 'outbox':
		$sql_tot .= 'WHERE privmsgs_from_userid = '.$userdata['user_id'].'
			AND ( privmsgs_type =  '.PRIVMSGS_NEW_MAIL.'
				OR privmsgs_type = '.PRIVMSGS_UNREAD_MAIL.' )';

		$sql .= 'WHERE pm.privmsgs_from_userid = '.$userdata['user_id'].'
			AND u.user_id = pm.privmsgs_to_userid
			AND ( pm.privmsgs_type =  '.PRIVMSGS_NEW_MAIL.'
				OR privmsgs_type = '.PRIVMSGS_UNREAD_MAIL.' )';
		break;

	case 'sentbox':
		$sql_tot .= 'WHERE privmsgs_from_userid = '.$userdata['user_id'].'
			AND privmsgs_type =	 '.PRIVMSGS_SENT_MAIL;

		$sql .= 'WHERE pm.privmsgs_from_userid = '.$userdata['user_id'].'
			AND u.user_id = pm.privmsgs_to_userid
			AND pm.privmsgs_type =	'.PRIVMSGS_SENT_MAIL;
		break;

	case 'savebox':
		$sql_tot .= 'WHERE ( ( privmsgs_to_userid = '.$userdata['user_id'].'
				AND privmsgs_type = '.PRIVMSGS_SAVED_IN_MAIL.' )
			OR ( privmsgs_from_userid = '.$userdata['user_id'].'
				AND privmsgs_type = '.PRIVMSGS_SAVED_OUT_MAIL.') )';

		$sql .= 'WHERE u.user_id = pm.privmsgs_from_userid
			AND ( ( pm.privmsgs_to_userid = '.$userdata['user_id'].'
				AND pm.privmsgs_type = '.PRIVMSGS_SAVED_IN_MAIL.' )
			OR ( pm.privmsgs_from_userid = '.$userdata['user_id'].'
				AND pm.privmsgs_type = '.PRIVMSGS_SAVED_OUT_MAIL.' ) )';
		break;

	default:
		message_die(GENERAL_MESSAGE, $lang['No_such_folder']);
		break;
}

//
// Show messages over previous x days/months
//
$msg_days = 0;
if ($submit_msgdays && (!empty($_POST['msgdays']) || !empty($_GET['msgdays']))) {
	$msg_days = (!empty($_POST['msgdays'])) ? intval($_POST['msgdays']) : intval($_GET['msgdays']);
	$min_msg_time = gmtime() - ($msg_days * 86400);

	$limit_msg_time_total = " AND privmsgs_date > $min_msg_time";
	$limit_msg_time = " AND pm.privmsgs_date > $min_msg_time ";

	if (!empty($_POST['msgdays'])) {
		$start = 0;
	}
} else {
	$limit_msg_time_total = $limit_msg_time = '';
	$post_days = 0;
}

$sql .= $limit_msg_time." ORDER BY pm.privmsgs_date DESC
LIMIT $start, ".$board_config['topics_per_page'];
$sql_all_tot = $sql_tot;
$sql_tot .= $limit_msg_time_total;

//
// Get messages
//
$result = $db->sql_query($sql_tot);
$pm_total = ($row = $db->sql_fetchrow($result)) ? $row['total'] : 0;

$result = $db->sql_query($sql_all_tot);
$pm_all_total = ($row = $db->sql_fetchrow($result)) ? $row['total'] : 0;

//
// Build select box
//
$previous_days = array(0, 1, 7, 14, 30, 90, 180, 364);
$previous_days_text = array($lang['All_Posts'], $lang['1_Day'], $lang['7_Days'], $lang['2_Weeks'], $lang['1_Month'], $lang['3_Months'], $lang['6_Months'], $lang['1_Year']);

$select_msg_days = '';
for ($i = 0; $i < count($previous_days); $i++) {
	$selected = ( $msg_days == $previous_days[$i] ) ? ' selected="selected"' : '';
	$select_msg_days .= '<option value="'.$previous_days[$i].'"'.$selected.'>'.$previous_days_text[$i].'</option>';
}

//
// Define correct icons
//
switch ($folder)
{
	case 'inbox':
		$l_box_name = $lang['Inbox'];
		break;
	case 'outbox':
		$l_box_name = $lang['Outbox'];
		break;
	case 'savebox':
		$l_box_name = $lang['Savebox'];
		break;
	case 'sentbox':
		$l_box_name = $lang['Sentbox'];
		break;
}
$post_pm = getlink('&amp;mode=post');
$post_pm_img = '<a href="'.$post_pm.'"><img src="'.$images['pm_postmsg'].'" alt="'.$lang['Post_new_pm'].'" /></a>';
$post_pm = '<a href="'.$post_pm.'">'.$lang['Post_new_pm'].'</a>';

//
// Output data for inbox status
//
$l_box_size_status = $inbox_limit_img_length = $inbox_limit_pct = '';
if ($folder != 'outbox') {
	$inbox_limit_pct = ( $board_config['max_'.$folder.'_privmsgs'] > 0 ) ? round(( $pm_all_total / $board_config['max_'.$folder.'_privmsgs'] ) * 100) : 100;
	$inbox_limit_img_length = ( $board_config['max_'.$folder.'_privmsgs'] > 0 ) ? round(( $pm_all_total / $board_config['max_'.$folder.'_privmsgs'] ) * $board_config['privmsg_graphic_length']) : $board_config['privmsg_graphic_length'];
	$inbox_limit_remain = ( $board_config['max_'.$folder.'_privmsgs'] > 0 ) ? $board_config['max_'.$folder.'_privmsgs'] - $pm_all_total : 0;
	$template->assign_block_vars('switch_box_size_notice', array());
	switch ($folder) {
		case 'inbox':
			$l_box_size_status = sprintf($lang['Inbox_size'], $inbox_limit_pct);
			break;
		case 'sentbox':
			$l_box_size_status = sprintf($lang['Sentbox_size'], $inbox_limit_pct);
			break;
		case 'savebox':
			$l_box_size_status = sprintf($lang['Savebox_size'], $inbox_limit_pct);
			break;
	}
}

//
// Dump vars to template
//
$template->assign_vars(array(
	'BOX_NAME' => $l_box_name,
	'INBOX_IMG' => $inbox_img,
	'SENTBOX_IMG' => $sentbox_img,
	'OUTBOX_IMG' => $outbox_img,
	'SAVEBOX_IMG' => $savebox_img,
	'INBOX' => $inbox_url,
	'SENTBOX' => $sentbox_url,
	'OUTBOX' => $outbox_url,
	'SAVEBOX' => $savebox_url,

	'POST_PM_IMG' => $post_pm_img,
	'POST_PM' => $post_pm,

	'INBOX_LIMIT_IMG_WIDTH' => $inbox_limit_img_length,
	'INBOX_LIMIT_PERCENT' => $inbox_limit_pct,

	'BOX_SIZE_STATUS' => $l_box_size_status,

	'T_TD_COLOR2' => $bgcolor4,

	'L_GO' => $lang['Go'],
	'L_INBOX' => $lang['Inbox'],
	'L_OUTBOX' => $lang['Outbox'],
	'L_SENTBOX' => $lang['Sent'],
	'L_SAVEBOX' => $lang['Saved'],
	'L_MARK' => $lang['Mark'],
	'L_FLAG' => $lang['Flag'],
	'L_SUBJECT' => $lang['Subject'],
	'L_DATE' => $lang['Date'],
	'L_DISPLAY_MESSAGES' => $lang['Display_messages'],
	'L_FROM_OR_TO' => ( $folder == 'inbox' || $folder == 'savebox' ) ? $lang['From'] : $lang['To'],
	'L_MARK_ALL' => $lang['Mark_all'],
	'L_UNMARK_ALL' => $lang['Unmark_all'],
	'L_DELETE_MARKED' => $lang['Delete_marked'],
	'L_DELETE_ALL' => $lang['Delete_all'],
	'L_SAVE_MARKED' => $lang['Save_marked'],

	'S_PRIVMSGS_ACTION' => getlink('&amp;folder='.$folder),
	'S_HIDDEN_FIELDS' => '',
	'S_POST_NEW_MSG' => $post_new_mesg_url,
	'S_SELECT_MSG_DAYS' => $select_msg_days
	)
);

//
// Okay, let's build the correct folder
//
$result = $db->sql_query($sql);
if ($row = $db->sql_fetchrow($result)) {
	$i = 0;
	do {
		$privmsg_id = $row['privmsgs_id'];

		$flag = $row['privmsgs_type'];

		$icon_flag = ( $flag == PRIVMSGS_NEW_MAIL || $flag == PRIVMSGS_UNREAD_MAIL ) ? $images['pm_unreadmsg'] : $images['pm_readmsg'];
		$icon_flag_alt = ( $flag == PRIVMSGS_NEW_MAIL || $flag == PRIVMSGS_UNREAD_MAIL ) ? $lang['Unread_message'] : $lang['Read_message'];

		$msg_userid = $row['user_id'];
		$msg_username = $row['username'];
		$msg_subject = $row['privmsgs_subject'];

		$u_from_user_profile = getlink('Your_Account&amp;profile='.$msg_userid);

		if (count($orig_word)) {
			$msg_subject = preg_replace($orig_word, $replacement_word, $msg_subject);
		}
		$u_subject = getlink('&amp;folder='.$folder.'&amp;mode=read&amp;p='.$privmsg_id);
		$msg_date = create_date($board_config['default_dateformat'], $row['privmsgs_date']);

		if ($flag == PRIVMSGS_NEW_MAIL && $folder == 'inbox') {
			$msg_subject = '<b>'.$msg_subject.'</b>';
			$msg_date = '<b>'.$msg_date.'</b>';
			$msg_username = '<b>'.$msg_username.'</b>';
		}

		$row_color = ( !($i % 2) ) ? $bgcolor2 : $bgcolor1;
		$row_class = ( !($i % 2) ) ? 'row1' : 'row2';
		$i++;

		$template->assign_block_vars('listrow', array(
			'ROW_COLOR' => $row_color,
			'ROW_CLASS' => $row_class,
			'FROM' => $msg_username,
			'SUBJECT' => $msg_subject,
			'DATE' => $msg_date,
			'PRIVMSG_FOLDER_IMG' => $icon_flag,

			'L_PRIVMSG_FOLDER_ALT' => $icon_flag_alt,

			'S_MARK_ID' => $privmsg_id,

			'U_READ' => $u_subject,
			'U_FROM_USER_PROFILE' => $u_from_user_profile)
		);
	}
	while ($row = $db->sql_fetchrow($result));

	$template->assign_vars(array(
		'PAGINATION' => pm_pagination('Private_Messages&amp;folder='.$folder, $pm_total, $board_config['topics_per_page'], $start),
		'PAGE_NUMBER' => sprintf($lang['Page_of'], ( floor( $start / $board_config['topics_per_page'] ) + 1 ), ceil( $pm_total / $board_config['topics_per_page'] )),

		'L_GOTO_PAGE' => $lang['Goto_page'])
	);
} else {
	$template->assign_vars(array(
		'L_NO_MESSAGES' => $lang['No_messages_folder'],

		'PAGINATION' => '',
		'PAGE_NUMBER' => sprintf($lang['Page_of'], 1, 1))
	);
	$template->assign_block_vars('switch_no_messages', array() );
}
if ($mode == '') {
	$template->set_filenames(array('body' => 'private_msgs/index_body.html'));
	$template->display('body');
	CloseTable();
}
