<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Identity;

abstract class Request
{
	const
		TYPE_ACCOUNT  = 0,
		TYPE_PASSWORD = 1,
		TYPE_NEWEMAIL = 2;

	public static function cleanup($type = null, $timeout = 86400)
	{
		$where = 'request_time < '.(time()-$timeout);
		if (!is_null($type)) {
			$where .= ' AND request_type = ' . intval($type);
		}
		\Poodle::getKernel()->SQL->TBL->users_request->delete($where);
	}

	public static function removeAccount($key)      { return self::remove($key, 0); }
	public static function removePassword($key)     { return self::remove($key, 1); }
	public static function removeEmailAddress($key) { return self::remove($key, 2); }
	public static function remove($key, $type = 0)
	{
		\Poodle::getKernel()->SQL->TBL->users_request->delete(array(
			'request_type' => $type,
			'request_key'  => $key,
		));
	}

	public static function getAccount($key)      { return self::find($key, 0); }
	public static function getPassword($key)     { return self::find($key, 1); }
	public static function getEmailAddress($key) { return self::find($key, 2); }
	public static function find($key, $type = 0)
	{
		$type = (int)$type;
		$SQL = \Poodle::getKernel()->SQL;
		$data = $SQL->uFetchAssoc("SELECT
			identity_id,
			user_nickname nickname,
			user_password password,
			user_email email,
			user_givenname givenname,
			user_surname surname,
			user_details details
		FROM {$SQL->TBL->users_request}
		WHERE request_type = {$type}
		  AND request_key = {$SQL->quote($key)}");
		if ($data && !empty($data['details'])) {
			$data['details'] = json_decode($data['details'], true);
		}
		return $data;
	}

	public static function getRequestByEmail($email, $type = 0)
	{
		$type = (int)$type;
		$email = \Poodle\Input::lcEmail($email);
		$SQL = \Poodle::getKernel()->SQL;
		return $SQL->uFetchAssoc("SELECT
			request_key,
			identity_id,
			user_nickname nickname,
			user_password password,
			user_email email,
			user_givenname givenname,
			user_surname surname,
			user_details details
		FROM {$SQL->TBL->users_request}
		WHERE request_type = {$type}
		  AND user_email = {$SQL->quote($email)}");
	}

	public static function newAccount($user)      { return self::create($user, 0); }
	public static function newPassword($user)     { return self::create($user, 1); }
	public static function newEmailAddress($user) { return self::create($user, 2); }
	public static function create($user, $type = 0)
	{
		$type = (int)$type;
		$key  = \Poodle\Hash::string('sha256', microtime().' '.random_bytes(64));
		\Poodle::getKernel()->SQL->TBL->users_request->insert(array(
			'request_type'   => $type, # 0 = account, 1 = password, 2 = email
			'request_time'   => time(),
			'request_key'    => $key,
			'identity_id'    => empty($user['id']) ? 0 : (int)$user['id'],
			'user_nickname'  => $user['nickname'],
			'user_password'  => empty($user['password']) ? null : $user['password'],
			'user_email'     => (1 != $type) ? \Poodle\Input::lcEmail($user['email']) : '',
			'user_givenname' => $user['givenname'],
			'user_surname'   => $user['surname'],
			'user_details'   => empty($user['details']) ? '' : \Poodle::dataToJSON($user['details']),
		));
		return $key;
	}

}
