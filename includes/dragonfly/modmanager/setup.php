<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by DragonflyCMS Dev. Team.
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\ModManager;

// Replaces Installer_Module
class Setup extends SetupBase
{

	protected
		$module,
		$id,
		$name,
		$path;

	public function __construct($name, $bypass=false)
	{
		if (!preg_match('#^[a-z0-9_\-]+$#i', $name)) {
			throw new \Exception('Module name characters error.');
		}
		$this->path = \Dragonfly::getModulePath($name).'install/';
		if (is_file($this->path .'cpg_inst.php')) {
			$class = static::getModuleClass($name);
			if (!$class) {
				throw new \Exception('Mismatch between module name and installer class name.');
			}
		} else {
			throw new \Exception('v10 Module installer is missing.');
		}
		$this->name = $name;
		$this->module = $class;
		if (!is_subclass_of($this->module, get_parent_class($this))) {
			throw new \Exception('Installer class not a child of '.get_parent_class($this));
		}
		parent::__construct();
		$this->bypass = ($bypass || !empty($this->module->bypass));
		$this->test   = ($this->test || !empty($this->module->test));
	}

	public function add_module()
	{
		if (!$this->module) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		try {
			$db->begin();
			$this->module->pre_install() && $this->module->exec();
			$db->commit();

			if (!$this->repair()) return false;

			//$db->begin();
			$this->post_install() || $this->sync_config() || $this->sync_radmin() || $this->sync_blocks() || $this->sync_userconfig();
			$this->exec() && $this->module->post_install() && $this->module->exec();
			//$db->commit();
		} catch (\Poodle\SQL\Exception $e) {
			$this->rollback();
			$this->module->rollback();
			$this->post_uninstall();
			throw $e;
		}

		// Extract static data from Phar
		if (0 === strpos($this->path, 'phar://')) {
			$phar = substr($this->path, 0, strrpos($this->path, '.phar')+5);
			$path_l = strlen($phar)+1;
			$phar = new \Poodle\PackageManager\PackageData($phar);
			$iterator = $phar->getRecursiveIteratorIterator();
			foreach ($iterator as $name => $file) {
				if ($file->isFile()) {
					if (0 !== strpos($name, "modules/{$this->name}/") || strpos($name, ".png")) {
						$file->extractTo($name);
					}
				}
			}
		}

		return true;
	}

	public function update_module($prev_version)
	{
		if (!$this->module) {
			return false;
		}
		$downgrade = version_compare($prev_version, $this->module->version, '>');
		if ($downgrade && !(method_exists($this->module, 'pre_downgrade') && method_exists($this->module, 'post_downgrade'))) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		try {
			$db->begin();
			($downgrade ? $this->module->pre_downgrade($prev_version) : $this->module->pre_upgrade($prev_version))
				&& $this->module->exec();
			$db->commit();

			if (!$this->repair()) return false;

			$db->begin();
			$this->sync_config() || $this->sync_radmin() || $this->sync_userconfig();
			$this->exec()
				&& ($downgrade ? $this->module->post_downgrade($prev_version) : $this->module->post_upgrade($prev_version))
				&& $this->module->exec();
			$this->setVersion($this->module->version);
			$db->commit();
		} catch (\Poodle\SQL\Exception $e) {
			$this->rollback();
			$this->module->rollback();
			$this->setVersion($prev_version);
			throw $e;
		}
		return true;
	}

	public function remove_module()
	{
		if (!$this->module) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		try {
			$db->begin();
			$this->module->pre_uninstall() && $this->module->exec();
			foreach ($this->module->dbtables as $table) {
				$this->add_query('DROP', $table);
			}
			if ($this->module->config) {
				$this->add_query('DELETE', 'config_custom', "cfg_name='".strtolower($this->name)."'");
			}
			$this->sync_radmin(true);
			$this->exec();
			$db->commit();
			$this->post_uninstall() && $this->module->post_uninstall() && $this->module->exec();
			$db->optimize($db->TBL->modules);
			if ($this->module->config) { $db->optimize($db->TBL->config_custom); }
			if ($this->module->blocks) { $db->optimize($db->TBL->blocks_custom); }
		} catch (\Poodle\SQL\Exception $e) {
			$this->rollback();
			$this->module->rollback();
			$this->rollback();
			throw $e;
		}
		return true;
	}

	public function repair()
	{
		if (!$this->module) {
			return false;
		}
		if ($xml = $this->module->getXMLSchema()) {
			if (!$this->sync_from_string($xml)) {
				return false;
			}
		} else if (is_file($this->path .'schema.xml') && !$this->sync_from_file('schema')) {
			return false;
		}

		if ($xml = $this->module->getXMLData()) {
			if (!$this->sync_from_string($xml)) {
				return false;
			}
		} else if (is_file($this->path .'data.xml') && !$this->sync_from_file('data')) {
			return false;
		}

		return true;
	}

	protected function setVersion($version)
	{
		\Dragonfly::getKernel()->SQL->TBL->modules->update(array('version'=>$version), array('title'=>$this->name));
	}

