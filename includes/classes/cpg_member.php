<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

class cpg_member
{

	private
		$members = array(),
		$user_id; // Member ID

	// Constructor
	/***********************************************************************************
	  NOTE: The global $userinfo contains all of this user's information
	************************************************************************************/
	public function __construct()
	{
		if ($this->user_id) return false;

		$ID = Dragonfly::getKernel()->IDENTITY;

		# Try v9 based login
		if (!$ID->isMember())
		{
			if (isset($_POST['ulogin'])) {
				\Dragonfly\Identity\Login::member();
			}
		}
		# Member Logout
		else if (!defined('ADMIN_PAGES') && isset($_GET['op']) && $_GET['op'] == 'logout')
		{
			$ID->logout();
		}
		# Member
		else {
			if ($ID->timezone && !date_default_timezone_set($ID->timezone)) {
				$ID->timezone = date_default_timezone_get();
//				$ID->save();
			}
		}

		$ID->user_ip   = Dragonfly::getKernel()->SQL->binary_safe(\Dragonfly\Net::ipn());
		$this->user_id = $ID->id;
		$this->members[$this->user_id] = $ID;

		$_SESSION['CPG_USER'] = new CPG_User(); // Df < 10 legacy workaround
	}

	/***********************************************************************************
	  Fetch data out of the *_users table from the given member returned in a array.
		$user: username or user_id
		$data: the specific data you want from that user seperated by comma's, default = '*' (all fields)
	************************************************************************************/
	public function getmemdata($user, $data='*')
	{
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
		$SQL = Dragonfly::getKernel()->SQL;
		$info = $SQL->uFetchAssoc("SELECT {$data} FROM {$SQL->TBL->users} WHERE ".(is_numeric($user) ? "user_id={$user}" : "username={$SQL->quote($user)}") . ' AND user_id > 1');
		if ($data == '*' && $info) {
			$this->members[$info['user_id']] = $info;
		}
		return $info;
	}

	/***********************************************************************************
	  Returns the admin name or false
	************************************************************************************/
	public function loadadmin()
	{
		if (defined('ADMIN_PAGES')) {
			if (isset($_GET['op']) && $_GET['op'] == 'logout') {
				$_SESSION['DF_VISITOR']->admin->logout();
			} else if (isset($_POST['alogin'])) {
				\Dragonfly\Identity\Login::admin();
			}
		}

		if (empty($_SESSION['DF_VISITOR']->admin)) {
			unset($_SESSION['CPG_ADMIN']); // Df < 10 legacy workaround
			return false;
		}
		$_SESSION['CPG_ADMIN'] = new CPG_Admin(); // Df < 10 legacy workaround
		return $_SESSION['DF_VISITOR']->admin->name;
	}

	function __get($key)
	{
		switch ($key)
		{
		case 'demo':
			trigger_error('CLASS[member] use Dragonfly::isDemo()', E_USER_DEPRECATED);
			return $this->demo;
		case 'admin':
			return empty($_SESSION['DF_VISITOR']->admin) ? null : $_SESSION['DF_VISITOR']->admin;
		case 'admin_id':
			trigger_error('CLASS[member][admin_id] use is_admin()', E_USER_DEPRECATED);
			return $_SESSION['DF_VISITOR']->admin->name;
		case 'members':
			return $this->members;
		case 'user_id':
			trigger_error('CLASS[member][user_id] use Dragonfly::getKernel()->IDENTITY->id', E_USER_DEPRECATED);
			return Dragonfly::getKernel()->IDENTITY->id;
		}
	}

}

/**
 * Df < 10 legacy workarounds
 */

class CPG_Admin implements ArrayAccess
{
	public function offsetExists($k)  { return isset($_SESSION['DF_VISITOR']->admin[$k]); }
	public function offsetGet($k)     { return $_SESSION['DF_VISITOR']->admin[$k]; }
	public function offsetSet($k, $v) {}
	public function offsetUnset($k)   {}
}

class CPG_User implements ArrayAccess
{
	public function offsetExists($k)  { return isset($_SESSION['DF_VISITOR']->identity[$k]); }
	public function offsetGet($k)     { return $_SESSION['DF_VISITOR']->identity[$k]; }
	public function offsetSet($k, $v) {}
	public function offsetUnset($k)   {}
}
