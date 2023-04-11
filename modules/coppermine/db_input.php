<?php
/***************************************************************************
    Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');
$K = \Dragonfly::getKernel();

function check_comment($str)
{
	global $CONFIG;

	$ercp = array('/\S{' . ($CONFIG['max_com_wlength'] + 1) . ',}/i');
	if ($CONFIG['filter_bad_words']) {
//		$ercp[] = '/' . ($word[0] == '*' ? '': '\b') . str_replace('*', '', $word) . ($word[(strlen($word)-1)] == '*' ? '': '\b') . '/i';
	}

	if (strlen($str) > $CONFIG['max_com_size']) $str = substr($str, 0, ($CONFIG['max_com_size'] -3)) . '...';
	$str = preg_replace($ercp, '(...)', $str);
	return $str;
}

$event = $_POST->txt('event') ?: $_GET->txt('event');
if (!$event) {
	cpg_error(PARAM_MISSING);
}

//$event = isset($_POST['event']) ? $_POST['event'] : NULL;
switch ($event) {

	// Comment update

	case 'comment_update':
		if (!$USER_DATA['can_post_comments']) {
			cpg_error(PERM_DENIED, 403);
		}
		// variable sanitation 8/27/2004 11:51PM
		$msg_id = $_POST->uint('msg_id') ?: cpg_error(PARAM_MISSING);
		$msg_body = (!empty($_POST['msg_body'])) ? Fix_Quotes($_POST['msg_body'], true): cpg_error(ERR_COMMENT_EMPTY);
		$msg_body = check_comment($msg_body);
		if (is_user()) {
			$update = "UPDATE {$CONFIG['TABLE_COMMENTS']} SET msg_body='{$msg_body}' WHERE msg_id={$msg_id} AND author_id = " . is_user();
		} else {
			$msg_author = (!empty($_POST['msg_author'])) ? Fix_Quotes($_POST['msg_author'], true): cpg_error(EMPTY_NAME_OR_COM);
			check_words($msg_author);
			$update = "UPDATE {$CONFIG['TABLE_COMMENTS']} SET msg_body='{$msg_body}', msg_author='{$msg_author}' WHERE msg_id={$msg_id}";
			if (!USER_IS_ADMIN) {
				$update .= " AND author_id = 0 AND author_md5_id ='" . md5(session_id()) . "'";
			}
		}
		$db->query($update);
		$comment_data = $db->uFetchRow("SELECT pid FROM {$CONFIG['TABLE_COMMENTS']} WHERE msg_id={$msg_id}");
		if ($comment_data) {
			$redirect = URL::index("&file=displayimage&pid={$comment_data[0]}");
			if (!USER_IS_ADMIN && $CONFIG['comment_email_notification'] && !can_admin($module_name)) {
				\Dragonfly\Email::send(
					$mailer_message,
					REVIEW_TITLE,
					"Comment Updated {$msg_body}\n\r ".COM_ADDED." @ http://{$K->CFG['server']['domain']}{$K->CFG['server']['path']}{$redirect}"
				);
			}
		} else {
			$redirect = URL::index();
		}
		URL::redirect($redirect);
		break;

	// Comment

	case 'comment':
		if (!$USER_DATA['can_post_comments']) {
			cpg_error(PERM_DENIED, 403);
		}

		// variable sanitation 8/28/2004 12:06AM
		$pid = $_POST->uint('pid') ?: cpg_error(PARAM_MISSING);
		$msg_body = (!empty($_POST['msg_body'])) ? strip_tags($_POST['msg_body']): cpg_error(ERR_COMMENT_EMPTY);
		$msg_body = check_comment($msg_body);
		$album_data = $db->uFetchRow("SELECT comments FROM {$CONFIG['TABLE_PICTURES']}, {$CONFIG['TABLE_ALBUMS']} WHERE {$CONFIG['TABLE_PICTURES']}.aid = {$CONFIG['TABLE_ALBUMS']}.aid AND pid={$pid}");
		if (!$album_data) {
			cpg_error(NON_EXIST_AP, 404);
		}
		if (!$album_data[0]) {
			cpg_error(PERM_DENIED, 403);
		}

		if (!$CONFIG['disable_flood_protection']) {
			$last_com_data = $db->uFetchAssoc("SELECT author_md5_id, author_id FROM {$CONFIG['TABLE_COMMENTS']} WHERE pid = {$pid} ORDER BY msg_id DESC");
			if ($last_com_data) {
				if ((USER_ID && $last_com_data['author_id'] == USER_ID) || (!USER_ID && $last_com_data['author_md5_id'] == md5(session_id()))) {
					cpg_error(NO_FLOOD);
				}
			}
		}

		$ID = \Dragonfly::getKernel()->IDENTITY;
		if ($ID->isMember()) {
			$msg_author = $ID->nickname;
		} else {
			$msg_author = (!empty($_POST['msg_author'])) ? strip_tags($_POST['msg_author']) : cpg_error(EMPTY_NAME_OR_COM);
			check_words($msg_author);
			$USER['name'] = $msg_author;
		}
		$CONFIG['TABLE_COMMENTS']->insert(array(
			'pid'           => $pid,
			'msg_author'    => $msg_author,
			'msg_body'      => $msg_body,
			'msg_date'      => time(),
			'author_md5_id' => USER_ID ? '' : md5(session_id()),
			'author_id'     => USER_ID,
			'msg_raw_ip'    => $_SERVER['REMOTE_ADDR'],
		));

		$redirect = URL::index("&file=displayimage&pid={$pid}");
		if ($CONFIG['comment_email_notification']) {
			\Dragonfly\Email::send($mailer_message,
				REVIEW_TITLE,
				$msg_body."\r\n ".COM_ADDED." @ http://{$K->CFG['server']['domain']}{$K->CFG['server']['path']}{$redirect}",
				$CONFIG['gallery_admin_email']);
		}

		\Poodle\Notify::success(COM_ADDED);
		\URL::redirect($redirect);
		break;

	// Unknown event

	default:
		cpg_error(PARAM_MISSING);
}
