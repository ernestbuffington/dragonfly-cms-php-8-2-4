<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

class Identity implements \ArrayAccess, \Iterator
{
	protected
		$auth_time = 0,
		$ACL = null,
		$user = array(),
		$groups = array(),
		$details = array();

	protected static
		$details_ids = null;

	const
		TYPE_REMOVED  = -1,
		TYPE_INACTIVE = 0,
		TYPE_ACTIVE   = 1;

	/**
	 * $user is array properties
	 */
	function __construct(array $data=array())
	{
		$this->init($data);
	}

	function __get($k)
	{
		if ('id' === $k) { return $this->user['id']; }
		if ('auth_time' === $k) { return $this->auth_time; }
		if ('fullname' === $k) { return $this->user['givenname'].' '.$this->user['surname']; }
		if ('groups' === $k) { return $this->groups; }
		if ('ACL' === $k) {
			if (!$this->ACL) { $this->ACL = new \Poodle\ACL\Groups(array_keys($this->groups)); }
			return $this->ACL;
		}
		if (array_key_exists($k, $this->user)) { return $this->user[$k]; }
		if (array_key_exists($k, $this->details)) { return $this->details[$k]; }
		\Poodle\Debugger::trigger("Undefined property {$k}", __CLASS__);
	}

	function __isset($k)
	{
		if ('fullname' === $k || 'auth_time' === $k || 'ACL' === $k) { return true; }
		return array_key_exists($k, $this->user) || array_key_exists($k, $this->details);
	}

	function __set($k, $v)
	{
		if ('id' === $k || 'ctime' === $k) { return; }
		if (array_key_exists($k, $this->user)) {
			$v = trim($v);
			$K = \Poodle::getKernel();
			if ('nickname' === $k) {
				if (!$v || mb_strlen($v) < max(1,$K->CFG->identity->nick_minlength)) {
					throw new \Exception(sprintf($K->L10N['%s is too short.'], $K->L10N['Nickname']));
				}
				if ($K->CFG->identity->nick_invalidchars && preg_match('#(['.preg_quote($K->CFG->identity->nick_invalidchars, '#').'])#', $v, $match)) {
					throw new \Exception(sprintf($K->L10N['%s contains disallowed character %s.'], $K->L10N['Nickname'], $match[1]));
				}
				if ($K->SQL->count('users', "user_nickname_lc=".$K->SQL->quote(mb_strtolower($v))." AND NOT user_id={$this->user['id']}")) {
					throw new \Exception(sprintf($K->L10N['%s is already in use.'], $K->L10N['Nickname']));
				}
			} else
			if ('email' === $k) {
				$v = \Poodle\Input::lcEmail($v);
				\Dragonfly\Net\Validate::emailaddress($v,1);
			}
			$this->user[$k] = is_int($this->user[$k]) ? (int)$v : trim($v);
			return;
		}
		if (array_key_exists($k, $this->details)) {
			$this->setDetail($k, $v);
		} else {
			\Poodle\Debugger::trigger("Undefined property {$k}", __CLASS__);
		}
	}

	function __call($name, array $arguments)
	{
		// ignore unknown methods as an instance of this can be
		// created instead of the website custom identity class
		// which may contain custom methods
	}

	public static function factory($data=array())
	{
		$class = self::getDefaultClassName();
		return new $class($data);
	}

	protected static function getDefaultClassName()
	{
		$class = $def_class = 'Poodle\\Identity';
		$K = \Poodle::getKernel();
		if ($K && !empty($K->CFG) && $K->CFG->poodle && !empty($K->CFG->poodle->identity_class)) {
			$class = $K->CFG->poodle->identity_class;
		}
		if ($class !== $def_class && !is_subclass_of($class, $def_class)) {
			$class = $def_class;
		}
		return $class;
	}

