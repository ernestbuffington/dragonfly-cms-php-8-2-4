<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Identity;

class Search
{
	public static function byID($id, $any=false)         { return static::find(array('id'=>$id), $any); }

	public static function byEmail($email, $any=false)   { return static::find(array('email'=>$email), $any); }

	public static function byNickname($name, $any=false) { return static::find(array('nickname'=>$name), $any); }

	protected static function find($user, $any_type=false)
	{
		static $users = array();
		$K   = \Poodle::getKernel();
		$SQL = $K->SQL;
		$where = '';
		if (isset($user['id'])) {
			if (empty($user['id'])) { return \Poodle\Identity::factory(); }
			if (isset($users[$user['id']])) { return $users[$user['id']]; }
			$where = 'user_id = '.(int)$user['id'];
		}
		else if (!empty($user['email'])) {
			$user['email'] = \Poodle\Input::lcEmail($user['email']);
			foreach ($users as $row) {
				if ($user['email'] === $row['email']) { return $row; }
			}
			$where = 'user_email = '.$SQL->quote($user['email']);
		}
		else if (!empty($user['nickname'])) {
			$user['nickname'] = mb_strtolower($user['nickname']);
			foreach ($users as $row) {
				if ($user['nickname'] === mb_strtolower($row['nickname'])) { return $row; }
			}
			$where = 'user_nickname_lc = '.$SQL->quote($user['nickname']);
		}
		else {
			throw new \Exception('$user unknown: '.implode(', ',$users));
		}

		if (!$any_type && (!isset($K->IDENTITY) || !$K->IDENTITY->isAdmin())) {
			$where .= ' AND user_level>0';
		}

		$query = 'SELECT
			user_id as identity_id,
			user_regdate as user_ctime,
			username as user_nickname,
			user_email,
			\'\' as user_givenname,
			\'\' as user_surname,
			user_lang as user_language,
			user_timezone,
			user_lastvisit as user_last_visit,
			user_allow_viewonline as user_default_status,
			CASE WHEN user_id=1 THEN 0 ELSE user_level END as user_type
		FROM '.$SQL->TBL->users.'
		WHERE '.$where;
		$user = $SQL->uFetchAssoc($query, true);
		if (!$user || !is_array($user)) { return false; }
		$SQL->removePrefix($user, 'identity', 'user');
		return $users[$user['id']] = \Poodle\Identity::factory($user);
	}
}
