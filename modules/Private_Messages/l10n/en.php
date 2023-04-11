<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004-2006 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  If editing this file please edit the second
  or right side of the pair of terms.

Encoding test: n-array summation ∑ latin ae w/ acute ǽ
*******************************************************/
if (!defined('CPG_NUKE')) { exit; }

global $LNG;
$LNG = array(
	'Unread_message' => 'Unread message',
	'Read_message' => 'Read message',
	'Read_pm' => 'Read message',
	'Click_return_inbox' => 'Click %sHere%s to return to your Inbox',
	'Click_return_index' => 'Click %sHere%s to return to the Index',
	'Send_a_new_message' => 'Send a new private message',
	'Send_a_reply' => 'Reply to a private message',
	'Date' => 'Date',
	'Sent' => 'Sent',
	'Saved' => 'Saved',
	//'PM_disabled' => 'Private messaging has been disabled on this board.',
	'Find' => 'Find',

	# Reading
	'Inbox' => 'Inbox',
	'Outbox' => 'Outbox',
	'Savebox' => 'Savebox',
	'Sentbox' => 'Sentbox',
	'Inbox limit' => 'Inbox limit',
	'Savebox limit' => 'Savebox limt',
	'Sentbox limit' => 'Sentbox limit',
	'Outbox limit' => 'Outbox limit',
	'inbox_size' => 'Your Inbox is %d%% full',
	'sentbox_size' => 'Your Sentbox is %d%% full',
	'savebox_size' => 'Your Savebox is %d%% full',
	'outbox_size' => 'Your Outbox is %d%% full',
	'Asc' => 'Asc',
	'Desc' => 'Desc',
	'Mark' => 'Mark',
	'Mark_all' => 'Mark all',
	'Unmark_all' => 'Unmark all',
	'Order_by_time' => 'Order by time',
	'Order_by_username' => 'Order by username',
	'Order_by_subject' => 'Order by subject',
	'Save_marked' => 'Save Marked',
	'Save_message' => 'Save Message',
	'Delete_marked' => 'Delete Marked',
	'Delete_message' => 'Delete Message',
	'Display_messages' => 'Display messages from previous',
	'All_Messages' => 'All Messages',
	'Post_new_pm' => 'Compose',
	'Post_reply_pm' => 'Reply',
	'Post_quote_pm' => 'Quote',
	'Edit_message' => 'Edit',

	# Admin
	'Flood interval' => 'Flood interval',
	'Flood explain' => 'antispam rate limit protection (1 message every N seconds)',
	'Statistic box length' => 'Statistic box length',
	'Messages per page' => 'Messages per page',
	'Allow BBCode' => 'Allow BBCode',
	'Allow smilies' => 'Allow smilies',

	# Compose
	'Edit_pm' => 'Edit message',
	'Message_sent' => 'Your message has been sent.',
	'Message_body' => 'Message body',
	'Options' => 'Options',
	'Find_username' => 'Find a username',
	'Enable_BBCode_pm' => 'Enable BBCode',
	'Enable_Smilies_pm' => 'Enable Smilies',
	'Quick Reply' => 'Quick Reply',

	# Notifications and Errors
	'Notification_subject' => 'New Private Message has arrived!',
	'Notification_message' => 'You have received a new private message to your account on "'.\Dragonfly::getKernel()->CFG->global->sitename.'" and you have requested that you be notified on this event. You can view your new message by clicking on the following link:',
	'Notification_edit_prefs' => 'Remember that you can always choose not to be notified of new messages by changing the appropriate setting in your profile.',
	'New_pms' => 'You have %d new messages',
	'New_pm' => 'You have %d new message',
	'No_new_pm' => 'You have no new messages',
	'Unread_pms' => 'You have %d unread messages',
	'Unread_pm' => 'You have %d unread message',
	'No_unread_pm' => 'You have no unread messages',
	'You_new_pm' => 'A new private message is waiting for you in your Inbox',
	'You_new_pms' => 'New private messages are waiting for you in your Inbox',
	'You_no_new_pm' => 'No new private messages are waiting for you',
	'No_messages_folder' => 'You have no messages in this folder',
	'Confirm_delete_pm' => 'Are you sure you want to delete this message?',
	'Confirm_delete_pms' => 'Are you sure you want to delete these messages?',
	'Cannot_send_privmsg' => 'Sorry, but the administrator has prevented you from sending private messages.',
	'No_to_user' => 'You must specify a username to whom to send this message.',
	'No_such_user' => 'Sorry, but no such user exists.',
	'Flood_Error' => 'You cannot make another post so soon after your last; please try again in a short while.',
	'Empty_subject' => 'You must specify a subject when posting a new topic.',
	'Empty_message' => 'You must enter a message when posting.',
	'No_match' => 'No matches found.',
	'No_such_folder' => 'No such folder exists',
	'No_folder' => 'No folder specified',
	'Click_view_privmsg' => 'Click %sHere%s to visit your Inbox',
);
