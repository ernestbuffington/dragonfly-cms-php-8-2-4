<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/classes/cpg_member.php,v $
  $Revision: 9.42 $
  $Author: nanocaiordo $
  $Date: 2007/12/12 12:54:17 $
**********************************************/

class cpg_member {

	public $members = array();
	public $user_id; // Member ID
	public $admin;
	public $admin_id;
	public $demo;

	// Constructor
	/***********************************************************************************
	  Decodes and checks the member cookie from the *_users table.
	  NOTE: The global $userinfo contains all of this user's information
	************************************************************************************/
	function __construct() {
		global $db, $user_prefix, $prefix, $MAIN_CFG, $SESS;
		if ($this->user_id) return false;
		$cookiename = $MAIN_CFG['cookie']['member'];
		# MySQL has an SQL99 ISO incompatibility because it rtrim()s
		# one specific binary data char (\x00 or \x20 version depended)
		# Due to that we must pad our string with another character.
		$visitor_ip = $db->binary_safe(Security::get_ip());
		# Load Member cookie
		$m_cookie   = $_COOKIE[$cookiename] ?? false;
		# Member Logout
		if ($m_cookie && !defined('ADMIN_PAGES') && isset($_GET['op']) && $_GET['op'] == 'logout') {
			global $CPG_SESS;
			unset($CPG_SESS['session_start']); # re-initialize session
			$_SESSION['CPG_USER'] = false;
			$m_cookie = explode(':', base64_decode($m_cookie));
			$db->sql_query('DELETE FROM '.$prefix."_session WHERE host_addr=$visitor_ip AND guest<>1");
			$db->sql_query('UPDATE '.$user_prefix.'_users SET user_session_time='.gmtime().' WHERE user_id='.intval($m_cookie[0]));
			$this->setmemcookie();
			$m_cookie = false;
		}
		# Or Member Login
		elseif (!$m_cookie && isset($_POST['ulogin'])) {
			$m_cookie = $this->_loginmember($visitor_ip);
		}
		$uid = 1;
		$pwd = '';
		if ($m_cookie) {
			$m_cookie = explode(':', base64_decode($m_cookie));
			$uid = intval($m_cookie[0]);
			$pwd = $m_cookie[2];
		}
		$uid = ($uid > 1) ? $uid : 1;
		# If the current session already has the Member details
		# load them else query the database
		if (isset($_SESSION['CPG_USER']) && is_array($_SESSION['CPG_USER']) &&
			$_SESSION['CPG_USER']['user_id'] == $uid && $_SESSION['CPG_USER']['user_password'] == $pwd)
		{
			$member = $_SESSION['CPG_USER'];
		} else {
			$result = $db->sql_query('SELECT * FROM '.$user_prefix.'_users WHERE user_id='.$uid);
			if ($db->sql_numrows($result) < 1) {
				$this->setmemcookie();
				$db->sql_freeresult($result);
				$result = $db->sql_query('SELECT * FROM '.$user_prefix.'_users WHERE user_id=1');
			}
			$member = $db->sql_fetchrow($result, SQL_ASSOC);
			$db->sql_freeresult($result);
			if ($member['user_id'] > 1 && ($member['user_password'] != $pwd || $member['user_password'] == '' || intval($member['user_level']) < 1)) {
				$this->setmemcookie();
				$member = $db->sql_ufetchrow('SELECT * FROM '.$user_prefix.'_users WHERE user_id=1', SQL_ASSOC);
				$db->sql_freeresult($result);
			}
		}
		$this->user_id = $member['user_id'];
		if ($this->user_id > 1) {
//		if ($this->user_id > 1 && !isset($member['_mem_of_groups'])) {
			$member['_mem_of_groups'] = array();
			$result = $db->sql_uquery('SELECT g.group_id, g.group_name, g.group_single_user FROM '.$prefix.'_bbgroups AS g INNER JOIN '.$prefix.'_bbuser_group AS ug ON (ug.group_id=g.group_id AND ug.user_id='.$this->user_id.' AND ug.user_pending=0)');
			while (list($g_id, $g_name, $single) = $db->sql_fetchrow($result, SQL_NUM)) {
				$member['_mem_of_groups'][$g_id] = ($single)?'':$g_name;
			}
		} else {
			$member['user_dst'] = $member['user_timezone'] = 0;
		}
		$member['user_ip'] = $visitor_ip;
		$_SESSION['CPG_USER'] = $member;
		$this->members[$this->user_id] =& $_SESSION['CPG_USER'];
//		$this->members[$this->user_id] = $member;
		$this->admin_id = false;
	}

