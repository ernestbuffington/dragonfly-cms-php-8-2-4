<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\SQL;

abstract class Record
{
	protected
		$sql_table = '',
		$sql_id_field = '',
		$sql_field_map = array(
		// 'field_name' => 'prop_name'
		);

	protected function sqlInitRecord($id)
	{
		if ($id) {
			$fields = array();
			foreach ($this->sql_field_map as $f => $n) { $fields[] = $n ? "{$f} {$n}" : "{$f}"; }

			$r = \Poodle::getKernel()->SQL->TBL[$this->sql_table]->uFetchAssoc(
				$fields,
				array($this->sql_id_field => $id)
			);
			if (!$r) { return false; }
			foreach ($r as $k => $v) {
				if (property_exists($this, $k)) {
					switch (gettype($this->$k)) {
						case 'boolean': $v = !!$v; break;
						case 'integer': $v = (int)$v; break;
						case 'double':  $v = (float)$v; break;
						case 'array':   $v = explode(',',$v); break;
//						case 'object':  $v = new $v(); break;
					}
					$this->$k = $v;
				}
			}
		}
		return true;
	}

	protected function sqlSaveRecord()
	{
		$fid = $this->sql_id_field;
		$oid = $this->sql_field_map[$fid];
		$fields = array();
		foreach ($this->sql_field_map as $f => $n) {
			$fields[$f] = is_array($this->$n) ? implode(',',$this->$n) : $this->$n;
		}
		unset($fields[$fid]);

		$tbl = \Poodle::getKernel()->SQL->TBL[$this->sql_table];
		if ($this->$oid) {
			$tbl->update($fields, array($fid => $this->$oid));
		} else {
			$this->$oid = $tbl->insert($fields, $fid);
		}
		return $this->$oid;
	}

}
