<?php

namespace Poodle\PackageManager;

class Repositories
{

	public static function checkForUpdates()
	{
		$SQL = \Poodle::getKernel()->SQL;
		$qr = $SQL->query("SELECT
			repo_id,
			package_type,
			package_name,
			package_version
		FROM {$SQL->TBL->packagemanager_installed}");
		$installed = array();
		$packages = array();
		while ($r = $qr->fetch_row()) {
			if (!isset($installed[$r[0]])) {
				$installed[$r[0]] = array();
			}
			$installed[$r[0]]["{$r[1]}-{$r[2]}"] = $r[3];
		}
		if ($installed) {
			$qr = $SQL->query("SELECT
				repo_id,
				repo_name,
				repo_location,
				repo_public_key
			FROM {$SQL->TBL->packagemanager_repos}
			WHERE repo_id IN (".implode(',',array_keys($installed)).") AND repo_enabled = 1");
			while ($r = $qr->fetch_row()) {
				$repo = new Repository();
				$repo->id = $r[0];
				$repo->name = $r[1];
				$repo->location = $r[2];
				$repo->public_key = $r[3];
				foreach ($repo->packages as $package) {
					$id = "{$package->type}-{$package->name}";
					if (isset($installed[$r[0]][$id])) {
						if (version_compare($package->version, $installed[$r[0]][$id], '>')) {
							$packages[] = $package;
						}
					}
				}
			}
		}
		return $packages;
	}

	public static function add($name, $url, $public_key = null, $enabled = false)
	{
		if (file_get_contents($url . 'packages.xml')) {
			if (!$public_key) {
				$public_key = file_get_contents($url . 'packages.pubkey') ?: '';
				if ($public_key) {
					$enabled = false;
					$public_key = trim(str_replace(array(Repository::PKB, Repository::PKE), '', $public_key));
				}
			}
			\Poodle::getKernel()->SQL->TBL->packagemanager_repos->insert(array(
				'repo_name'       => $name,
				'repo_enabled'    => $enabled,
				'repo_location'   => $url,
				'repo_public_key' => $public_key
			));
		}
	}

}
