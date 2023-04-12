<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/modules/Private_Messages/read.php,v $
  $Revision: 9.24 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:28 $
**********************************************/
if (!defined('CPG_NUKE') && !defined('IN_PHPBB')) { exit; }
global $pagetitle;

	// Read a PM
	if (!empty($_GET[POST_POST_URL])) {
		$privmsgs_id = intval($_GET[POST_POST_URL]);
	} else {
		message_die(GENERAL_ERROR, $lang['No_post_id']);
	}

	//
	// SQL to pull appropriate message, prevents nosey people
	// reading other peoples messages ... hopefully!
	//
	switch( $folder ) {
		case 'inbox':
			$pm_sql_user = "AND pm.privmsgs_to_userid = ".$userdata['user_id']."
				AND ( pm.privmsgs_type = ".PRIVMSGS_READ_MAIL."
					OR pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL."
					OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
			break;
		case 'outbox':
			$pm_sql_user = "AND pm.privmsgs_from_userid =  ".$userdata['user_id']."
				AND ( pm.privmsgs_type = ".PRIVMSGS_NEW_MAIL."
					OR pm.privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." ) ";
			break;
		case 'sentbox':
			$pm_sql_user = "AND pm.privmsgs_from_userid =  ".$userdata['user_id']."
				AND pm.privmsgs_type = ".PRIVMSGS_SENT_MAIL;
			break;
		case 'savebox':
			$pm_sql_user = "AND ( ( pm.privmsgs_to_userid = ".$userdata['user_id']."
					AND pm.privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL." )
				OR ( pm.privmsgs_from_userid = ".$userdata['user_id']."
					AND pm.privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL." )
				)";
			break;
		default:
			message_die(GENERAL_ERROR, $lang['No_such_folder']);
			break;
	}

	//
	// Major query obtains the message ...
	//
	$result = $db->sql_query("SELECT u.username AS username_1, u.user_id AS user_id_1, u2.username AS username_2, u2.user_id AS user_id_2, u.user_posts, u.user_from, u.user_website, u.user_email, u.user_icq, u.user_aim, u.user_yim, u.user_skype, u.user_regdate, u.user_msnm, u.user_viewemail, u.user_rank, u.user_sig, u.user_avatar, pm.*, pmt.privmsgs_text
		FROM ".$prefix."_bbprivmsgs pm, ".$prefix."_bbprivmsgs_text pmt, ".$user_prefix."_users u, ".$user_prefix."_users u2
		WHERE pm.privmsgs_id = $privmsgs_id
			AND pmt.privmsgs_text_id = pm.privmsgs_id
			$pm_sql_user
			AND u.user_id = pm.privmsgs_from_userid
			AND u2.user_id = pm.privmsgs_to_userid");

	//
	// Did the query return any data?
	//
	if (!($privmsg = $db->sql_fetchrow($result))) {
		url_redirect(getlink("&folder=$folder"));
	}

	$privmsg_id = $privmsg['privmsgs_id'];

	//
	// Is this a new message in the inbox? If it is then save
	// a copy in the posters sent box
	//
	if (($privmsg['privmsgs_type'] == PRIVMSGS_NEW_MAIL || $privmsg['privmsgs_type'] == PRIVMSGS_UNREAD_MAIL) && $folder == 'inbox')
	{
		// Update appropriate counter
		switch ($privmsg['privmsgs_type'])
		{
			case PRIVMSGS_NEW_MAIL:
				$sql = "user_new_privmsg = user_new_privmsg - 1";
				break;
			case PRIVMSGS_UNREAD_MAIL:
				$sql = "user_unread_privmsg = user_unread_privmsg - 1";
				break;
		}
		$db->sql_query("UPDATE ".$user_prefix."_users SET $sql WHERE user_id = ".$userdata['user_id']);
		unset($_SESSION['CPG_USER']);
		$db->sql_query("UPDATE ".$prefix."_bbprivmsgs SET privmsgs_type = ".PRIVMSGS_READ_MAIL." WHERE privmsgs_id = ".$privmsg['privmsgs_id']);
		// Check to see if the poster has a 'full' sent box
		$result = $db->sql_query("SELECT COUNT(privmsgs_id) AS sent_items, MIN(privmsgs_date) AS oldest_post_time
			FROM ".$prefix."_bbprivmsgs
			WHERE privmsgs_type = ".PRIVMSGS_SENT_MAIL."
				AND privmsgs_from_userid = ".$privmsg['privmsgs_from_userid']);

		$sql_priority = ( SQL_LAYER == 'mysql' ) ? 'LOW_PRIORITY' : '';

		if ( $sent_info = $db->sql_fetchrow($result) )
		{
			if ( $sent_info['sent_items'] >= $board_config['max_sentbox_privmsgs'] )
			{
				$result = $db->sql_query("SELECT privmsgs_id FROM ".$prefix."_bbprivmsgs
					WHERE privmsgs_type = ".PRIVMSGS_SENT_MAIL."
						AND privmsgs_date = ".$sent_info['oldest_post_time']."
						AND privmsgs_from_userid = ".$privmsg['privmsgs_from_userid']);
				$old_privmsgs_id = $db->sql_fetchrow($result);
				$old_privmsgs_id = $old_privmsgs_id['privmsgs_id'];
				$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs WHERE privmsgs_id = $old_privmsgs_id");
				$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs_text WHERE privmsgs_text_id = $old_privmsgs_id");
			}
		}

		//
		// This makes a copy of the post and stores it as a SENT message from the sendee. Perhaps
		// not the most DB friendly way but a lot easier to manage, besides the admin will be able to
		// set limits on numbers of storable posts for users ... hopefully!
		//
		$subject = $privmsg['privmsgs_subject'];
		$message = $privmsg['privmsgs_text'];
		$db->sql_query("INSERT $sql_priority INTO ".$prefix."_bbprivmsgs (privmsgs_type, privmsgs_subject, privmsgs_from_userid, privmsgs_to_userid, privmsgs_date, privmsgs_ip, privmsgs_enable_html, privmsgs_enable_bbcode, privmsgs_enable_smilies, privmsgs_attach_sig)
			VALUES (".PRIVMSGS_SENT_MAIL.", '".Fix_Quotes($subject)."', ".$privmsg['privmsgs_from_userid'].", ".$privmsg['privmsgs_to_userid'].", ".$privmsg['privmsgs_date'].", ".$db->binary_safe($privmsg['privmsgs_ip']).", ".$privmsg['privmsgs_enable_html'].", ".$privmsg['privmsgs_enable_bbcode'].", ".$privmsg['privmsgs_enable_smilies'].", " .	$privmsg['privmsgs_attach_sig'].")");
		$privmsg_sent_id = $db->sql_nextid('privmsgs_id');
		unset($subject);
		$db->sql_query("INSERT $sql_priority INTO ".$prefix."_bbprivmsgs_text (privmsgs_text_id, privmsgs_text)
			VALUES ($privmsg_sent_id, '".Fix_Quotes($message)."')");
		unset($message);
	}

	//
	// Pick a folder, any folder, so long as it's one below ...
	//
	$post_urls = array(
		'post' => getlink("&amp;mode=post"),
		'reply' => getlink("&amp;mode=reply&amp;p=$privmsg_id"),
		'quote' => getlink("&amp;mode=quote&amp;p=$privmsg_id"),
		'edit' => getlink("&amp;mode=edit&amp;p=$privmsg_id")
	);
	$post_icons = array(
		'post_img' => '<a href="'.$post_urls['post'].'"><img src="'.$images['pm_postmsg'].'" alt="'.$lang['Post_new_pm'].'" /></a>',
		'post' => '<a href="'.$post_urls['post'].'">'.$lang['Post_new_pm'].'</a>',
		'reply_img' => '<a href="'.$post_urls['reply'].'"><img src="'.$images['pm_replymsg'].'" alt="'.$lang['Post_reply_pm'].'" /></a>',
		'reply' => '<a href="'.$post_urls['reply'].'">'.$lang['Post_reply_pm'].'</a>',
		'quote_img' => '<a href="'.$post_urls['quote'].'"><img src="'.$images['pm_quotemsg'].'" alt="'.$lang['Post_quote_pm'].'" /></a>',
		'quote' => '<a href="'.$post_urls['quote'].'">'.$lang['Post_quote_pm'].'</a>',
		'edit_img' => '<a href="'.$post_urls['edit'].'"><img src="'.$images['pm_editmsg'].'" alt="'.$lang['Edit_pm'].'" /></a>',
		'edit' => '<a href="'.$post_urls['edit'].'">'.$lang['Edit_pm'].'</a>'
	);

	if ( $folder == 'inbox' )
	{
		$post_img = $post_icons['post_img'];
		$reply_img = $post_icons['reply_img'];
		$quote_img = $post_icons['quote_img'];
		$edit_img = '';
		$post = $post_icons['post'];
		$reply = $post_icons['reply'];
		$quote = $post_icons['quote'];
		$edit = '';
		$l_box_name = $lang['Inbox'];
	}
	else if ( $folder == 'outbox' )
	{
		$post_img = $post_icons['post_img'];
		$reply_img = '';
		$quote_img = '';
		$edit_img = $post_icons['edit_img'];
		$post = $post_icons['post'];
		$reply = '';
		$quote = '';
		$edit = $post_icons['edit'];
		$l_box_name = $lang['Outbox'];
	}
	else if ( $folder == 'savebox' )
	{
		if ( $privmsg['privmsgs_type'] == PRIVMSGS_SAVED_IN_MAIL )
		{
			$post_img = $post_icons['post_img'];
			$reply_img = $post_icons['reply_img'];
			$quote_img = $post_icons['quote_img'];
			$edit_img = '';
			$post = $post_icons['post'];
			$reply = $post_icons['reply'];
			$quote = $post_icons['quote'];
			$edit = '';
		}
		else
		{
			$post_img = $post_icons['post_img'];
			$reply_img = '';
			$quote_img = '';
			$edit_img = '';
			$post = $post_icons['post'];
			$reply = '';
			$quote = '';
			$edit = '';
		}
		$l_box_name = $lang['Saved'];
	}
	else if ( $folder == 'sentbox' )
	{
		$post_img = $post_icons['post_img'];
		$reply_img = '';
		$quote_img = '';
		$edit_img = '';
		$post = $post_icons['post'];
		$reply = '';
		$quote = '';
		$edit = '';
		$l_box_name = $lang['Sent'];
	}

	$s_hidden_fields = '<input type="hidden" name="mark[]" value="'.$privmsgs_id.'" />';

	$pagetitle .= ' '._BC_DELIM.' '.$lang['Read_pm'];
	define('HEADER_INC', TRUE);
	require_once('header.php');

	$template->assign_vars(array(
		'INBOX_IMG' => $inbox_img,
		'SENTBOX_IMG' => $sentbox_img,
		'OUTBOX_IMG' => $outbox_img,
		'SAVEBOX_IMG' => $savebox_img,
		'INBOX' => $inbox_url,

		'POST_PM_IMG' => $post_img,
		'REPLY_PM_IMG' => $reply_img,
		'EDIT_PM_IMG' => $edit_img,
		'QUOTE_PM_IMG' => $quote_img,
		'POST_PM' => $post,
		'REPLY_PM' => $reply,
		'EDIT_PM' => $edit,
		'QUOTE_PM' => $quote,

		'SENTBOX' => $sentbox_url,
		'OUTBOX' => $outbox_url,
		'SAVEBOX' => $savebox_url,

		'BOX_NAME' => $l_box_name,

		'L_MESSAGE' => $lang['Message'],
		'L_INBOX' => $lang['Inbox'],
		'L_OUTBOX' => $lang['Outbox'],
		'L_SENTBOX' => $lang['Sent'],
		'L_SAVEBOX' => $lang['Saved'],
		'L_FLAG' => $lang['Flag'],
		'L_SUBJECT' => $lang['Subject'],
		'L_POSTED' => $lang['Posted'],
		'L_DATE' => $lang['Date'],
		'L_FROM' => $lang['From'],
		'L_TO' => $lang['To'],
		'L_SAVE_MSG' => $lang['Save_message'],
		'L_DELETE_MSG' => $lang['Delete_message'],

		'S_PRIVMSGS_ACTION' => getlink('&amp;folder='.$folder),
		'S_HIDDEN_FIELDS' => $s_hidden_fields)
	);

	$username_from = $privmsg['username_1'];
	$user_id_from = $privmsg['user_id_1'];
	$username_to = $privmsg['username_2'];
	$user_id_to = $privmsg['user_id_2'];
	$post_date = create_date($board_config['default_dateformat'], $privmsg['privmsgs_date']);

	$temp_url = getlink('Your_Account&amp;profile='.$user_id_from);
	$profile_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_profile'].'" alt="'.$lang['Read_profile'].'" title="'.$lang['Read_profile'].'" /></a>';
	$profile = '<a href="'.$temp_url.'">'.$lang['Read_profile'].'</a>';

	$temp_url = getlink('&amp;mode=post&amp;u='.$user_id_from);
	$pm_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_pm'].'" alt="'.$lang['Send_private_message'].'" title="'.$lang['Send_private_message'].'" /></a>';
	$pm = '<a href="'.$temp_url.'">'.$lang['Send_private_message'].'</a>';

	if (!empty($privmsg['user_viewemail']) || $userdata['user_level'] == ADMIN)
	{
		$email_uri = ( $board_config['board_email_form'] ) ? getlink('Forums&amp;file=profile&amp;mode=email&amp;u='.$user_id_from) : 'mailto:'.$privmsg['user_email'];

		$email_img = '<a href="'.$email_uri.'"><img src="'.$images['icon_email'].'" alt="'.$lang['Send_email'].'" title="'.$lang['Send_email'].'" /></a>';
		$email = '<a href="'.$email_uri.'">'.$lang['Send_email'].'</a>';
	}
	else
	{
		$email_img = '';
		$email = '';
	}

	$www_img = ( $privmsg['user_website'] ) ? '<a href="'.$privmsg['user_website'].'" target="_userwww"><img src="'.$images['icon_www'].'" alt="'.$lang['Visit_website'].'" title="'.$lang['Visit_website'].'" /></a>' : '';
	$www = ( $privmsg['user_website'] ) ? '<a href="'.$privmsg['user_website'].'" target="_userwww">'.$lang['Visit_website'].'</a>' : '';

	if ( !empty($privmsg['user_icq']) )
	{
		$icq_status_img = '<a href="http://wwp.icq.com/'.$privmsg['user_icq'].'#pager"><img src="http://web.icq.com/whitepages/online?icq='.$privmsg['user_icq'].'&img=5" width="18" height="18" /></a>';
		$icq_img = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$privmsg['user_icq'].'"><img src="'.$images['icon_icq'].'" alt="'.$lang['ICQ'].'" title="'.$lang['ICQ'].'" /></a>';
		$icq =	'<a href="http://wwp.icq.com/scripts/search.dll?to='.$privmsg['user_icq'].'">'.$lang['ICQ'].'</a>';
	}
	else
	{
		$icq_status_img = '';
		$icq_img = '';
		$icq = '';
	}

	$aim_img = ( $privmsg['user_aim'] ) ? '<a href="aim:goim?screenname='.$privmsg['user_aim'].'&amp;message=Hello+Are+you+there?"><img src="'.$images['icon_aim'].'" alt="'.$lang['AIM'].'" title="'.$lang['AIM'].'" /></a>' : '';
	$aim = ( $privmsg['user_aim'] ) ? '<a href="aim:goim?screenname='.$privmsg['user_aim'].'&amp;message=Hello+Are+you+there?">'.$lang['AIM'].'</a>' : '';

	//$temp_url = getlink("Your_Account&amp;profile=$user_id_from");
	$temp_url = 'http://members.msn.com/'.$privmsg['user_msnm'];
	$msn_img = ( $privmsg['user_msnm'] ) ? '<a href="'.$temp_url.'"><img src="'.$images['icon_msnm'].'" alt="'.$lang['MSNM'].'" title="'.$lang['MSNM'].'" /></a>' : '';
	$msn = ( $privmsg['user_msnm'] ) ? '<a href="'.$temp_url.'">'.$lang['MSNM'].'</a>' : '';

	$yim_img = ( $privmsg['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$privmsg['user_yim'].'&amp;.src=pg"><img src="'.$images['icon_yim'].'" alt="'.$lang['YIM'].'" title="'.$lang['YIM'].'" /></a>' : '';
	$yim = ( $privmsg['user_yim'] ) ? '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$privmsg['user_yim'].'&amp;.src=pg">'.$lang['YIM'].'</a>' : '';

	$skype_img = ( $privmsg['user_skype'] ) ? '<a href="callto://'.$privmsg['user_skype'].'"><img src="'.$images['icon_skype'].'" alt="Skype" title="Skype" /></a>' : '';
	$skype = ( $privmsg['user_skype'] ) ? '<a href="callto://'.$privmsg['user_skype'].'">Skype</a>' : '';

	$temp_url = getlink("Forums&amp;file=search&amp;search_author=".urlencode($username_from)."&amp;showresults=posts");
	$search_img = '<a href="'.$temp_url.'"><img src="'.$images['icon_search'].'" alt="'.$lang['Search_user_posts'].'" title="'.$lang['Search_user_posts'].'" /></a>';
	$search = '<a href="'.$temp_url.'">'.$lang['Search_user_posts'].'</a>';

	//
	// Processing of post
	//
	$post_subject = $privmsg['privmsgs_subject'];
	$private_message = $privmsg['privmsgs_text'];

	if ( $board_config['allow_sig'] ) {
		$user_sig = ( $privmsg['privmsgs_from_userid'] == $userdata['user_id'] ) ? $userdata['user_sig'] : $privmsg['user_sig'];
	} else {
		$user_sig = '';
	}

	//
	// If the board has HTML off but the post has HTML
	// on then we process it, else leave it alone
	//
	if (!$board_config['allow_html'] || !$userdata['user_allowhtml'])
	{
		if ($user_sig != '')
		{
			$user_sig = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $user_sig);
		}

		if ( $privmsg['privmsgs_enable_html'] )
		{
			$private_message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $private_message);
		}
	}

	if ( $user_sig != '' && $privmsg['privmsgs_attach_sig'] )
	{
		require_once('includes/nbbcode.php');
		$user_sig = ( $board_config['allow_bbcode'] ) ? decode_bbcode($user_sig, 1, false) : preg_replace('/\:[0-9a-z\:]+\]/si', ']', $user_sig);
	}

	$private_message = ( $board_config['allow_bbcode'] ) ? decode_bbcode($private_message, 1, false) : preg_replace('/\:[0-9a-z\:]+\]/si', ']', $private_message);

	$private_message = make_clickable($private_message);

	if ( $privmsg['privmsgs_attach_sig'] && $user_sig != '' )
	{
		$private_message .= '<br /><br />_________________<br />'.make_clickable($user_sig);
	}

	$orig_word = array();
	$replacement_word = array();
	obtain_word_list($orig_word, $replacement_word);

	if ( count($orig_word) )
	{
		$post_subject = preg_replace($orig_word, $replacement_word, $post_subject);
		$private_message = preg_replace($orig_word, $replacement_word, $private_message);
	}

	if ( $board_config['allow_smilies'] && $privmsg['privmsgs_enable_smilies'] )
	{
		$private_message = set_smilies($private_message);
	}

//	  $private_message = str_replace("\n", '<br />', $private_message);

	//
	// Dump it to the templating engine
	// we set both VAR_IMG and VAR so theme designers can
	// use VAR_IMG to display image links
	// otherwise use VAR to display plain links
	//
	$template->assign_vars(array(
		'MESSAGE_TO' => $username_to,
		'MESSAGE_FROM' => $username_from,
		//'RANK_IMAGE' => $rank_image,
		//'POSTER_JOINED' => $poster_joined,
		//'POSTER_POSTS' => $poster_posts,
		//'POSTER_FROM' => $poster_from,
		//'POSTER_AVATAR' => $poster_avatar,
		'POST_SUBJECT' => $post_subject,
		'POST_DATE' => $post_date,
		'MESSAGE' => $private_message,

		'PM_IMG' => $pm_img,
		'PM' => $pm,
		'PROFILE_IMG' => $profile_img,
		'PROFILE' => $profile,
		'SEARCH_IMG' => $search_img,
		'SEARCH' => $search,
		'EMAIL_IMG' => $email_img,
		'EMAIL' => $email,
		'WWW_IMG' => $www_img,
		'WWW' => $www,
		'ICQ_STATUS_IMG' => $icq_status_img,
		'ICQ_IMG' => $icq_img,
		'ICQ' => $icq,
		'AIM_IMG' => $aim_img,
		'AIM' => $aim,
		'MSN_IMG' => $msn_img,
		'MSN' => $msn,
		'YIM_IMG' => $yim_img,
		'YIM' => $yim,
		'SKYPE_IMG' => $skype_img,
		'SKYPE' => $skype
		)
	);

	// ROPM QUICK REPLY
	//if ( $board_config['ropm_quick_reply'] && $privmsg['privmsgs_from_userid'] != $userdata['user_id'] )
	if ( $board_config['ropm_quick_reply']) {
		require_once('includes/nbbcode.php');

		$last_msg = $privmsg['privmsgs_text'];
		$last_msg = '[quote="'.$privmsg['username_1'].'"]'.$last_msg.'[/quote]';
		$last_msg = str_replace('\\', '\\\\', $last_msg); //'
		$last_msg = str_replace('"', '&quot;', $last_msg);
		$last_msg = str_replace(chr(13), '', $last_msg);
		$s_hidden_fields = '
<input type="hidden" name="folder" value="'.$folder.'" />
<input type="hidden" name="mode" value="post" />
<input type="hidden" name="username" value="'.$privmsg['username_1'].'" />';

		$template->assign_block_vars('quickreply', array(
			'POST_ACTION' => getlink(),
			'S_HIDDEN_FIELDS' => $s_hidden_fields,
			'SUBJECT' => ( ( !preg_match('/^Re:/', $privmsg['privmsgs_subject']) ) ? 'Re: ' : '' ).$privmsg['privmsgs_subject'],
			'S_HTML_CHECKED' => ( !$userdata['user_allowhtml'] ) ? ' checked="checked"' : '',
			'S_BBCODE_CHECKED' => ( !$userdata['user_allowbbcode'] ) ? ' checked="checked"' : '',
			'S_SMILIES_CHECKED' => ( !$userdata['user_allowsmile'] ) ? ' checked="checked"' : '',
			'S_QREPLY_MSG' => $last_msg,
			'S_SIG_CHECKED' => ( $userdata['user_attachsig'] ) ? ' checked="checked"' : '',
			'S_HTMLCB' => $board_config['allow_html'],
			'S_BBCODECB' => $board_config['allow_bbcode'],
			'S_SMILIESCB' => $board_config['allow_smilies'],

			'BBCODEBUTTONS' => ( $board_config['ropm_quick_reply_bbc'] ) ? bbcode_table('message', 'qreply', 1) : '',
			'SMILIES'		=> ( $board_config['allow_smilies'] ) ? smilies_table('onerow', 'message', 'qreply') : ''
			));

		$template->assign_vars(array(
			'L_EMPTY_MESSAGE' => $lang['Empty_message'],
			'L_PREVIEW' => $lang['Preview'],
			'L_SUBMIT' => $lang['Submit'],
			'L_CANCEL' => $lang['Cancel'],
			'L_SUBJECT' => $lang['Subject'],
			'L_MESSAGE' => $lang['Message'],
			'L_OPTIONS' => $lang['Options'],
			'L_ATTACH_SIGNATURE' => $lang['Attach_signature'],
			'L_DISABLE_HTML' => $lang['Disable_HTML_post'],
			'L_DISABLE_BBCODE' => $lang['Disable_BBCode_post'],
			'L_DISABLE_SMILIES' => $lang['Disable_Smilies_post'],
			'L_QUOTE_SELECTED' => $lang['PMQR_QuoteSelelected'],
			'L_NO_TEXT_SELECTED' => $lang['PMQR_QuoteSelelectedEmpty'],
			'L_ERROR' => $lang['Error'],
			'L_QUOTE_LAST_MESSAGE' => $lang['PMQR_Quick_quote'],
			'L_QUICK_REPLY' => $lang['PMQR_Quick_Reply'],
		));
}
$template->set_filenames(array('body' => 'private_msgs/read_body.html'));
$template->display('body');