	# Should be 'private' function in PHP 5
	# or 'protected' so that a subclass can still use it
	function _loginmember($visitor_ip) {
		global $db, $prefix, $user_prefix, $sec_code, $CPG_SESS;
		$username = Fix_Quotes($_POST['ulogin']);
		$result = $db->sql_query('SELECT user_id, username, user_password, user_level, theme FROM '.$user_prefix."_users WHERE username='$username' AND user_id>1");
		if ($db->sql_numrows($result) < 1) {
			url_redirect(getlink('Your_Account&error=1&uname='.urlencode(base64_encode($username))), true);
		}
		$setinfo = $db->sql_fetchrow($result, SQL_ASSOC);
		if ($setinfo['user_password'] != '' && $setinfo['user_level'] > 0) {
			$pass = md5($_POST['user_password']);
			if ($setinfo['user_password'] != $pass) { url_redirect(getlink('Your_Account&error=2'), true); }
			if ($sec_code & 2) {
				$gfxid = $_POST['gfxid'] ?? 0;
				$code = $CPG_SESS['gfx'][$gfxid];
				$gfx_check  = $_POST['gfx_check'] ?? '';
				if (strlen($gfx_check) < 2 || $code != $gfx_check) { url_redirect(getlink('Your_Account&error=2'), true); }
			}
			$db->sql_query('DELETE FROM '.$prefix."_session WHERE host_addr=$visitor_ip AND guest=1");
			unset($CPG_SESS['session_start']);
			$CPG_SESS['theme'] = $setinfo['theme'];
			return $this->setmemcookie($setinfo['user_id'], $pass, false);
		} else {
			if ($setinfo['user_level'] == 0) { url_redirect(getlink('Your_Account&profile='.$setinfo['user_id'])); }
			else if ($setinfo['user_level'] == -1) { url_redirect(getlink('Your_Account&profile='.$setinfo['user_id'])); }
			url_redirect(getlink('Your_Account&error=2'), true);
		}
	}

	/***********************************************************************************
	  Fetch data out of the *_users table from the given member returned in a array.
		$user: username or user_id
		$data: the specific data you want from that user seperated by comma's, default = '*' (all fields)
	************************************************************************************/
	function getmemdata($user, $data='*') {
		if (is_numeric($user)) {
			if (isset($this->members[$user])) {
				if ($data == '*') { return $this->members[$user]; }
				else {
					$data = explode(',', $data);
					foreach ($data as $row) {
						$row = trim($row);
						$info[$row] = $this->members[$user][$row];
					}
					return $info;
				}
			}
		} else {
			foreach($this->members AS $member) {
				if ($member['username'] == $user) {
					if ($data == '*') { return $member; }
					else {
						$data = explode(',', $data);
						foreach ($data as $row) {
							$row = trim($row);
							$info[$row] = $member[$row];
						}
						return $info;
					}
				}
			}
		}
		global $db, $user_prefix;
		$info = $db->sql_ufetchrow("SELECT $data FROM ".$user_prefix.'_users WHERE '.(is_numeric($user) ? "user_id=$user" : "username='".Fix_Quotes($user)."'") . ' AND user_id > 1', SQL_ASSOC);
		if ($data == '*' && $info) {
			$this->members[$info['user_id']] = $info;
		}
		return $info;
	}

	function setmemcookie($setuid=false, $setpass='', $secure=false) {
		global $MAIN_CFG;
		if (!$setuid) {
			if (isset($_COOKIE[($MAIN_CFG['cookie']['member'])])) {
//				if ($this->local) setcookie($MAIN_CFG['cookie']['member'],'',-1, $MAIN_CFG['cookie']['path']);
				setcookie($MAIN_CFG['cookie']['member'],'', ['expires' => -1, 'path' => $MAIN_CFG['cookie']['path'], 'domain' => $MAIN_CFG['cookie']['domain']]); //, int secure
			}
			return false;
		} else {
			$data = base64_encode("$setuid:$secure:$setpass");
//			if ($this->local) setcookie($MAIN_CFG['cookie']['member'],$data,(gmtime()+15552000), $MAIN_CFG['cookie']['path']);
			setcookie($MAIN_CFG['cookie']['member'],$data, ['expires' => gmtime()+15552000, 'path' => $MAIN_CFG['cookie']['path'], 'domain' => $MAIN_CFG['cookie']['domain']]); //, int secure
			return $data;
		}
	}

