<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Admin;

class Identity implements \ArrayAccess
{
	protected
		$id,
		$name,
		$password,
		$email,
		$totp_2fa,
		$radmin;

	function __construct($id=0)
	{
		if (!$this->id) { $this->init($id); }
	}

	protected function init($id)
	{
		$this->id       = 0;
		$this->name     = null;
		$this->password = null;
		$this->email    = null;
		$this->totp_2fa = null;
		$this->radmin   = array();

		$id  = intval($id);
		$SQL = \Dragonfly::getKernel()->SQL;
		$qr  = $SQL->query("SELECT * FROM {$SQL->TBL->admins} WHERE admin_id={$id}");
		$admin = $qr->fetch_assoc();
		if (!$admin) {
			foreach ($qr->fetch_fields() as $field) {
				if (0 === strpos($field->name, 'radmin')) {
					$this->radmin[strtolower(substr($field->name,6))] = false;
				}
			}
		} else {
			$this->setData($admin);
		}
		ksort($this->radmin);
	}

	function __get($key)
	{
		if ('radmin'===$key) {
			$aops = $this->radmin;
			unset($aops['super']);
			return $aops;
		}
		if ('aid'===$key) { $key = 'name'; }
		if ('admin_id'===$key) { $key = 'id'; }

		if (property_exists($this, $key)) { return $this->$key; }

		if ('totp_2fa_qr' === $key) {
			return $this->totp_2fa
				? \Poodle\TOTP::getQRCode("{$this->name}@{$_SERVER['HTTP_HOST']}", $this->totp_2fa)
				: null;
		}

		$k = str_replace('radmin','',$key);
		if (array_key_exists($k, $this->radmin)) { return $this->radmin[$k]; }

		trigger_error(__CLASS__.' undefined property '.$key);
	}

	function __set($key, $value)
	{
		switch ($key)
		{
		case 'id':
			throw new Exception('Disallowed to set id');

		case 'name':
			if ($this->id) return;
			break;

		case 'password':
			$value = \Poodle\Auth::hashPassword($value);
			break;

		case 'email':
			$value = mb_strtolower(trim($value));
			break;
		}

		if (property_exists($this, $key)) {
			$this->$key = $value;
			return;
		}

		$k = str_replace('radmin','',$key);
		if (array_key_exists($k, $this->radmin)) {
			$this->radmin[$k] = !empty($value);
			return;
		}

		trigger_error(__CLASS__.' undefined property '.$key);
	}

	public function authenticate($name, $password, $totp)
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$row = $SQL->uFetchAssoc("SELECT * FROM {$SQL->TBL->admins} WHERE LOWER(aid)=".$SQL->quote(mb_strtolower($name)));
		if ($row) {
			$aid = $row['admin_id'];
			if (!($login = \Dragonfly::getKernel()->CACHE->get('a_login')) || !isset($login[$aid])) {
				$login[$aid] = 1;
			} else if ($login[$aid] >= 5) {
				cpg_error('Too many failed login attempts');
			} else {
				++$login[$aid];
			}
			// Verify password
			if ($row['totp_2fa'] && !\Poodle\TOTP::verifyCode($row['totp_2fa'], $totp)) {
				\Poodle\LOG::error(\Poodle\LOG::LOGIN, "Admin TOTP incorrect for {$name}");
			} else if (\Poodle\Auth::verifyPassword($password, $row['pwd'])) {
				$this->setData($row);
				unset($login[$this->id]);
			} else {
				\Poodle\LOG::error(\Poodle\LOG::LOGIN, "Admin password incorrect for {$name}");
			}
			\Dragonfly::getKernel()->CACHE->set('a_login', $login);
		} else {
			\Poodle\LOG::error(\Poodle\LOG::LOGIN, "Admin credentials not found for {$name}");
		}
		return 0<$this->id;
	}

	public function getPermissions()
	{
		return array_keys(array_filter($this->radmin));
	}

	public function canAdmin($module='super')
	{
		return ($this->radmin['super'] || !empty($this->radmin[strtolower($module)]));
	}

	public function save()
	{
		$data = array(
			'email'    => $this->email,
			'totp_2fa' => (string) $this->totp_2fa,
		);

		if ($this->password) { $data['pwd'] = $this->password; }

		if (1 == $this->id) { $this->radmin['super'] = 1; }

		foreach ($this->radmin as $k => $v) {
			$data['radmin'.$k] = (!$this->radmin['super'] && $v);
		}
		$data['radminsuper'] = $this->radmin['super'];

		$tbl = \Dragonfly::getKernel()->SQL->TBL->admins;
		if (!$this->id) {
			$data['aid'] = $this->name;
			$this->id = $tbl->insert($data, 'admin_id');
		} else {
			$tbl->update($data, "admin_id={$this->id}");
		}
	}

	public function delete()
	{
		\Dragonfly::getKernel()->SQL->TBL->admins->delete("admin_id={$this->id}");
	}

	public function logout()
	{
		$this->init(0);
		Cookie::remove();
	}

	public function reload()
	{
		$this->init($this->id);
	}

	protected function setData($data)
	{
		$this->id       = $data['admin_id'];
		$this->name     = $data['aid'];
		$this->email    = $data['email'];
		$this->totp_2fa = $data['totp_2fa'];
		foreach ($data as $field => $val) {
			if (0 === strpos($field, 'radmin')) {
				$this->radmin[strtolower(substr($field,6))] = 0<$val;
			}
		}
	}

	public function isDemo()
	{
		return (CPGN_DEMO && false !== strpos($this->name, 'demo'));
	}

	# ArrayAccess
	public function offsetExists($k)  { return property_exists($this, $k) || array_key_exists(str_replace('radmin','',$k), $this->radmin); }
	public function offsetGet($k)     { return $this->__get($k); }
	public function offsetSet($k, $v) {}
	public function offsetUnset($k)   {}
}
