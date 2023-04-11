<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly;

abstract class Session implements \SessionHandlerInterface
{
	use \Poodle\Events;

	public $sess_id, $dbupdate; // old code compatibility

	protected
		$mode = 0,
		$old_handler,
		$timeout = 10800; # 3 hours

	public static function factory($config)
	{
		$class = $config->handler;
		return new $class($config);
	}

	function __construct($config)
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			throw new \Exception('Session already started!');
		}
		\Poodle\PHP\INI::set('session.gc_divisor', 100);
		\Poodle\PHP\INI::set('session.gc_probability', 1);
		\Poodle\PHP\INI::set('session.gc_maxlifetime', 1440); # seconds
		// PHP7 Cannot find serialization handler 'igbinary'
//		if (!(function_exists('igbinary_serialize') && \Poodle\PHP\INI::set('session.serialize_handler', 'igbinary'))
		\Poodle\PHP\INI::set('session.serialize_handler', 'php_serialize');
		# Modify PHP configuration to prevent:
		\Poodle\PHP\INI::set('session.use_only_cookies', 1); # SID in url
		\Poodle\PHP\INI::set('session.use_trans_sid', 0);    # SID ob
		\Poodle\PHP\INI::set('url_rewriter.tags', '');       # SID in tags
		$this->old_handler = \Poodle\PHP\INI::get('session.save_handler');
		$K = \Dragonfly::getKernel();
		$name = 'DF-' .sprintf('%u', crc32(\Dragonfly\Net::ipn().BASEHREF));
		if (!empty($config->timeout)) { $this->setTimeout($config->timeout); }
		# Start session
		session_name($name);
		if ($K) { session_set_cookie_params(0, $K->base_uri, $K->cookie_domain); } // [, bool secure], httponly = useless due to XMLHTTPRequest