	/***********************************************************************************
	  Decodes and checks the admin cookie from the *_admins table
	  and returns the admin id.
	************************************************************************************/
	function loadadmin() {
		if ($this->admin_id) return $this->admin_id;
		global $MAIN_CFG, $SESS;
		$cookiename = $MAIN_CFG['cookie']['admin'];
		$admin = $_COOKIE[$cookiename] ?? false;
		if (!$admin) {
			if (defined('ADMIN_PAGES') && isset($_POST['alogin'])) {
				$result = $this->_loginadmin();
				$_SESSION['CPG_ADMIN'] = $result;
				return $result;
			}
			$_SESSION['CPG_ADMIN'] = false;
			return false;
		}
		if (defined('ADMIN_PAGES') && isset($_GET['op']) && $_GET['op'] == 'logout') {
			$_SESSION['CPG_ADMIN'] = false;
			$this->setadmcookie();
			return false;
		}
		$admin = explode(':', base64_decode($admin));
		$aid = intval($admin[0]);
		$pwd = $admin[1];
		$stay_alive = intval($admin[2]);
		if (isset($_SESSION['CPG_ADMIN']) && is_array($_SESSION['CPG_ADMIN']) &&
			$_SESSION['CPG_ADMIN']['admin_id'] == $aid && $_SESSION['CPG_ADMIN']['pwd'] == $pwd)
		{
			$row = $_SESSION['CPG_ADMIN'];
		} else {
			global $db, $prefix;
			$_SESSION['CPG_ADMIN'] = false;
			$result = $db->sql_query('SELECT * FROM '.$prefix.'_admins WHERE admin_id='.$aid);
			$row = ($db->sql_numrows($result) > 0) ? $db->sql_fetchrow($result, SQL_ASSOC) : array('pwd'=>'');
		}
		if ($aid < 1 || $pwd == '' || $row['pwd'] != $pwd) {
			$this->setadmcookie();
			return false;
		} else {
			if (defined('ADMIN_PAGES') && $stay_alive) {
				$this->setadmcookie(true, $aid, $pwd, true);
			}
			$_SESSION['CPG_ADMIN'] = $row;
			unset($row['pwd']);
			$this->admin = $row;
			$this->admin_id = $row['aid'];
			$this->demo = (CPGN_DEMO && preg_match($this->admin_id, 'demo'));
		}
		return $this->admin_id;
	}

	# Should be 'private' function in PHP 5
	# or 'protected' so that a subclass can still use it
	function _loginadmin() {
		$aid = isset($_POST['alogin']) ? Fix_Quotes($_POST['alogin']) : NULL;
		$pwd = $_POST['pwd'] ?? NULL;
		if ($aid && $pwd) {
			global $sec_code, $CPG_SESS;
			if ($sec_code & 1) {
				$gfxid = $_POST['gfxid'] ?? 0;
				$code = $CPG_SESS['gfx'][$gfxid];
				$gfx_check  = $_POST['gfx_check'] ?? '';
				if (strlen($gfx_check) < 2 || $code != $gfx_check) { return false; }
			}
			global $db, $prefix;
			$pwd = md5($pwd);
			$result = $db->sql_query('SELECT * FROM '.$prefix."_admins WHERE aid='$aid'");
			$row = $db->sql_fetchrow($result, SQL_ASSOC);
			if (isset($row['admin_id'])) {
				if (!($login = Cache::array_load('login', 'a', false)) || !isset($login[$row['admin_id']])) {
					$login[$row['admin_id']] = 1;
				} else if ($login[$row['admin_id']] >= 5) {
					cpg_error('Too many failed login attempts');
				} else {
					$login[$row['admin_id']]++;
				}
				if ($row['pwd'] == $pwd && $row['pwd'] != '') {
					$this->setadmcookie(true, $row['admin_id'], $pwd, isset($_POST['persistent']));
					unset($row['pwd']);
					$this->admin = $row;
					$this->admin_id = $row['aid'];
					$this->demo = (CPGN_DEMO && preg_match($this->admin_id, 'demo'));
					unset($CPG_SESS['admin']);
					$login[$row['admin_id']] = 1;
				}
				Cache::array_save('login', 'a', $login);
			}
		}
		return $this->admin_id;
	}

	function setadmcookie($setaid=false, $id=0, $pwd='', $persistent=false, $secure=false) {
		global $MAIN_CFG;
		if (!$setaid) {
			if (!isset($_COOKIE[($MAIN_CFG['cookie']['admin'])])) { return; }
			$data = '';
			$time = -1;
		} else {
			$data = base64_encode("$id:$pwd:$persistent");
			$time = ($persistent ? (gmtime()+(86400*30)) : 0);
		}
//		if ($this->local) setcookie($MAIN_CFG['cookie']['admin'], $data, $time, $MAIN_CFG['cookie']['path']);
		setcookie($MAIN_CFG['cookie']['admin'], $data, ['expires' => $time, 'path' => $MAIN_CFG['cookie']['path'], 'domain' => $MAIN_CFG['cookie']['domain']]); //, int secure
	}

}
