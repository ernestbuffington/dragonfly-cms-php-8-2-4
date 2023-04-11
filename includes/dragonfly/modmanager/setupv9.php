<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by DragonflyCMS Dev. Team.
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\ModManager;

class SetupV9 extends Setup
{

	public function __construct($name, $bypass=false)
	{
		if (!preg_match('#^[a-z0-9_\-]+$#i', $name)) {
			throw new \Exception('Module name characters error.');
		}
		$this->path = MODULE_PATH.$name.'/sql/';
		if (!is_file($this->path .'cpg_inst.php')) {
			$this->path = MODULE_PATH.$name.'/';
			if (!is_file($this->path .'cpg_inst.php')) {
				throw new \Exception('v9 Module installer is missing.');
			}
		}
		$class = static::getModuleClass($name);
		if (!$class) {
			throw new \Exception('Mismatch between module name and installer class name.');
		}
		$this->name = $name;
		$this->module = $class;
		SetupBase::__construct();
		$this->bypass = $bypass;
	}

	public function add_module()
	{
		if ($this->module) {
			try {
				$db = \Dragonfly::getKernel()->SQL;
				global $installer;
				$installer = $this;
				$db->begin();
				if (!$this->module->install()) {
					throw new \Exception('Install Error');
				}
				$this->sync_radmin();
				$this->exec();
				$this->post_install();
				$db->commit();
				return true;
			} catch (\Poodle\SQL\Exception $e) {
				$this->rollback();
				throw $e;
			}
		}
		return false;
	}

	public function update_module($prev_version)
	{
		if ($this->module && version_compare($this->module->version, $prev_version, '>')) {
			try {
				$db = \Dragonfly::getKernel()->SQL;
				global $installer;
				$installer = $this;
				$db->begin();
				$this->module->upgrade($prev_version);
				$this->sync_radmin();
				$this->exec();
				$db->query("UPDATE {$db->TBL->modules} SET version='{$this->module->version}' WHERE title='{$this->name}'");
				$db->commit();
				return true;
			} catch (\Poodle\SQL\Exception $e) {
				$this->rollback();
				throw $e;
			}
		}
		return false;
	}

	public function remove_module()
	{
		if (!$this->module) {
			return false;
		}
		$db = \Dragonfly::getKernel()->SQL;
		try {
			$db->begin();
			global $installer;
			$installer = $this;
			$this->module->uninstall();
			$this->sync_radmin(true);
			$this->exec();
			$db->commit();
			$this->post_uninstall();
			$db->optimize($db->TBL->modules);
			$db->optimize($db->TBL->config_custom);
			$db->optimize($db->TBL->blocks_custom);
		} catch (\Poodle\SQL\Exception $e) {
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

		return true;
	}

}
