<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Private_Messages/save.php,v $
  $Revision: 9.8 $
  $Author: phoenix $
  $Date: 2007/05/17 02:26:15 $
**********************************************/
if (!defined('CPG_NUKE') && !defined('IN_PHPBB')) { exit; }

// See if recipient is at their savebox limit
$sql = "SELECT COUNT(privmsgs_id) AS savebox_items, MIN(privmsgs_date) AS oldest_post_time
		FROM ".$prefix."_bbprivmsgs
		WHERE ((privmsgs_to_userid = ".$userdata['user_id']."
				AND privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL.")
			OR (privmsgs_from_userid = ".$userdata['user_id']."
				AND privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL.") )";
$result = $db->sql_query($sql);
$sql_priority = ( SQL_LAYER == 'mysql' ) ? 'LOW_PRIORITY' : '';
if ($saved_info = $db->sql_fetchrow($result)) {
	if ($saved_info['savebox_items'] >= $board_config['max_savebox_privmsgs']) {
		$result = $db->sql_query("SELECT privmsgs_id FROM ".$prefix."_bbprivmsgs
				WHERE ( ( privmsgs_to_userid = ".$userdata['user_id']." AND privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL." )
						OR ( privmsgs_from_userid = ".$userdata['user_id']." AND privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL.") )
					AND privmsgs_date = ".$saved_info['oldest_post_time']);
		$old_privmsgs_id = $db->sql_fetchrow($result);
		$old_privmsgs_id = $old_privmsgs_id['privmsgs_id'];
		$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs 
		WHERE privmsgs_id = $old_privmsgs_id");
		$db->sql_query("DELETE $sql_priority FROM ".$prefix."_bbprivmsgs_text 
		WHERE privmsgs_text_id = $old_privmsgs_id");
	}
}
$saved_sql_id = '';
for ($i = 0; $i < sizeof($mark_list); $i++) {
	$saved_sql_id .= (($saved_sql_id != '') ? ', ' : '').intval($mark_list[$i]);
}
		// Process request
		$saved_sql = "UPDATE ".$prefix."_bbprivmsgs";
		// Decrement read/new counters if appropriate
		if ($folder == 'inbox' || $folder == 'outbox') {
			switch ($folder) {
				case 'inbox':
					$sql = 'privmsgs_to_userid = '.$userdata['user_id'];
					break;
				case 'outbox':
					$sql = 'privmsgs_from_userid = '.$userdata['user_id'];
					break;
			}

			// Get information relevant to new or unread mail
			// so we can adjust users counters appropriately
			$result = $db->sql_query("SELECT privmsgs_to_userid, privmsgs_type FROM ".$prefix."_bbprivmsgs
				WHERE privmsgs_id IN ($saved_sql_id)
					AND $sql
					AND privmsgs_type IN (".PRIVMSGS_NEW_MAIL.", ".PRIVMSGS_UNREAD_MAIL.")");
			if ( $row = $db->sql_fetchrow($result)) {
				$update_users = $update_list = array();
				do {
					switch ($row['privmsgs_type']) {
						case PRIVMSGS_NEW_MAIL:
							$update_users['new'][$row['privmsgs_to_userid']]++;
							break;

						case PRIVMSGS_UNREAD_MAIL:
							$update_users['unread'][$row['privmsgs_to_userid']]++;
							break;
					}
				}
				while ($row = $db->sql_fetchrow($result));
				if (sizeof($update_users)) {
					foreach ($update_users as $type => $users) {
         foreach ($users as $user_id => $dec) {
             $update_list[$type][$dec][] = $user_id;
         }
     }
					unset($update_users);
					foreach ($update_list as $type => $dec_ary) {
         switch ($type) {
   							case 'new':
   								$type = 'user_new_privmsg';
   								break;
   
   							case 'unread':
   								$type = 'user_unread_privmsg';
   								break;
   						}
         foreach ($dec_ary as $dec => $user_ary) {
             $user_ids = implode(', ', $user_ary);
             $db->sql_query("UPDATE ".$user_prefix."_users 
							SET $type = $type - $dec 
							WHERE user_id IN ($user_ids)");
         }
     }
					unset($update_list);
					unset($_SESSION['CPG_USER']);
				}
			}
			$db->sql_freeresult($result);
		}
		switch ($folder) {
			case 'inbox':
				$saved_sql .= " SET privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL."
					WHERE privmsgs_to_userid = ".$userdata['user_id']."
						AND ( privmsgs_type = ".PRIVMSGS_READ_MAIL."
						OR privmsgs_type = ".PRIVMSGS_NEW_MAIL."
						OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL.")";
				break;
			case 'outbox':
				$saved_sql .= " SET privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL."
					WHERE privmsgs_from_userid = ".$userdata['user_id']."
						AND ( privmsgs_type = ".PRIVMSGS_NEW_MAIL."
						OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." ) ";
				break;
			case 'sentbox':
				$saved_sql .= " SET privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL."
					WHERE privmsgs_from_userid = ".$userdata['user_id']."
						AND privmsgs_type = ".PRIVMSGS_SENT_MAIL;
				break;
		}
		$db->sql_query($saved_sql." AND privmsgs_id IN ($saved_sql_id)");
		url_redirect(getlink('&amp;folder=savebox'));
