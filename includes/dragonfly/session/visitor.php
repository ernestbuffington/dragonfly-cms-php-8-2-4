<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Session;

class Visitor
{
	protected
		$admin,
		$identity,
		$prev_identity_id;

	function __construct()
	{
		if (!$this->admin) {
			$AC = \Dragonfly::getKernel()->CFG->admin_cookie;
			if (!empty($AC->allow)) {
				$provider = new \Dragonfly\Admin\Cookie();
				$result = $provider->authenticate($_COOKIE);
				if ($result instanceof \Poodle\Auth\Result\Success) {
					$this->admin = $result->user;
//					\Dragonfly\Admin\Cookie::set($aid);
				}
			}
		}

		if (!$this->identity)
		{
			# No Identity, try cookie
			$AC = \Dragonfly::getKernel()->CFG->auth_cookie;
			if (!empty($AC->allow)) {
				$this->identity = $ID = \Dragonfly\Identity::factory();
				$result = $ID->authenticate($_COOKIE, array(
					'class' => isset($AC->class) ? $AC->class : 'Dragonfly\\Identity\\Cookie'
				));
				if ($result instanceof \Poodle\Auth\Result\Success && $result->user->id) {
					$ID->updateLastVisit();
				}
			}
		}
	}

	function __get($key)
	{
		if ('admin' === $key && !$this->admin) {
			$this->admin = new \Dragonfly\Admin\Identity();
		}

		if ('identity' === $key && !$this->identity) {
			$this->identity = \Dragonfly\Identity::factory();
		}

		if (property_exists($this,$key)) { return $this->$key; }
	}

	function __set($k,$v)
	{
		throw new Exception('Not allowed to set property');
	}

	function __isset($k)
	{
		if ('admin' === $k) { return ($this->admin && $this->admin->id); }
		if (property_exists($this, $k)) { return isset($this->$k); }
	}

	function __unset($k)
	{
		if ('admin'    === $k) { unset($this->admin); }
		if ('identity' === $k) { unset($this->identity); }
	}

	public function switchIdentity($id)
	{
		if ($this->admin->canAdmin()) {
			$ID = \Poodle\Identity\Search::byID($id);
			if ($ID) {
				$this->prev_identity_id = $this->identity->id;
				$this->identity = $ID;
				return true;
			}
		}
		return false;
	}
}