//		if ($K) { session_set_cookie_params(0, $K->CFG->cookie->path, $K->CFG->cookie->domain, ('https' === $_SERVER['REQUEST_SCHEME'])); } // httponly = useless due to XMLHTTPRequest

		$this->start(isset($_COOKIE[$name]) ? $_COOKIE[$name] : null);

		if (empty($_SESSION['CPG_SESS']) && isset($_COOKIE[$name]) && session_id() == $_COOKIE[$name]) {
			$sid = $_COOKIE[$name];
			$this->delete();
			$this->start();
			if (session_id() == $sid) {
				$this->delete();
				cpg_error('Your cookie has expired, the page will be refreshed to set a new cookie.', 'Cookie expired', $_SERVER['REQUEST_URI']);
			}
		}

		# Session hijack attempt?
		if (!empty($_SESSION['_PID']) && $this->pid() !== $_SESSION['_PID']) {
			$pid = $_SESSION['_PID'];
			$this->delete();
			\Poodle\LOG::error('Session', "PID Incorrect,\nwas {$pid}\nnow:".$this->pid());
			cpg_error('Your cookie is invalid, the page will be refreshed to set a new cookie.', 'Cookie expired', $_SERVER['REQUEST_URI']);
		}

		# Session expired?
		if (!$_SESSION && isset($_COOKIE[$name]) && session_id() === $_COOKIE[$name]) {
			$this->start();
		}

		if (!$this->mode && !$_SESSION) $this->mode = 1;

		// old code compatibility
		$this->new = $this->is_new();
		$this->sess_id = session_id();

		$_SESSION['CPG_SESS'] = empty($_SESSION['CPG_SESS']) ? array() : $_SESSION['CPG_SESS'];
		$GLOBALS['CPG_SESS'] =& $_SESSION['CPG_SESS'];

		if (empty($_SESSION['DF_VISITOR']) || !($_SESSION['DF_VISITOR'] instanceof \Dragonfly\Session\Visitor)) {
			$_SESSION['DF_VISITOR'] = new \Dragonfly\Session\Visitor();
		}
	}

	function __destruct()
	{
		$this->write_close();
		if ($this->old_handler) { \Poodle\PHP\INI::set('session.save_handler', $this->old_handler); }
	}

	protected function setHandler()
	{
		if (!session_set_save_handler($this, false)) {
			throw new \Exception('Failed to set session handler');
		}
	}

	protected function after_start($id)
	{
		$this->setTimeout(\Poodle\PHP\INI::get('session.gc_maxlifetime'));
//		if (1 === random_int(1, (int)\Poodle\PHP\INI::get('session.gc_divisor')/\Poodle\PHP\INI::get('session.gc_probability')))
		if (1 === random_int(1, 100)) { $this->db_gc(); }
	}

	protected function before_write_close() {}

	public function is_new()  { return (1 === $this->mode); }

	public function timeout() { return $this->timeout; }

	public function setTimeout($time)
	{
		// if $time > 300 then $time is in seconds else in minutes
		$time = (int)$time;
		$this->timeout = min(32767, max(1800, 300>$time?$time*60:$time));
	}

	final public function delete()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION = array();
			if (isset($_COOKIE[session_name()])) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', -1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
				unset($_COOKIE[session_name()]);
			}
			$K = \Dragonfly::getKernel();
			if ($K && $K->SQL && isset($K->SQL->TBL->sessions)) {
				$K->SQL->TBL->sessions->delete('sess_id='.$K->SQL->quote(session_id()));
			}
			return session_destroy();
		}
	}

	final public function abort()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			// PHP 5.6
			if (function_exists('session_abort')) {
				session_abort();
			} else {
				// TODO: use copy of $_SESSION at start as $_SESSION = $TMP?
				$this->write_close();
			}
		}
	}

	final public function reset()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			// PHP 5.6
			if (function_exists('session_reset')) {
				session_reset();
			} else {
				// TODO: use copy of $_SESSION at start as $_SESSION = $TMP?
			}
		}
	}

	final public function write_close()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			if (defined('SEARCHBOT') && SEARCHBOT) {
				$this->delete();
			} else {
				try {
					$_SESSION['_PID'] = $this->pid();
					$this->triggerEvent('beforeWriteClose');
					$this->before_write_close();
					global $module_name;
					if (defined('ADMIN_PAGES') && (!isset($_SERVER['REDIRECT_STATUS']) || 404 != $_SERVER['REDIRECT_STATUS'])) {
						$_SESSION['SECURITY']['page'] = $module_name;
						$_SESSION['CPG_SESS']['admin']['page'] = $_GET->txt('op') ?: $_POST->txt('op');
					}
					$_SESSION['CPG_SESS']['user']['page'] = $module_name;
					$_SESSION['CPG_SESS']['user']['file'] = $_GET->txt('file') ?: $_POST->txt('file');
					$_SESSION['CPG_SESS']['user']['uri'] = $_SERVER['REQUEST_URI'];
					session_write_close();
				} catch (\Exception $e) {
					error_log(__CLASS__ . ' ' . $e->getMessage()."\n".$e->getTraceAsString());
				}
			}
		}
	}

	public function db_write($id, $value = '')
	{
		$K = \Dragonfly::getKernel();
		if (!$K || !$K->SQL || !isset($K->SQL->TBL->sessions)) { return false; }
		$SQL = $K->SQL;
		$row = array(
			'sess_expiry' => time() + $this->timeout,
			'sess_ip'     => $SQL->quote($_SERVER['REMOTE_ADDR']),
			'sess_value'  => $SQL->quoteBinary($value),
			'identity_id' => $SQL->quote($K->IDENTITY->id),
			'sess_uri'    => $SQL->quote($_SERVER['REQUEST_URI']),
		);
		if ($this->is_new()) {
			$row['sess_id']      = $SQL->quote($id);
			$row['sess_timeout'] = $this->timeout;
			$row['sess_user_agent'] = $SQL->quote($_SERVER['HTTP_USER_AGENT']);
			return $SQL->TBL->sessions->insertPrepared($row);
		}
		return $SQL->TBL->sessions->updatePrepared($row, 'sess_id='.$SQL->quote($id));
	}

	# garbage collector
	public function db_gc()
	{
		$K = \Dragonfly::getKernel();
		if ($K && $K->SQL && isset($K->SQL->TBL->sessions)) {
			$K->SQL->TBL->sessions->delete('sess_expiry < '.time());
			$K->SQL->TBL->session->delete('time < '. (time() - DF_SESSION_FREQ_CLEAR_DB));
		}
		return true;
	}

	private function start($id=null)
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			$this->delete();
		}
		$this->setHandler(); # http://bugs.php.net/bug.php?id=32330
		session_id($id?$id:sha1(session_name().microtime()));
