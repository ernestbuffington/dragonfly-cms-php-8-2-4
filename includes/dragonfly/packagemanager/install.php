<?php

namespace Dragonfly\PackageManager;

use Poodle\PackageManager\Repository;

class Install extends \Poodle\PackageManager\Install
{

	function __construct($ftp_user = null, $ftp_pass = null, $ftp_path = null)
	{
		parent::__construct($ftp_user, $ftp_pass, $ftp_path);
		$this->tmp_dir = getcwd() . '/cache';
	}

	public static function getInstalledPackages()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$qr = $SQL->query("SELECT
			package_type,
			package_name,
			package_version
		FROM {$SQL->TBL->packagemanager_installed}");
		$packages = array('core-dragonflycms' => 0);
		while ($r = $qr->fetch_row()) {
			$packages["{$r[0]}-{$r[1]}"] = $r[2];
		}
		$packages['core-dragonflycms'] = \Dragonfly::VERSION;
		return $packages;
	}

	public function repositoryPackage(Repository $repository, $package_name)
	{
		if ($package = parent::repositoryPackage($repository, $package_name)) {
			try {
				if ('core' === $package->type && 'dragonflycms' === $package_name) {
					$upgrade = new \Dragonfly\Setup\Upgrade();
					$upgrade->setEventsListener($this);
					$upgrade->run();
				} else if ('module' === $package->type) {
					$this->runModuleSetup($package_name);
				}
			} catch (\Exception $e) {
				$this->dispatchEvent(new \Poodle\PackageManager\InstallErrorEvent($e->getMessage()));
				return false;
			}
			return $package;
		}
		return false;
	}

	protected function runModuleSetup($modname)
	{
		if (!is_file("modules/{$modname}/install/cpg_inst.php")) {
			return true;
		}
		define('ADMIN_MOD_INSTALL', 1);
		try {
			$db = \Dragonfly::getKernel()->SQL;
			$setup = new \Dragonfly\ModManager\Setup($modname);
			$version = $db->uFetchRow("SELECT version FROM {$db->TBL->modules} WHERE title = {$db->quote($modname)}");
			if ($version) {
				if (!$setup->update_module($version[0])) {
					//cpg_error(_UPGRADEFAILED .': ' .$setup->error, _UPGRADEFAILED);
					return false;
				}
			} else if ($setup->error || !$setup->add_module()) {
				//cpg_error($setup->error, 'Module install failed');
				return false;
			}
			\Dragonfly\ModManager\SetupBase::clearCache();
			//cpg_error('The module "'.$modname.'" has been properly installed, have a blast using it!', 'Module install succeeded', DF_MODE_DEVELOPER ? false : URL::admin('modules'));
			//cpg_error(_TASK_COMPLETED, 'Module update suceeded', DF_MODE_DEVELOPER ? false : URL::admin('modules'));
		} catch (\Exception $e) {
			cpg_error(_UPGRADEFAILED .': ' .$e->getMessage(), _UPGRADEFAILED);
		}
		return true;
	}

}
