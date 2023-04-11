<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Forums;

abstract class Auth
{
	const
		LEVEL_ALL   = 0,
		LEVEL_REG   = 1,
		LEVEL_ACL   = 2, // Private (Groups / Users)
		LEVEL_MOD   = 3,
		LEVEL_ADMIN = 5,

		ALL        = 0,
		VIEW       = 1, // The user may see the forum on the forum index page.
		READ       = 2, // The user may view topics in a forum, and read posts in those topics.
		POST       = 3,
		REPLY      = 4,
		EDIT       = 5,
		DELETE     = 6,
		ANNOUNCE   = 7,
		STICKY     = 8,
		POLLCREATE = 9,
		VOTE       = 10,
		ATTACH     = 11,
		DOWNLOAD   = 20;

	private static $fields = array(
		1  => 'auth_view',
		2  => 'auth_read',
		3  => 'auth_post',
		4  => 'auth_reply',
		5  => 'auth_edit',
		6  => 'auth_delete',
		7  => 'auth_announce',
		8  => 'auth_sticky',
		9  => 'auth_pollcreate',
		10 => 'auth_vote',
		11 => 'auth_attachments',
		20 => 'auth_download'
	);

	/*
		$type's accepted:
		VIEW, READ, POST, REPLY, EDIT, DELETE, STICKY, ANNOUNCE, VOTE, POLLCREATE

		Possible options ($type/forum_id combinations):

		* If you include a type and forum_id then a specific lookup will be done and
		the single result returned

		* If you set type to static::ALL and specify a forum_id an array of all auth types
		will be returned

		* If you provide a forum_id a specific lookup on that forum will be done

		* If you set forum_id to 0 and specify a type an array listing the
		results for all forums will be returned

		* If you set forum_id to 0 and type to static::ALL a multidimensional
		array containing the auth permissions for all types and all forums for that
		user is returned

		All results are returned as associative arrays, even when a single auth type is
		specified.

		If available you can send an array (either one or two dimensional) containing the
		forum auth levels, this will prevent the auth function having to do its own
		lookup
	*/

	public static function all($forum_id, $f_access = null)
	{
		return static::forType(static::ALL, $forum_id, $f_access);
	}

	public static function view($forum_id, $f_access = null)
	{
		return static::forType(static::VIEW, $forum_id, $f_access);
	}

	public static function read($forum_id, $f_access = null)
	{
		return static::forType(static::READ, $forum_id, $f_access);
	}