	protected function init($user)
	{
		if ($user instanceof \Poodle\Identity) {
			foreach (get_object_vars($this) as $k => $v) {
				$this->$k = $user->$k;
			}
			return;
		}
		$K = \Poodle::getKernel();

		$this->user = array(
			'id'         => 0,
			'ctime'      => 0,
			'last_visit' => 0,
			'status'     => 0,     // 0=offline/hidden, 1=online
			'type'       => 1,     // 0=inactive, 1=active
			'nickname'   => '',
			'givenname'  => '',
			'surname'    => '',
			'email'      => '',
			'language'   => empty($K->CFG) ? 'en' : $K->CFG->poodle->l10n_default,
			'timezone'   => date_default_timezone_get(), // One non-backward compatible of http://php.net/timezones
		);
		if ($user) {
			foreach ($this->user as $k => $v) {
				if (isset($user[$k])) {
					$this->user[$k] = is_int($v) ? (int)$user[$k] : trim($user[$k]);
				}
			}
		}

		$this->initDetails();
		$this->initGroups();
		$this->auth_time = 0;
	}

	protected function initDetails()
	{
	}

	public function initGroups()
	{
		$this->groups = array();
		$this->ACL    = null;
	}

	public function setDetail($name, $value)
	{
	}

	public static function getCurrent()
	{
		$ID = null;

		if (!empty($_SESSION['Poodle']['IDENTITY'])) {
			$ID = $_SESSION['Poodle']['IDENTITY'];
			$class = self::getDefaultClassName();
			if (!($ID instanceof $class)) {
				$ID = null;
			}
		}

		if (!$ID) {
			$ID = static::factory();

			# No Identity, try cookie
			$AC = \Poodle::getKernel()->CFG->auth_cookie;
			if (!empty($AC->allow)) {
				$result = $ID->authenticate($_COOKIE, array(
					'class' => isset($AC->class) ? $AC->class : 'Poodle\\Auth\\Provider\\Cookie'
				));
				if ($result instanceof \Poodle\Auth\Result\Success && $result->user->id) {
					$ID->updateLastVisit();
				}
			}
			$ID->setCurrent();
		}

		if ($ID->isMember()) {
			if ($ID->timezone && !date_default_timezone_set($ID->timezone)) {
				$ID->timezone = date_default_timezone_get();
				$ID->save();
			}
//			$K->SESSION->addEventListener('beforeWriteClose', array(__CLASS__,'beforeSessionClose'));
		}

		return $ID;
	}

	public function setCurrent()
	{
		$_SESSION['Poodle']['IDENTITY'] = $this;
	}

	/**
	 * Impersonate user
	 */
	public static function switchCurrentTo($id)
	{
		if (\Poodle::getKernel()->IDENTITY->isAdmin()) {
			$ID = \Poodle\Identity\Search::byID($id);
			if ($ID) {
				$_SESSION['Poodle']['PREV_IDENTITY'] = $_SESSION['Poodle']['IDENTITY']->id;
				$ID->setCurrent();
				return true;
			}
		}
		return false;
	}

	/**
	 * Use on current logged in visitor only
	 */
	public function beforeSessionClose(\Poodle\Events\Event $event)
	{
	}

	/**
	 * Store data unless there is no: user_nickname, user_email or authentication
	 */
	public function save()
	{
		$user = array();

		foreach ($this->user as $k => $v) {
			if ('id' !== $k) { $user['user_'.$k] = $v; }
		}
		$user['user_nickname_lc'] = mb_strtolower($user['user_nickname']);
		$user['user_default_status'] = $user['user_status'];
		unset($user['user_status']);
		// Although some servers use the old RFC for case-sensitive email addresses
		// most don't and make our live much easier so we just lowercase them
		$user['user_email'] = \Poodle\Input::lcEmail($user['user_email']);

		$tbl = \Poodle::getKernel()->SQL->TBL->users;
		if (empty($this->user['id'])) {
			$this->user['ctime'] = $user['user_ctime'] = time();
			$this->user['id'] = $tbl->insert($user,'identity_id');
//			$this->addToGroup(1);
		} else if (0 < $this->user['id']) {
			unset($user['user_ctime'], $user['user_last_visit']);
			$tbl->update($user,"identity_id={$this->user['id']}");
		}

		return $this->user['id'];
	}

