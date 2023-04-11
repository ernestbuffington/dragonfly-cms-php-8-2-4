<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

class Identity extends \Poodle\Identity
{
	const
		ANONYMOUS_ID = 1,

		LEVEL_DELETED = -1,
		LEVEL_USER    = 1,
		LEVEL_ADMIN   = 2,
		LEVEL_MOD     = 3;

	public
		$ip;

	protected
		$avatar_url,
		$profile_uri;

	protected static
		$v9_map = array(
		'username'  => 'nickname',
		'regdate'   => 'ctime',
		'lang'      => 'language',
		'lastvisit' => 'last_visit'
		);

	function __get($k)
	{
		if ('_mem_of_groups' === $k) {
			return $this->groups;
		}
		if ('profile_uri' === $k) {
			return $this->isMember() ? static::getProfileURL($this->user['id']) : null;
		}
		if ('avatar_url' === $k) {
			return \Dragonfly\Identity\Avatar::getURL($this);
		}
		$k = str_replace('user_','',$k);
		if ('ip' === $k) { return $this->ip; }
		if (isset(self::$v9_map[$k])) { $k = self::$v9_map[$k]; }
		return parent::__get($k);
	}

	function __isset($k)
	{
		$k = str_replace('user_','',$k);
		return parent::__isset(strtr($k,static::$v9_map));
	}

	function __set($k, $v)
	{
		$k = str_replace('user_','',$k);
		if ('ip' === $k) { $this->ip = $v; }
		else { parent::__set(strtr($k,static::$v9_map), $v); }
	}

	protected function init($user)
	{
		if ($user instanceof self) {
			foreach (get_object_vars($this) as $k => $v) {
				$this->$k = $user->$k;
			}
			return;
		}

		$this->user = array(
			'id'         => static::ANONYMOUS_ID, # not 0, else forums code fails
			'ctime'      => 0,
			'last_visit' => 0,
			'status'     => 0,     # 0=offline/hidden, 1=online
			'type'       => 1,     # 0=inactive, 1=active
			'nickname'   => '',
			'givenname'  => '',
			'surname'    => '',
			'email'      => '',
			'language'   => \Dragonfly::getKernel()->CFG->global->language,
			'timezone'   => date_default_timezone_get(), # One non-backward compatible of http://php.net/timezones
		);
		if ($user) {
			foreach ($this->user as $k => $v) {
				if (isset($user[$k])) {
					$this->user[$k] = is_int($v) ? (int)$user[$k] : trim($user[$k]);
				}
			}
		}
		$this->user['id'] = max(static::ANONYMOUS_ID, $this->user['id']);

		$this->initDetails();
		$this->initGroups();
		$this->auth_time = 0;
	}

	protected function getDetails()
	{
		$K = \Dragonfly::getKernel();
		$qr = $K->SQL->query("SELECT * FROM {$K->SQL->TBL->users} WHERE user_id={$this->user['id']}");
		$details = $qr->fetch_assoc();
		if (!$details) {
			$details = array();
			foreach ($qr->fetch_fields() as $field) {
				$details[$field->name] = $field->def;
			}
		}
		if (!$this->isMember()) {
			$details['user_posts'] = 0;
			$details['storynum'] = $K->CFG->global->storyhome;
		}
		unset(
			$details['user_id'],          # this->id
			$details['username'],         # this->nickname
			$details['user_email'],       # this->email
			$details['user_regdate'],     # this->ctime
			$details['user_lang'],        # this->language
			$details['user_timezone'],    # this->timezone
			$details['user_lastvisit'],   # this->last_visit
			$details['user_nickname_lc']
		);
		return $details;
	}

	protected function initDetails()
	{
		$details = $this->getDetails();
		foreach ($details as $k => $v) { $this->details[str_replace('user_','',$k)] = $v; }
	}

