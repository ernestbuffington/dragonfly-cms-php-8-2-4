<?php
/***************************************************************************
 *				functions_post.php
 *				-------------------
 *	 begin		: Saturday, Feb 13, 2001
 *	 copyright		: (C) 2001 The phpBB Group
 *	 email		: support@phpbb.com
 *
 *	 Modifications made by CPG Dev Team http://cpgnuke.com
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

namespace Dragonfly\Forums;

abstract class Notify
{

	# Handle user notification on new post
	public static function users($mode, \Dragonfly\Forums\Post $post, $notify_user)
	{
		$forum_id   = $post->forum->id;
		$topic_id   = $post->topic->id;

		$orig_word = $replacement_word = array();
		obtain_word_list($orig_word, $replacement_word);
		if ($orig_word) {
			$topic_title = preg_replace($orig_word, $replacement_word, $post->topic->title);
		}

		global $board_config, $db, $MAIN_CFG, $userinfo, $template;
		$lang = $template->L10N;

		$current_time = time();

		if ('reply' == $mode) {
			# Create the list of users not to send to
			$do_not_notify_user_ids = array();
			$result_users = $db->query('SELECT user_id FROM '.$db->TBL->users.' WHERE user_level < 1');
			while ($row_user = $result_users->fetch_row()) {
				$do_not_notify_user_ids[] = $row_user[0];
			}
			$result_users->free();
			$do_not_notify_user_ids[] = \Dragonfly\Identity::ANONYMOUS_ID;
			$do_not_notify_user_ids[] = $userinfo['user_id'];

			$topic_notify_user_ids = array();
			$bcc_list_ary = array();

			# Notify topic watchers first - select the list of users to notify
			$result_user_info = $db->query('SELECT u.user_id, u.user_email, u.user_lang
				FROM '.TOPICS_WATCH_TABLE.' tw, '.$db->TBL->users.' u
				WHERE tw.topic_id = '.$topic_id.'
				  AND tw.user_id NOT IN ('.implode(', ',$do_not_notify_user_ids).')
				  AND tw.notify_status = 0
				  AND u.user_id = tw.user_id');
			if ($result_user_info->num_rows) {
				set_time_limit(0);
				# Keep track of which users are notified so we do not notify them in the forum notify
				while ($row_user_info = $result_user_info->fetch_assoc()) {
					if ($row_user_info['user_email']) {
						$bcc_list_ary[$row_user_info['user_lang']][] = $row_user_info['user_email'];
					}
					$topic_notify_user_ids[$row_user_info['user_email']] = $row_user_info['user_id'];
				}
				static::sendMailing('topic_notify', $lang['Topic_reply_notification'], $bcc_list_ary, array(
					'TOPIC_TITLE' => $topic_title,
					'U_TOPIC' => \URL::index("&file=viewtopic&p={$post->id}", true, true)."#{$post->id}",
					'U_STOP_WATCHING_TOPIC' => \URL::index("&file=viewtopic&t={$topic_id}&unwatch", true, true)
				));
			}
			$result_user_info->free();

			if ($topic_notify_user_ids) {
				$db->query('UPDATE '.TOPICS_WATCH_TABLE.'
				SET notify_status = 1
				WHERE topic_id = '.$topic_id.' AND user_id IN ('.implode(', ', $topic_notify_user_ids).')');
			}

			if ($board_config['allow_forum_watch']) {
				# Now notify any forum watchers that haven't already been notified
				# and make sure we don't notify users that were notified in the topic watch section
				$do_not_notify_user_ids = array_merge($do_not_notify_user_ids, $topic_notify_user_ids);
				$result_user_info = $db->query('SELECT u.user_id, u.user_email, u.user_lang
				FROM '.FORUMS_WATCH_TABLE.' fw, '.$db->TBL->users.' u
				WHERE fw.forum_id = '.$forum_id.'
				  AND fw.user_id NOT IN ('.implode(', ',$do_not_notify_user_ids).')
				  AND fw.notify_status = 0
				  AND u.user_id = fw.user_id');
				$bcc_list_ary = $forum_notify_user_ids = array();
				if ($result_user_info->num_rows) {
					# Sixty second limit
					set_time_limit(0);

					while ($row_user_info = $result_user_info->fetch_assoc()) {
						if ($row_user_info['user_email']) {
							$bcc_list_ary[$row_user_info['user_lang']][] = $row_user_info['user_email'];
						}
						$forum_notify_user_ids[$row_user_info['user_email']] = $row_user_info['user_id'];
					}

					static::sendMailing('forum_notify', $lang['Topic_reply_notification'], $bcc_list_ary, array(
						'TOPIC_TITLE' => $topic_title,
						'FORUM_NAME' => $post->forum->name,
						'U_TOPIC' => \URL::index("&file=viewforum&f={$forum_id}", true, true),
						'U_STOP_WATCHING_FORUM' => \URL::index("&file=viewforum&f={$forum_id}&unwatch", true, true)
					));
				}
				$result_user_info->free();

				# If the user was notified of a new topic (not forum), then make sure we also mark
				# them as notified for the forum
				$forum_notify_user_ids = array_merge($forum_notify_user_ids, $topic_notify_user_ids);
					if (count($forum_notify_user_ids)) {
					$db->query('UPDATE '.FORUMS_WATCH_TABLE.'
					SET notify_status = 1
					WHERE forum_id = '.$forum_id.' AND user_id IN ('.implode(', ', $forum_notify_user_ids).')');
				}
			}
		}

		else if ('newtopic' == $mode && $board_config['allow_forum_watch']) {
			# Create the list of users not to send to
			$do_not_notify_user_ids = array();
			$result_users = $db->query('SELECT user_id FROM '.$db->TBL->users.' WHERE user_level < 1');
			while ($row_user = $result_users->fetch_row()) {
				$do_not_notify_user_ids[] = $row_user[0];
			}
			$result_users->free();
			$do_not_notify_user_ids[] = \Dragonfly\Identity::ANONYMOUS_ID;
			$do_not_notify_user_ids[] = $userinfo['user_id'];

			# Notify any forum watchers
			$result_user_info = $db->query('SELECT u.user_id, u.user_email, u.user_lang
			FROM '.FORUMS_WATCH_TABLE.' fw, '.$db->TBL->users.' u
			WHERE fw.forum_id = '.$forum_id.'
				AND fw.user_id NOT IN ('.implode(', ',$do_not_notify_user_ids).')
				AND fw.notify_status = 0
				AND u.user_id = fw.user_id');
			$bcc_list_ary = $forum_notify_user_ids = array();
			if ($result_user_info->num_rows) {
				# Sixty second limit
				set_time_limit(0);

				while ($row_user_info = $result_user_info->fetch_assoc()) {
					if ($row_user_info['user_email']) {
						$bcc_list_ary[$row_user_info['user_lang']][] = $row_user_info['user_email'];
					}
					$forum_notify_user_ids[$row_user_info['user_email']] = $row_user_info['user_id'];
				}

				static::sendMailing('newtopic_notify', $lang['New_topic_notify'], $bcc_list_ary, array(
					'TOPIC_TITLE' => $topic_title,
					'FORUM_NAME' => $post->forum->name,
					'U_TOPIC' => \URL::index("&file=viewforum&f={$forum_id}", true, true),
					'U_STOP_WATCHING_FORUM' => \URL::index("&file=viewforum&f={$forum_id}&unwatch", true, true)
				));
			}
			$result_user_info->free();

			if (count($forum_notify_user_ids)) {
				$db->query('UPDATE '.FORUMS_WATCH_TABLE.'
				SET notify_status = 1
				WHERE forum_id = '.$forum_id.' AND user_id IN ('.implode(', ', $forum_notify_user_ids).')');
			}
		}

		$row = $db->uFetchRow("SELECT topic_id FROM ".TOPICS_WATCH_TABLE."
			WHERE topic_id = {$topic_id} AND user_id = {$userinfo['user_id']}");

		if (!$notify_user && $row) {
			$db->query("DELETE FROM ".TOPICS_WATCH_TABLE." WHERE topic_id = {$topic_id} AND user_id = {$userinfo['user_id']}");
		} else if ($notify_user && !$row[0]) {
			$db->query("INSERT INTO ".TOPICS_WATCH_TABLE."
				(user_id, topic_id, notify_status)
				VALUES
				({$userinfo['user_id']}, {$topic_id}, 0)");
		}
	}

	protected static function sendMailing($template, $subject, $bcc_list_ary, $vars)
	{
		if (!$bcc_list_ary) {
			return;
		}
		$msg_vars = array();
		foreach ($vars as $key => $val) {
			$msg_vars['{'.$key.'}'] = $val;
		}
		$msg_vars = array_merge($msg_vars, array(
			'{EMAIL_SIG}' => '',
			'{USERNAME}' => '',
			'{SITENAME}' => $GLOBALS['board_config']['sitename'],
		));
		$emailer = \Dragonfly\Email::getMailer();
		foreach ($bcc_list_ary as $user_lang => $bcc_list) {
			try {
				if (!$user_lang) {
					$user_lang = \Dragonfly::getKernel()->OUT->L10N->lng;
				}
				$tpl_file = "includes/l10n/{$user_lang}/Forums/email/{$template}.tpl";
				if (!is_file(realpath($tpl_file))) {
					$tpl_file = "includes/l10n/en/Forums/email/{$template}.tpl";
					if (!is_file(realpath($tpl_file))) {
						throw new \Exception('Could not find email template file: '.$template);
					}
				}
				$msg = strtr(file_get_contents($tpl_file), $msg_vars);

				foreach ($bcc_list as $email_address) {
					$emailer->addBCC($email_address);
				}

				// We now try and pull a subject from the email body ... if it exists,
				// do this here because the subject may contain a variable
				$match = array();
				if (preg_match('#^(Subject:(.*?))$#m', $msg, $match)) {
					$emailer->subject = trim($match[2] ?: ($subject ?: 'No Subject'));
					$msg = trim(preg_replace('#[\r\n]*?'.preg_quote($match[1], '#').'#s', '', $msg));
				} else {
					$emailer->subject = trim($subject ?: 'No Subject');
				}
				$emailer->body = trim(preg_replace('#^(Charset:(.*?))$#m', '', $msg));
				if (!$emailer->send()) {
					throw new \Exception($emailer->error);
				}
			} catch (\Exception $e) {
				trigger_error('Failed sending email: '.$e->getMessage(), E_USER_WARNING);
			}
			$emailer->clear();
		}
	}

}
