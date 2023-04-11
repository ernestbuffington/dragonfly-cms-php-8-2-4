<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Session\Handler;

class Builtin extends \Dragonfly\Session
{
	private static
		$module,
		$save_path;

	function __construct($config)
	{
		if (!self::$module) {
			self::$module = session_module_name();
			self::$save_path = session_save_path();
		}
		parent::__construct($config);
	}

	# http://bugs.php.net/bug.php?id=32330
	protected function setHandler()
	{
		session_module_name(self::$module);
		if (!\Poodle\PHP\INI::set('session.save_path', self::$save_path)) {
			session_save_path(self::$save_path);
		}
	}

	protected function before_write_close() { return $this->db_write(session_id()); }

	// These are not used due to internal handler
	public function open($save_path, $name) { return true; }
	public function close() { return true; }
	public function read($id) { return ''; }
	public function write($id, $value) { return true; }
	public function destroy($id) { return true; }
	public function gc($maxlifetime) { return true; }
}
