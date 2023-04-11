<?php

namespace Poodle\PackageManager;

class Install
{
	use \Poodle\Events;

	protected
		$FS = null,
		$mask,
		$tmp_dir;

	function __construct($ftp_user = null, $ftp_pass = null, $ftp_path = null)
	{
		if ($ftp_user) {
//			$this->FS = new \Poodle\FTP();
			if (extension_loaded('ftp')) {
				$this->FS = new \Poodle\FTP\Adapter\Extension();
			} else {
				$this->FS = new \Poodle\FTP\Adapter\Socket();
			}
			$this->FS->connect('127.0.0.1', $ftp_user, $ftp_pass);
			$this->FS->chdir($ftp_path);
			$this->mask = 0022;
		} else {
//			$this->FS = new \Poodle\FTP('local');
			$this->FS = new \Poodle\FTP\Adapter\Local();
			$this->FS->connect('', '', '');
			$this->mask = static::getMask();
		}
	}

	public static function getPossibleWriteMethods($path = false)
	{
		if (!$path) {
			$path = getcwd();
		}
		$path = rtrim($path, '/\\') . '/';

		$methods = array(
			'direct' => array(
				'allowed' => false,
				'umask' => static::getMask($path),
			),
			'ftp' => array(
				'allowed' => extension_loaded('ftp') || extension_loaded('sockets') || function_exists('fsockopen'),
			),
		);
		$methods['direct']['allowed'] = (false !== $methods['direct']['umask']);
		return $methods;
	}

	public static function getMask($path = false)
	{
		if (!$path) {
			$path = getcwd();
		}
		$mask = false;
		if (is_writable($path)) {
			$temp_file_name = $path . 'temp-write-test-' . time();
			if (touch($temp_file_name)) {
				// Determine the owner of the directory, and that of the temporary file
				$path_owner = fileowner($path);
				$temp_file_owner = fileowner($temp_file_name);
				if (false !== $path_owner && $path_owner === $temp_file_owner) {
					// PHP creates files as the same owner
					$mask = 0022;
				} else {
					$mask = 0000;
				}
				unlink($temp_file_name);
			}
		}
		return $mask;
	}

	public function repositoryPackage(Repository $repository, $package_name)
	{
		if ($package_name instanceof Package) {
			$package = $package_name;
			$package_name = $package->name;
		} else {
			$package = $repository->getPackage($package_name);
		}
		$location = $repository->location . $package->location;
		$this->files = array();

		try {
			$package->addEventListener('download', function (\Poodle\Events\Event $event) use ($package_name) {
				if ($event->complete) {
					$this->dispatchEvent(new InstallProgressEvent("{$package_name}-fetch", "Downloading package '{$package_name}' complete", 1));
				} else {
					$this->dispatchEvent(new InstallProgressEvent("{$package_name}-fetch", "Downloading package '{$package_name}'"));
				}
			});
			$phar = $package->getPackageData($this->tmp_dir);

			$count = $phar->count(); // Only returns files, not directories
			$this->dispatchEvent(new InstallProgressEvent("{$package_name}-extract", "Extracting package '{$package_name}'", null, $count));

			$manifest = null;
			if ($metadata = $phar->getMetadata()) {
				$manifest = $metadata['package']; // package xml data
			}

			$i = 0;
			$iterator = $phar->getRecursiveIteratorIterator();
			foreach ($iterator as $key => $file) {
				if ('core' === $package->type || strpos($key, '/')) {
					$file->extractTo($this->FS, $this->mask);
					$this->files[] = $key;
				}
				if ($file->isFile()) {
					++$i;
				}
				$this->dispatchEvent(new InstallProgressEvent("{$package_name}-extract", $key, $i, $count));
			}
			$this->dispatchEvent(new InstallProgressEvent("{$package_name}-extract", "Complete", $count, $count));

			$TBL = \Poodle::getKernel()->SQL->TBL->packagemanager_installed;
			$manifest = trim(preg_replace('/<\\?.*?\\?>/', '', $manifest));
			$where = array('package_type' => $package->type, 'package_name' => $package_name);
			$pd = $TBL->uFetchRow(array('package_data'), $where);
			if (is_array($pd)) {
				$pd = json_decode($pd[0], true);
				$TBL->update(array(
					'package_version' => $package->version,
					'repo_id' => $repository->id,
					'package_data' => array(
						'files' => array_unique(array_merge($this->files, $pd['files'])),
						'manifest' => $manifest
					)
				), $where);
			} else {
				$TBL->insert(array(
					'package_type' => $package->type,
					'package_name' => $package_name,
					'package_version' => $package->version,
					'repo_id' => $repository->id,
					'package_data' => array(
						'files' => $this->files,
						'manifest' => $manifest
					)
				));
			}
		} catch (\Exception $e) {
			$this->dispatchEvent(new InstallErrorEvent($e->getMessage()));
			return false;
		}

		return $package;
	}

}

class InstallErrorEvent extends \Poodle\Events\Event
{
	public
		$message;

	function __construct($message)
	{
		parent::__construct('error');
		$this->message = $message;
	}
}

class InstallProgressEvent extends \Poodle\Events\Event
{
	public
		$task,
		$message,
		$value,
		$max;

	function __construct($task, $message, $value = null, $max = null)
	{
		parent::__construct('progress');
		$this->task = $task;
		$this->message = $message;
		$this->value = $value;
		$this->max = $max;
	}
}