	public function addToGroup($group_id)
	{
		return false;
	}

	public function removeFromGroup($group_id)
	{
		return false;
	}

	public function setGroups(array $group_ids)
	{
		return false;
	}

	public function updateAuth($provider, $claimed_id, $password = null)
	{
		return \Poodle\Auth::update($provider, new \Poodle\Auth\Credentials($this, $claimed_id, $password));
	}

	public function updateLastVisit()
	{
		if ($this->isMember()) {
			\Poodle::getKernel()->SQL->TBL->users->update(
				array('user_last_visit'=>time()),
				"identity_id={$this->user['id']}");
		}
	}

	public function isMember() { return 0<$this->user['id']; }

	public function isAdmin()  { return 0<$this->user['id'] && $this->inGroup(3) && $this->__get('ACL')->admin(); }

	public function inGroup($id) { return array_key_exists($id, $this->groups); }

	/**
	 * Authentication methods
	 */

	public function logout()
	{
		// reset whole object first
		foreach (get_object_vars($this) as $k => $v) { $this->$k = null; }

		$user = array();
		if (!empty($_SESSION['Poodle']['PREV_IDENTITY'])) {
			$user = \Poodle\Identity\Search::byID($_SESSION['Poodle']['PREV_IDENTITY']);
		} else {
			\Poodle\Auth\Provider\Cookie::remove();
		}
		$this->init($user);
		$this->auth_time = time();
		unset($_SESSION['Poodle']['PREV_IDENTITY']);
	}

	/**
	 * Check the given credentials
	 * @param array $credentials
	 * @param array|null $provider info about the chosen authentication provider
	 * @return int|false userid on success, false on failure
	 */
	public function authenticate($credentials, $provider_config=null)
	{
		// get provider
		if (!$provider_config) {
			$auth_provider = \Poodle\Auth\Detect::provider($credentials['auth_claimed_id']);
		}
		else if (is_object($provider_config)) {
			$auth_provider = $provider_config;
		}
		else if (!empty($provider_config['class'])) {
			$auth_provider = new $provider_config['class'];
		}

		if (empty($auth_provider)) {
			throw new \Exception('$auth_provider not found');
		}

		if (!($auth_provider instanceof \Poodle\Auth\Provider)) {
			throw new \Exception('$auth_provider not an instanceof \Poodle\Auth\Provider');
		}

		// authenticate
		$result = $auth_provider->authenticate($credentials);
		// store identity data
		if ($result instanceof \Poodle\Auth\Result\Success) {
			if ($auth_provider->is_2fa || !$result->user->has2FA()) {
				$this->init($result->user);
				$this->auth_time = time();
			}
		}

		return $result;
	}

	public function has2FA()
	{
		$SQL = \Poodle::getKernel()->SQL;
		$result = $SQL->uFetchRow("SELECT
			COUNT(*)
		FROM {$SQL->TBL->auth_identities}
		INNER JOIN {$SQL->TBL->auth_providers} USING (auth_provider_id)
		WHERE identity_id = {$this->user['id']}
		  AND auth_provider_is_2fa = 1
		  AND auth_provider_mode > 0");
		return 0 < $result[0];
	}

	# ArrayAccess
	public function offsetExists($k)  { return $this->__isset($k); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) { $this->__set($k, $v); }
	public function offsetUnset($k)   {}

	# Iterator
	private $_iterator_valid;
	public function rewind()  { $this->_iterator_valid = (false !== reset($this->user)); }
	public function valid()   { return $this->_iterator_valid; }
	public function current() { return current($this->user); }
	public function key()     { return key($this->user); }
	public function next()    { $this->_iterator_valid = (false !== next($this->user)); }
}
