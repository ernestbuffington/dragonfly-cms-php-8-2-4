<?php

namespace Dragonfly\PackageManager;

class Repositories extends \Poodle\PackageManager\Repositories
{

	public static function checkForUpdates()
	{
		$packages = parent::checkForUpdates();
		if ($packages) {
			$SQL = \Dragonfly::getKernel()->SQL;
			$qr = $SQL->query("SELECT title, version FROM {$SQL->TBL->modules}");
			$modules = array();
			while ($r = $qr->fetch_row()) {
				$modules[$r[0]] = $r[1];
			}
			foreach ($packages as $i => $package) {
				if ('module' === $package->type
				 && isset($modules[$package->name])
				 && !version_compare($package->version, $modules[$package->name], '>')) {
					unset($packages[$i]);
				}
			}
		}
		return $packages;
	}

}
