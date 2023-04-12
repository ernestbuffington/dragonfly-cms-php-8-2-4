<?php
/***************************************************************************
 *							functions_validate.php
 *							  -------------------
 *	 begin				  : Saturday, Feb 13, 2001
 *	 copyright			  : (C) 2001 The phpBB Group
 *	 email				  : support@phpbb.com
 *
 *	 Modifications made by CPG Dev Team http://cpgnuke.com
 *	 Last modification notes:
 *
 *	 $Id: functions_validate.php,v 9.2 2005/03/25 05:25:56 djmaze Exp $
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

if (!defined('IN_PHPBB')) {
	die('Hacking attempt');
}

//
// Check to see if the username has been taken, or if it is disallowed.
// Used for registering, changing names, and posting anonymously with a username
//
function validate_username($username)
{
	$row = [];
 global $db, $lang, $userdata;
	// Remove doubled up spaces
	$username = preg_replace('#\s+#', ' ', $username);
	// Limit username length
	$username = substr($username, 0, 25);

	$result = $db->sql_query("SELECT username FROM " . USERS_TABLE . " WHERE LOWER(username) = '" . strtolower($username) . "'");
	if ($db->sql_numrows($result)) {
		if ((is_user() && $row['username'] != $userdata['username']) || !is_user()) {
			$db->sql_freeresult($result);
			return array('error' => true, 'error_msg' => $lang['Username_taken']);
		}
	}
	$db->sql_freeresult($result);

	$result = $db->sql_query("SELECT group_name FROM " . GROUPS_TABLE . " WHERE LOWER(group_name) = '" . strtolower($username) . "'");
	if ($db->sql_numrows($result)) {
		$db->sql_freeresult($result);
		return array('error' => true, 'error_msg' => $lang['Username_taken']);
	}
	$db->sql_freeresult($result);

	$result = $db->sql_query("SELECT disallow_username FROM " . DISALLOW_TABLE);
	if ($db->sql_numrows($result)) {
		while($row = $db->sql_fetchrow($result)); {
			if (preg_match("#\b(" . str_replace("\*", ".*?", phpbb_preg_quote($row['disallow_username'], '#')) . ")\b#i", $username)) {
				$db->sql_freeresult($result);
				return array('error' => true, 'error_msg' => $lang['Username_disallowed']);
			}
		}
	}
	$db->sql_freeresult($result);

	$result = $db->sql_query("SELECT word FROM	" . WORDS_TABLE);
	if ($db->sql_numrows($result)) {
		while ($row = $db->sql_fetchrow($result)); {
			if (preg_match("#\b(" . str_replace("\*", ".*?", phpbb_preg_quote($row['word'], '#')) . ")\b#i", $username)) {
				$db->sql_freeresult($result);
				return array('error' => true, 'error_msg' => $lang['Username_disallowed']);
			}
		}
	}
	$db->sql_freeresult($result);

	// Don't allow " and ALT-255 in username.
	if (strstr($username, '"') || strstr($username, '&quot;') || strstr($username, chr(160))) {
		return array('error' => true, 'error_msg' => $lang['Username_invalid']);
	}

	return array('error' => false, 'error_msg' => '');
}
