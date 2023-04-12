<?php
/***************************************************************************
 *				   usercp_email.php
 *				  -------------------
 *	 begin		  : Saturday, Feb 13, 2001
 *	 copyright		  : (C) 2001 The phpBB Group
 *	 email		  : support@phpbb.com
 *
 *	 Modifications made by CPG Dev Team http://cpgnuke.com
 *	 Last modification notes:
 *
 *	 $Id: usercp_email.php,v 9.5 2007/12/12 12:54:20 nanocaiordo Exp $
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
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

// Is send through board enabled? No, return to index
if (!$board_config['board_email_form']) {
	url_redirect(getlink());
}

if ( !empty($_GET[POST_USERS_URL]) || !empty($_POST[POST_USERS_URL]) ) {
	$user_id = ( !empty($_GET[POST_USERS_URL]) ) ? intval($_GET[POST_USERS_URL]) : intval($_POST[POST_USERS_URL]);
} else {
	message_die(GENERAL_MESSAGE, $lang['No_user_specified']);
}

if (!is_user()) {
	url_redirect(getlink('Your_Account'), true);
}

$error = FALSE;

if ($row = $db->sql_ufetchrow("SELECT username, user_email, user_viewemail, user_lang FROM " . USERS_TABLE . " WHERE user_id=$user_id", SQL_ASSOC)) {
	$username = $row['username'];
	$user_email = $row['user_email'];
	$user_lang = $row['user_lang'];

	if ( $row['user_viewemail'] || $userdata['user_level'] == ADMIN ) {
		if ( gmtime() - $userdata['user_emailtime'] < $board_config['flood_interval'] ) {
			message_die(GENERAL_MESSAGE, $lang['Flood_email_limit']);
		}
		if ( isset($_POST['submit']) ) {
			$error = FALSE;
			if ( !empty($_POST['subject']) ) {
				$subject = trim($_POST['subject']);
			} else {
				$error = TRUE;
				$error_msg = ( !empty($error_msg) ) ? $error_msg.'<br />'.$lang['Empty_subject_email'] : $lang['Empty_subject_email'];
			}
			if ( !empty($_POST['message']) ) {
				$message = trim($_POST['message']);
			} else {
				$error = TRUE;
				$error_msg = ( !empty($error_msg) ) ? $error_msg.'<br />'.$lang['Empty_message_email'] : $lang['Empty_message_email'];
			}
			if ( !$error ) {
				$sql = "UPDATE " . USERS_TABLE . " SET user_emailtime=".gmtime()." WHERE user_id=".$userdata['user_id'];
				$db->sql_query($sql);
					include("includes/phpBB/emailer.php");
					$emailer = new emailer();
					$emailer->from($userdata['user_email']);
					$emailer->replyto($userdata['user_email']);
					$email_headers = 'X-AntiAbuse: Board servername - '.trim($board_config['server_name'])."\n";
					$email_headers .= 'X-AntiAbuse: User_id - '.$userdata['user_id']."\n";
					$email_headers .= 'X-AntiAbuse: Username - '.$userdata['username']."\n";
					$email_headers .= 'X-AntiAbuse: User IP - '.decode_ip($user_ip)."\n";
					$emailer->use_template('profile_send_email', $user_lang);
					$emailer->email_address($user_email);
					$emailer->set_subject($subject);
					$emailer->extra_headers($email_headers);
					$emailer->assign_vars(array(
						'SITENAME' => $board_config['sitename'],
						'BOARD_EMAIL' => $board_config['board_email'],
						'FROM_USERNAME' => $userdata['username'],
						'TO_USERNAME' => $username,
						'MESSAGE' => $message)
					);
					$emailer->send();
					$emailer->reset();
				if ( !empty($_POST['cc_email']) ) {
						$emailer->from($userdata['user_email']);
						$emailer->replyto($userdata['user_email']);
						$emailer->use_template('profile_send_email');
						$emailer->email_address($userdata['user_email']);
						$emailer->set_subject($subject);
						$emailer->assign_vars(array(
							'SITENAME' => $board_config['sitename'],
							'BOARD_EMAIL' => $board_config['board_email'],
							'FROM_USERNAME' => $userdata['username'],
							'TO_USERNAME' => $username,
							'MESSAGE' => $message)
						);
						$emailer->send();
						$emailer->reset();
					}
					url_refresh(getlink());
					$message = $lang['Email_sent'] . '<br /><br />' . sprintf($lang['Click_return_index'],	'<a href="' . getlink() . '">', '</a>');
					message_die(GENERAL_MESSAGE, $message);
				}
				}
		$page_title = $lang['Send_email_msg'];
		include('includes/phpBB/page_header.php');
		make_jumpbox('viewforum');
		if ( $error ) {
			$template->set_filenames(array('reg_header' => 'forums/error_body.html'));
			$template->assign_vars(array('ERROR_MESSAGE' => $error_msg));
			$template->assign_var_from_handle('ERROR_BOX', 'reg_header');
		}
		$template->assign_vars(array(
			'USERNAME' => $username,

			'S_HIDDEN_FIELDS' => '',
			'S_POST_ACTION' => getlink("&amp;file=profile&amp;mode=email&amp;".POST_USERS_URL."=$user_id"),

			'L_SEND_EMAIL_MSG' => $lang['Send_email_msg'],
			'L_RECIPIENT' => $lang['Recipient'],
			'L_SUBJECT' => $lang['Subject'],
			'L_MESSAGE_BODY' => $lang['Message_body'],
			'L_MESSAGE_BODY_DESC' => $lang['Email_message_desc'],
			'L_EMPTY_SUBJECT_EMAIL' => $lang['Empty_subject_email'],
			'L_EMPTY_MESSAGE_EMAIL' => $lang['Empty_message_email'],
			'L_OPTIONS' => $lang['Options'],
			'L_CC_EMAIL' => $lang['CC_email'],
			'L_SPELLCHECK' => $lang['Spellcheck'],
			'L_SEND_EMAIL' => $lang['Send_email'],
			'L_GO' => $lang['Go'])
		);

		$template->set_filenames(array('body' => 'forums/profile_send_email.html'));
		include('includes/phpBB/page_tail.php');
	} else {
		message_die(GENERAL_MESSAGE, $lang['User_prevent_email']);
	}
} else {
	message_die(GENERAL_MESSAGE, $lang['User_not_exist']);
}