	public function initGroups()
	{
		$this->groups = array();
		$SQL = \Dragonfly::getKernel()->SQL;
		$member['_mem_of_groups'] = array();
		$result = $SQL->query("SELECT
			g.group_id,
			g.group_name,
			g.group_single_user
		FROM {$SQL->TBL->bbgroups} AS g
		INNER JOIN {$SQL->TBL->bbuser_group} AS ug ON (ug.group_id=g.group_id AND ug.user_id={$this->user['id']} AND ug.user_pending=0)");
		while ($group = $result->fetch_row()) {
			$this->groups[(int)$group[0]] = $group[2] ? '' : $group[1];
		}
	}

	public function setDetail($name, $value)
	{
		$name = str_replace('user_','',$name);
		if (array_key_exists($name, $this->details)) {
			$this->details[$name] = $value;
		}
	}

	public static function getCurrent()
	{
		return (empty($_SESSION['DF_VISITOR']) || empty($_SESSION['DF_VISITOR']->identity)) ? static::factory() : $_SESSION['DF_VISITOR']->identity;
	}

	public function setCurrent() {}

	public static function switchCurrentTo($id)
	{
		return isset($_SESSION['DF_VISITOR']) ? $_SESSION['DF_VISITOR']->switchIdentity($id) : false;
	}

	/**
	 * Store data unless there is no: user_nickname
	 */
	public function save()
	{
		$user = array(
			'username' => $this->user['nickname'],
			'user_nickname_lc' => mb_strtolower($this->user['nickname']),
			# Although some servers use the old RFC for case-sensitive email addresses
			# most don't and make our live much easier so we just lowercase them
			'user_email' => \Poodle\Input::lcEmail($this->user['email']),
			//'' as user_givenname,
			//'' as user_surname,
			'user_lang' => $this->user['language'],
			'user_timezone' => $this->user['timezone'],
			'user_allow_viewonline' => $this->user['status'],
			'user_level' => $this->user['type']
		);

		$details = $this->getDetails();

		$tbl = \Dragonfly::getKernel()->SQL->TBL->users;
		if (!$this->isMember()) {
			foreach ($details as $field => $value) {
				$dfield = str_replace('user_','',$field);
				if (array_key_exists($dfield,$this->details)) {
					$user[$field] = $this->details[$dfield];
				}
			}
			$this->user['last_visit'] = $user['user_lastvisit'] = time();
			$this->user['ctime'] = $user['user_regdate'] = time();
			$this->user['id'] = $tbl->insert($user,'user_id');
//			$this->addToGroup(1);
			\Dragonfly\Identity\Create::sendWelcomePM($this->user['id']);
			\Dragonfly\Identity\Create::notifyAdmin($this->user['nickname']);
		} else if (0 < $this->user['id']) {
			foreach ($details as $field => $value) {
				$dfield = str_replace('user_','',$field);
				if (array_key_exists($dfield,$this->details) && $value != $this->details[$dfield]) {
					$user[$field] = $this->details[$dfield];
				}
			}
			$tbl->update($user,"user_id={$this->user['id']}");
		}

		return $this->user['id'];
	}

	public function updateLastVisit()
	{
		if ($this->isMember()) {
			\Dragonfly::getKernel()->SQL->TBL->users->update(
				array('user_lastvisit'=>time()),
				"user_id={$this->user['id']}");
		}
	}

	public function isMember() { return static::ANONYMOUS_ID<$this->user['id'] ? $this->user['id'] : false; }

	public function isAdmin()  { return empty($_SESSION['DF_VISITOR']->admin) ? false : $_SESSION['DF_VISITOR']->admin->name; }

	public function isOnline() { return ($this->details['session_time'] > time()-300) && ($this->user['status'] || is_admin()); }

	public function inGroup($id) { return array_key_exists($id, $this->groups) ? $this->groups[$id] : false; }

	public function logout()
	{
		if (empty($_SESSION['Poodle']['PREV_IDENTITY'])) {
			if ($this->isMember()) {
				$SQL = \Dragonfly::getKernel()->SQL;
				$visitor_ip = $SQL->quoteBinary(\Dragonfly\Net::ipn());
				unset($_SESSION['CPG_SESS']['session_start']); # re-initialize session
				$SQL->query("DELETE FROM {$SQL->TBL->session} WHERE host_addr={$visitor_ip} AND guest<>1");
				$SQL->query("UPDATE {$SQL->TBL->users} SET user_session_time=".time()." WHERE user_id={$this->user['id']}");
			}
			$_SESSION['Poodle']['PREV_IDENTITY'] = 1;
			\Dragonfly\Identity\Cookie::remove();
		}
		parent::logout();
	}

	public static function loginURL($redirect = null)
	{
		if (!$redirect && preg_match('#login[&/]redirect_uri=#', $_SERVER['REQUEST_URI'])) {
			return $_SERVER['REQUEST_URI'];
		}
		return \URL::index('login&redirect_uri='.\Poodle\Base64::urlEncode($redirect?:$_SERVER['REQUEST_URI']));
	}

	public static function logoutURL($redirect = null)
	{
		return \URL::index('Your_Account&op=logout&redirect_uri='.\Poodle\Base64::urlEncode($redirect?:$_SERVER['REQUEST_URI']));
	}

	public static function getRegisterURL()
	{
		return \Dragonfly::getKernel()->CFG->member->allowuserreg
			? \URL::index('Your_Account&file=register')
			: null;
	}

	public static function getProfileURL($id)
	{
		if (!$id || (\Dragonfly::getKernel()->CFG->member->private_profile && !is_user())) {
			return null;
		}
		return \URL::index('Your_Account&profile='.rawurlencode($id));
	}

	public function getUploadUsage($module_id=0)
	{
		$module_id = (int)$module_id;
		$SQL = \Dragonfly::getKernel()->SQL;
		list($size) = $SQL->uFetchRow("SELECT
			SUM(upload_size)
		FROM {$SQL->TBL->users_uploads}
		WHERE identity_id = {$this->user['id']}".($module_id?" AND module_id={$module_id}":""));
		return (int)$size;
	}

	public function getUploadQuota()
	{
		if (!$this->isMember()) {
			return 0;
		}

		if (static::LEVEL_ADMIN == $this->user['type']) {
			return 2147483647; // 32bit = 2 GiB
		}

		$SQL = \Dragonfly::getKernel()->SQL;

		// Get Group Quota
		list($quota) = $SQL->uFetchRow("SELECT
			MAX(g.group_upload_quota)
		FROM {$SQL->TBL->bbuser_group} u
		INNER JOIN {$SQL->TBL->bbgroups} g ON (g.group_id = u.group_id AND g.group_single_user = 0)
		WHERE u.user_id = {$this->user['id']}");

		return 1024 * 1024 * max((int)$quota, (int)\Dragonfly::getKernel()->CFG->member->upload_quota);
	}

}

class_alias('Dragonfly\\Identity','Dragonfly_Identity');