	protected function sync_from_string($xml)
	{
		$db = \Dragonfly::getKernel()->SQL;
		if ($db->XML->syncSchemaFromString($xml)) {
			return true;
		}
		throw new \Exception(print_r($db->XML->errors, true));
	}

	protected function sync_from_file($file)
	{
		$db = \Dragonfly::getKernel()->SQL;
		if ($db->XML->syncSchemaFromFile($this->path .$file .'.xml')) {
			return true;
		}
		throw new \Exception(print_r($db->XML->errors, true));
	}

	public function sync_config()
	{
		if (!$this->module) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		if (!empty($this->module->config) && is_array($this->module->config)) {
			$sql = array();
			foreach ($this->module->config as $field => $value) {
				if (preg_match('#^[a-z0-9_\-]+$#i', $field)) {
					$sql[] = "('{$this->name}', '{$field}', ".$db->quote($value).")";
				}
			}
			$this->add_query(
				'INSERT_MULTIPLE',
				'config_custom',
				array (
					'cfg_name, cfg_field, cfg_value',
					implode(',', $sql)
				),
				"cfg_name='{$this->name}'"
			);
		}
	}

	public function sync_radmin($drop=false)
	{
		if (!$this->module) {
			return false;
		}
		$radmin = 'radmin' . strtolower($this->name);
		$cols = array_keys(\Dragonfly::getKernel()->SQL->TBL->admins->listColumns(false));
		if (in_array($radmin, $cols)) {
			if ($drop || empty($this->module->radmin)) {
				$this->add_query('DEL', 'admins', $radmin, array($radmin, 'INT1', false, 0));
			}
		} else if (!$drop && !empty($this->module->radmin)) {
			$this->add_query('ADD', 'admins', array($radmin, 'INT1', false, 0));
		}
	}

	public function sync_blocks()
	{
		if (!$this->module) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		if (!empty($this->module->blocks)) {
			$result = $db->query("SELECT bid, bposition FROM {$db->TBL->blocks} WHERE active=1");
			if ($result->num_rows) {
				$in_modules = array();
				$bpos = array('l' => 1, 'c' => 1, 'r' => 1, 'd' => 1);
				while ($row = $result->fetch_row()) {
					$in_modules[] = "({$row[0]}, {$this->id}, '{$row[1]}', ".($bpos[$row[1]]++).")";
				}
				$this->add_query(
					'INSERT_MULTIPLE',
					'blocks_custom',
					array (
						'bid, mid, side, weight',
						implode(',', $in_modules)
					),
					'mid='.$this->id
				);
			}
			$result->free();
		}
	}

	public function sync_userconfig()
	{
		if (!$this->module) {
			return false;
		}
		if (!empty($this->module->userconfig) && is_array($this->module->userconfig)) {}
	}

	public function __get($key)
	{
		$allowed = array('radmin', 'version', 'name', 'description', 'author', 'website');
		if (in_array($key, $allowed)) { return $this->$key; }
	}

	public static function getModuleClass($name)
	{
		$name = static::getModuleClassName($name);
		return $name ? new $name : false;
	}

	public static function getModuleClassName($name)
	{
		$path = \Dragonfly::getModulePath($name);
		$file = $path.'install/cpg_inst.php';
		if (!is_file($file)) {
			$file = $path.'sql/cpg_inst.php';
			if (!is_file($file)) {
				$file = $path.'cpg_inst.php';
			}
		}
		if (is_file($file)) {
			require_once($file);
			if (class_exists("Dragonfly\\ModManager\\{$name}", false)) {
				return "Dragonfly\\ModManager\\{$name}";
			}
			if (class_exists("{$name}_Setup", false)) {
				return "{$name}_Setup";
			}
			if (class_exists($name, false)) {
				return $name;
			}
		}
		return false;
	}

	public function pre_install(){return false;}

	public function post_install()
	{
		$db = \Dragonfly::getKernel()->SQL;
		$id = $db->uFetchRow("SELECT mid FROM {$db->TBL->modules} WHERE title = {$db->quote($this->name)}");
		if ($id) {
			$this->id = (int)$id[0];
			$db->TBL->modules->update(array('uninstall'=>1, 'version'=>$this->module->version), "mid={$this->id}");
		} else {
			$this->id = $db->TBL->modules->insert(array('title'=>$this->name, 'uninstall'=>1, 'version'=>$this->module->version), 'mid');
		}
		static::clearCache();
		return true;
	}

	public function pre_upgrade($prev_version){return false;}

	public function post_upgrade($prev_version){return false;}

	public function pre_uninstall(){return false;}

	public function post_uninstall()
	{
		$db = \Dragonfly::getKernel()->SQL;
		if ($id = $db->uFetchRow("SELECT mid FROM {$db->TBL->modules} WHERE title={$db->quote($this->name)}")) {
			$this->id = $id[0];
			$db->TBL->blocks_custom->delete("mid={$this->id}");
			$db->TBL->modules->delete("mid={$this->id}");
		} else {
			$db->TBL->modules->delete(array('title'=>$this->name));
		}
		static::clearCache();
		return true;
	}

}
