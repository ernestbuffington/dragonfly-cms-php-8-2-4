<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

class Upload
{
	protected
		$id          = 0,
		$identity_id = 0,
		$module_id   = 0,
		$time        = 0,
		$size        = 0,
		$file        = '',
		$name        = '';

	function __construct($id = 0)
	{
		$this->id = (int)$id;
		if ($this->id) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$row = $SQL->uFetchRow("SELECT
				identity_id,
				module_id,
				upload_time,
				upload_size,
				upload_file,
				upload_name
			FROM {$SQL->TBL->users_uploads}
			WHERE upload_id = {$this->id}");
			if (!$row) {
				throw new \Exception('Invalid upload_id');
			}
			$this->identity_id = $row[0];
			$this->module_id   = $row[1];
			$this->time        = (int)$row[2];
			$this->size        = (int)$row[3];
			$this->file        = $row[4];
			$this->name        = $row[5];
		} else {
			$this->time = time();
			$this->identity_id = \Dragonfly::getKernel()->IDENTITY->id;
			if (!empty($GLOBALS['Module'])) {
				$this->module_id = $GLOBALS['Module']->id;
			}
		}
	}

	function __get($k)
	{
		if (property_exists($this, $k)) {
			return $this->$k;
		}
		trigger_error(__CLASS__ . " undefined property {$k}");
	}

	function __set($k, $v)
	{
		if (property_exists($this, $k)) {
			if ('id' == $k) {
				return;
			}
			switch (gettype($this->$k))
			{
			case 'string':
				$this->$k = trim($v);
				break;
			case 'integer':
				$this->$k = (int)$v;
				break;
			}
		} else {
			trigger_error(__CLASS__ . " undefined property {$k}");
		}
	}

	public function save()
	{
		if (!$this->id) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$this->id = $SQL->TBL->users_uploads->insert(array(
				'identity_id' => $this->identity_id,
				'module_id'   => $this->module_id,
				'upload_time' => $this->time,
				'upload_size' => $this->size,
				'upload_file' => $this->file,
				'upload_name' => $this->name
			),'upload_id');
		}
	}

	public function create($file)
	{
		if ($file instanceof \Poodle\Input\File) {
			$this->file = $file->moveTo("uploads/{$GLOBALS['Module']->name_lc}/{$this->identity_id}/{$file->filename}");
			$this->size = $file->size;
			$this->name = $file->org_name;
		} else if (is_file($file)) {
			$this->file = $file;
			$this->size = filesize($file);
			$this->name = basename($file);
		}
	}

	public function delete()
	{
		if ($this->file) {
			if (is_file($this->file)) {
				error_log("unlink({$this->file})");
				unlink($this->file);
			}
			$this->file = null;
		}
		if ($this->id) {
			\Dragonfly::getKernel()->SQL->TBL->users_uploads->delete("upload_id = {$this->id}");
		}
	}

}
