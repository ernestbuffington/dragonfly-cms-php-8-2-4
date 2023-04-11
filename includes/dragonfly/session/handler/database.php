<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Session\Handler;

class Database extends \Dragonfly\Session
{

	protected function after_start($id) {}

	#
	# Handler functions
	#

	public function open($save_path, $name) { return true; }

	public function close() { return true; }

	public function read($id)
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$value = $SQL->uFetchRow("SELECT sess_expiry, sess_timeout, sess_value
			FROM {$SQL->TBL->sessions} WHERE sess_id={$SQL->quote($id)}");
		if ($value && $value[0] >= time()) {
			$this->setTimeout($value[1]);
			return $SQL->unescapeBinary($value[2]);
		}
		return '';
	}

	public function write($id, $value)
	{
		return $this->db_write($id, $value);
	}

	public function destroy($id)
	{
		\Dragonfly::getKernel()->SQL->TBL->sessions->delete(array('sess_id'=>$id));
		return true;
	}

	public function gc($maxlifetime)
	{
		return $this->db_gc();
	}
}