//		session_cache_limiter('');
		if (session_start()) {
			# calls save_handler read method
			$this->after_start(session_id());
		} else {
			$error = error_get_last();
			cpg_error("Session start failed: {$error['message']}");
		}
	}

	# Create Protection ID
	protected static function pid()
	{
		static $pid;
		if (!$pid) {
			// Can't use all $_SERVER['HTTP_*'] vars because MSIE changes them randomly
			// Firefox changes when FirePHP/0.6 is active or not
			// So we only use a partial string and the stable ua detect object
			$pid = md5(
				substr($_SERVER['HTTP_USER_AGENT'],0,strpos($_SERVER['HTTP_USER_AGENT'],')'))
				.json_encode(\Poodle\UserAgent::getInfo())
			);
		}
		return $pid;
	}

	public function init_info()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$ID = \Dragonfly::getKernel()->IDENTITY;
/*
		Only this file:
		- session_start stores when member started to view website
		- user_session_time store current visit
		BB :
		- user_lastvisit stores previous session end
		  recieved from $ID->session_time
*/
		if (!isset($_SESSION['CPG_SESS']['session_start'])) {
			if ($ID->id > 1) {
				$ID->lastvisit = ($ID->session_time > 0) ? $ID->session_time : time();
				$SQL->TBL->users->updatePrepared(array(
					'user_session_time' => time(),
					'user_lastvisit' => $ID->lastvisit
				), "user_id={$ID->id}");
			}
			$_SESSION['CPG_SESS']['session_start'] = $_SESSION['CPG_SESS']['session_time'] = time();
			$this->dbupdate = true;
		} else if (DF_SESSION_FREQ_UPDATE_DB < (time() - $_SESSION['CPG_SESS']['session_time'])) {
			$_SESSION['CPG_SESS']['session_time'] = time();
			if ($ID->id > 1) {
				list($user_level, $new, $unread) = $SQL->uFetchRow("SELECT
					user_level, user_new_privmsg, user_unread_privmsg
					FROM {$SQL->TBL->users} WHERE user_id={$ID->id}");
				if ($user_level < 1) {
					$this->delete();
					\URL::redirect();
				}
				$ID->new_privmsg = (int)$new;
				$ID->unread_privmsg = (int)$unread;
				$SQL->TBL->users->updatePrepared(array('user_session_time' => time()), "user_id={$ID->id}");
			}
			$this->dbupdate = true;
		}
		$ID->session_time = time();
	}

	// Used by Who_where and User_Info blocks
	public function online()
	{
		if ($this->dbupdate) {
			$K = \Dragonfly::getKernel();
			$ID = $K->IDENTITY;
			$uname = session_id();
			$guest = 1;
			if ($ID->isMember()) {
				$uname = $ID->nickname;
				$guest = 0;
			} else if ($ID->isAdmin()) {
				$uname = $_SESSION['DF_VISITOR']->admin->name;
				$guest = 2;
				return;
			} else if (defined('SEARCHBOT') && SEARCHBOT) {
				$uname = SEARCHBOT;
				$guest = 3;
			}
			if (empty($uname)) {
				return; # something screwey
			}
			$tbl = $K->SQL->TBL->session;
			$data = array(
				'identity_id' => $ID->id,
				'time'   => time(),
				'module' => empty($GLOBALS['module_title']) ? _HOME : $GLOBALS['module_title'],
				'url'    => defined('ADMIN_PAGES') ? \Dragonfly::$URI_INDEX : mb_substr($_SERVER['REQUEST_URI'],0,255),
				'guest'  => $guest,
			);
			$tbl->update($data, array('uname'=>$uname));
			if (!$K->SQL->affected_rows) {
				$data['uname'] = $uname;
				$data['host_addr'] = \Dragonfly\Net::ipn();
				try {
					$tbl->insert($data);
				} catch (\Exception $e) {}
			}
		}
	}

}