	public static function isForumModerator($forum_id)
	{
		if (!is_user()) {
			return false;
		}
		if (can_admin($GLOBALS['module_name'])) {
			return true;
		}
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;
		$forum_id = (int) $forum_id;
		$u_access = $SQL->uFetchAll("SELECT
			MAX(a.auth_mod) auth_mod
		FROM ".AUTH_ACCESS_TABLE."
		WHERE group_id IN (".implode(',', array_keys($K->IDENTITY['_mem_of_groups']) ?: array(0)).")
		  AND forum_id = {$forum_id}");
		return static::check_user(static::LEVEL_MOD, 'auth_mod', $u_access);
	}

	public static function forType($type, $forum_id, $f_access = null)
	{
		$K = \Dragonfly::getKernel();
		$SQL = $K->SQL;
		$userinfo = $K->IDENTITY;
		$is_admin = can_admin($GLOBALS['module_name']);
		$reg_allowed = is_user() && ($is_admin || !in_group($GLOBALS['board_config']['restricted_group']));

		if (static::ALL == $type) {
			$auth_fields = static::$fields;
		} else if (isset(static::$fields[$type])) {
			$auth_fields = array(static::$fields[$type]);
		} else {
			return array();
		}

		$a_sql = implode(', ', $auth_fields);

		# If f_access has been passed, or auth is needed to return an array of forums
		# then we need to pull the auth information on the given forum (or all forums)
		if (empty($f_access)) {
			$sql = "SELECT forum_id, {$a_sql} FROM ".FORUMS_TABLE;
			if ($forum_id) {
				$f_access = $SQL->uFetchAssoc($sql . " WHERE forum_id = {$forum_id}");
			} else {
				$f_access = $SQL->uFetchAll($sql);
			}
			if (!$f_access) {
				return array();
			}
		}

		# If the user isn't logged on then all we need do is check if the forum
		# has the type set to ALL, if yes they are good to go, if not then they
		# are denied access
		$revoked = array();
		$u_access = array();
		if (is_user()) {
			$result = $SQL->query("SELECT forum_id, {$a_sql}, auth_mod
				FROM ".AUTH_ACCESS_TABLE."
				WHERE group_id IN (".implode(',', array_keys($userinfo['_mem_of_groups']) ?: array(0)).")"
				. ($forum_id ? "AND forum_id = {$forum_id}" : ''));
			while ($row = $result->fetch_assoc()) {
				if ($forum_id) {
					$u_access[] = $row;
				} else {
					$u_access[$row['forum_id']][] = $row;
				}
			}

			# Check for removed forum privileges: post, reply, edit, delete, create polls, vote, attach and download
			if (!$is_admin) {
				$result = $SQL->query("SELECT forum_id FROM ".FORUMS_TABLE."_privileges WHERE user_id = {$userinfo->id}");
				while ($row = $result->fetch_row()) {
					$revoked[$row[0]] = true;
				}
			}
			$result->free();
		}

		$auth_user = array();
		foreach ($auth_fields as $key) {
			# If the user is logged on and the forum type is either ALL or REG then the user has access
			#
			# If the type if ACL, MOD or ADMIN then we need to see if the user has specific permissions
			# to do whatever it is they want to do ... to do this we pull relevant information for the
			# user (and any groups they belong to)
			#
			# Now we compare the users access level against the forums. We assume here that a moderator
			# and admin automatically have access to an ACL forum, similarly we assume admins meet an
			# auth requirement of MOD
			if ($forum_id) {
				$value = $f_access[$key];
				$auth_user[$key.'_type'] = $value;
				if (!empty($revoked[$forum_id]) && 'auth_view' !== $key && 'auth_read' !== $key) {
					$auth_user[$key] = false;
					continue;
				}
				switch ($value)
				{
					case static::LEVEL_ALL:
						$auth_user[$key] = true;
						break;

					case static::LEVEL_REG:
						$auth_user[$key] = $reg_allowed;
						break;

					case static::LEVEL_ACL:
						$auth_user[$key] = (is_user() ? $is_admin || static::check_user(static::LEVEL_ACL, $key, $u_access) : 0);
						break;

					case static::LEVEL_MOD:
						$auth_user[$key] = (is_user() ? $is_admin || static::check_user(static::LEVEL_MOD, 'auth_mod', $u_access) : 0);
						break;

					case static::LEVEL_ADMIN:
						$auth_user[$key] = $is_admin;
						break;

					default:
						$auth_user[$key] = 0;
						break;
				}
			} else {
				foreach ($f_access as $perms) {
					$value = $perms[$key];
					$f_forum_id = $perms['forum_id'];
					if (!isset($u_access[$f_forum_id])) {
						$u_access[$f_forum_id] = false;
					}
					if (!empty($revoked[$f_forum_id]) && 'auth_view' !== $key && 'auth_read' !== $key) {
						$auth_user[$f_forum_id][$key] = false;
						continue;
					}

					switch ($value) {
						case static::LEVEL_ALL:
							$auth_user[$f_forum_id][$key] = true;
							break;

						case static::LEVEL_REG:
							$auth_user[$f_forum_id][$key] = $reg_allowed;
							break;

						case static::LEVEL_ACL:
							$auth_user[$f_forum_id][$key] = (is_user() ? $is_admin || static::check_user(static::LEVEL_ACL, $key, $u_access[$f_forum_id]) : 0);
							break;

						case static::LEVEL_MOD:
							$auth_user[$f_forum_id][$key] = (is_user() ? $is_admin || static::check_user(static::LEVEL_MOD, 'auth_mod', $u_access[$f_forum_id]) : 0);
							break;

						case static::LEVEL_ADMIN:
							$auth_user[$f_forum_id][$key] = $is_admin;
							break;

						default:
							$auth_user[$f_forum_id][$key] = 0;
							break;
					}
				}
			}
		}

		# Is user a moderator?
		if ($forum_id) {
			$auth_user['auth_mod'] = (is_user() && empty($revoked[$forum_id])) ? $is_admin || static::check_user(static::LEVEL_MOD, 'auth_mod', $u_access) : 0;
		} else {
			foreach ($f_access as $perms) {
				$f_forum_id = $perms['forum_id'];
				if (!isset($u_access[$f_forum_id])) { $u_access[$f_forum_id] = false; }
				$auth_user[$f_forum_id]['auth_mod'] = (is_user() && empty($revoked[$f_forum_id])) ? $is_admin || static::check_user(static::LEVEL_MOD, 'auth_mod', $u_access[$f_forum_id]) : 0;
			}
		}

		return $auth_user;
	}

	public static function getLevelName($level)
	{
		$lang = \Dragonfly::getKernel()->L10N;
		switch ($level)
		{
			case static::LEVEL_ALL:
				return $lang['Auth_Anonymous_Users'];

			case static::LEVEL_REG:
				return $lang['Auth_Registered_Users'];

			case static::LEVEL_ACL:
				return $lang['Auth_Users_granted_access'];

			case static::LEVEL_MOD:
				return $lang['Auth_Moderators'];

			case static::LEVEL_ADMIN:
				return $lang['Auth_Administrators'];

			default:
				return 'unknown';
		}
	}

	protected static function check_user($type, $key, $u_access)
	{
		$auth_user = false;
		if (is_array($u_access)) {
			foreach ($u_access as $perms) {
				switch ($type) {
					case static::LEVEL_ACL:
						$auth_user |= $perms[$key];
					case static::LEVEL_MOD:
						$auth_user |= $perms['auth_mod'];
						break;
				}
				if ($auth_user) { break; }
			}
		}
		return !!$auth_user;
	}

}
