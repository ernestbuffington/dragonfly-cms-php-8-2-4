<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/modules/Private_Messages/delete.php,v $
  $Revision: 9.10 $
  $Author: phoenix $
  $Date: 2007/05/17 02:26:15 $
**********************************************/
if (!defined('CPG_NUKE') && !defined('IN_PHPBB')) { exit; }
global $pagetitle;

// Delete PM's
if ( isset($mark_list) && !is_array($mark_list) ) {
	// Set to empty array instead of '0' if nothing is selected.
	$mark_list = array();
}
if (!$confirm) {
	$s_hidden_fields = '<input type="hidden" name="mode" value="'.$mode.'" />';
	$s_hidden_fields .= ( isset($_POST['delete']) ) ? '<input type="hidden" name="delete" value="true" />' : '<input type="hidden" name="deleteall" value="true" />';
	for($i = 0; $i < (is_countable($mark_list) ? count($mark_list) : 0); $i++) {
		$s_hidden_fields .= '<input type="hidden" name="mark[]" value="'.intval($mark_list[$i]).'" />';
	}
	//
	// Output confirmation page
	//
	$pagetitle .= ' '._BC_DELIM.' '.$lang['Confirm_delete_pm'];
	cpg_delete_msg(getlink('&amp;folder='.$folder),
	               (((is_countable($mark_list) ? count($mark_list) : 0) == 1) ? $lang['Confirm_delete_pm'] : $lang['Confirm_delete_pms']),
	               $s_hidden_fields);
}
else {
	if ($delete_all) {
		switch($folder) {
			case 'inbox':
				$delete_type = "privmsgs_to_userid = ".$userdata['user_id']." AND (
				privmsgs_type = ".PRIVMSGS_READ_MAIL." OR privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
				break;
			case 'outbox':
				$delete_type = "privmsgs_from_userid = ".$userdata['user_id']." AND ( privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
				break;
			case 'sentbox':
				$delete_type = "privmsgs_from_userid = ".$userdata['user_id']." AND privmsgs_type = ".PRIVMSGS_SENT_MAIL;
				break;
			case 'savebox':
				$delete_type = "( ( privmsgs_from_userid = ".$userdata['user_id']."
					AND privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL." )
				OR ( privmsgs_to_userid = ".$userdata['user_id']."
					AND privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL." ) )";
				break;
		}
		$result = $db->sql_query("SELECT privmsgs_id FROM ".$prefix."_bbprivmsgs WHERE $delete_type");
		while ( $row = $db->sql_fetchrow($result) ) {
			$mark_list[] = $row['privmsgs_id'];
		}
		unset($delete_type);
	}
	if (is_countable($mark_list) ? count($mark_list) : 0) {
		$delete_sql_id = '';
		for ($i = 0; $i < sizeof($mark_list); $i++) {
			$delete_sql_id .= (($delete_sql_id != '') ? ', ' : '').intval($mark_list[$i]);
		}
		if ($folder == 'inbox' || $folder == 'outbox') {
			switch ($folder) {
				case 'inbox':
					$sql = "privmsgs_to_userid = ".$userdata['user_id'];
					break;
				case 'outbox':
					$sql = "privmsgs_from_userid = ".$userdata['user_id'];
					break;
			}
			// Get information relevant to new or unread mail
			// so we can adjust users counters appropriately
			$result = $db->sql_query("SELECT privmsgs_to_userid, privmsgs_type
					FROM ".$prefix."_bbprivmsgs
					WHERE privmsgs_id IN ($delete_sql_id)
						AND $sql
						AND privmsgs_type IN (".PRIVMSGS_NEW_MAIL.", ".PRIVMSGS_UNREAD_MAIL.")");
			if ($row = $db->sql_fetchrow($result)) {
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
   								$type = "user_new_privmsg";
   								break;
   							case 'unread':
   								$type = "user_unread_privmsg";
   								break;
   						}
         foreach ($dec_ary as $dec => $user_ary) {
             $user_ids = implode(', ', $user_ary);
             $db->sql_query("UPDATE ".$user_prefix."_users 
							SET $type = $type - $dec WHERE user_id IN ($user_ids)");
         }
     }
					unset($update_list);
					unset($_SESSION['CPG_SESS']);
				}
			}
			$db->sql_freeresult($result);
		}
		// Delete the messages
		$delete_sql = "DELETE FROM ".$prefix."_bbprivmsgs 
		WHERE privmsgs_id IN ($delete_sql_id) AND ";
		switch( $folder ) {
			case 'inbox':
				$delete_sql .= "privmsgs_to_userid = ".$userdata['user_id']." AND (
					privmsgs_type = ".PRIVMSGS_READ_MAIL." OR privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
				break;
			case 'outbox':
				$delete_sql .= "privmsgs_from_userid = ".$userdata['user_id']." AND (
					privmsgs_type = ".PRIVMSGS_NEW_MAIL." OR privmsgs_type = ".PRIVMSGS_UNREAD_MAIL." )";
				break;
			case 'sentbox':
				$delete_sql .= "privmsgs_from_userid = ".$userdata['user_id']." AND privmsgs_type = ".PRIVMSGS_SENT_MAIL;
				break;
			case 'savebox':
				$delete_sql .= "( ( privmsgs_from_userid = ".$userdata['user_id']."
					AND privmsgs_type = ".PRIVMSGS_SAVED_OUT_MAIL." )
				OR ( privmsgs_to_userid = ".$userdata['user_id']."
					AND privmsgs_type = ".PRIVMSGS_SAVED_IN_MAIL." ) )";
				break;
		}
		$db->sql_query($delete_sql); // BEGIN_TRANSACTION
		$db->sql_query("DELETE FROM ".$prefix."_bbprivmsgs_text 
		WHERE privmsgs_text_id IN ($delete_sql_id)"); // END_TRANSACTION
	}
}
