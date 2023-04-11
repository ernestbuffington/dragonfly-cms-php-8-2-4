<?php

namespace Poodle\PackageManager;

class Remove
{
	use \Poodle\Events;

	protected
		$FS = null;

	function __construct($ftp_user = null, $ftp_pass = null, $ftp_path = null)
	{
		$ftp_host = '127.0.0.1';
		if ($ftp_user) {
			if (extension_loaded('ftp')) {
				$this->FS = new \Poodle\FTP\Adapter\Extension();
			} else {
				$this->FS = new \Poodle\FTP\Adapter\Socket();
			}
			$this->FS->connect($ftp_host, $ftp_user, $ftp_pass);
			$this->FS->chdir($ftp_path);
		} else {
			$this->FS = new \Poodle\FTP\Adapter\Local();
			$this->FS->connect($ftp_host, '', '');
		}
	}

	public function package($package_name)
	{
		$this->files = array();

		$TBL = \Poodle::getKernel()->SQL->TBL->packagemanager_installed;
		$where = array('package_name' => $package_name);
		$data = $TBL->uFetchRow(array('package_data'), $where);
		if (is_array($data)) {
			$data = json_decode($data[0], true);
			$this->files = $data['files'];
		}
		try {
			if ($this->files) {
				krsort($this->files);
				$count = count($this->files);
				$this->dispatchEvent(new RemoveProgressEvent("{$package_name}-extract", "Removing package '{$package_name}'", null, $count));
				$i = 0;
				foreach ($this->files as $file) {
					try {
						if ($this->FS->isDir($file)) {
							try {
								$this->FS->rmdir($file);
							} catch (\Exception $e) {
								// Ignore when directory is not empty
							}
						} else if ($this->FS->exists($file)) {
							$this->FS->delete($file);
						}
					} catch (\Exception $e) {
						throw new \Exception($e->getMessage() . ' for: ' . $file);
					}
					$this->dispatchEvent(new RemoveProgressEvent("{$package_name}-remove", $file, ++$i));
				}
				$this->dispatchEvent(new RemoveProgressEvent("{$package_name}-remove", "Complete", $count));
			}

			$TBL->delete($where);

		} catch (\Exception $e) {
			$this->dispatchEvent(new RemoveErrorEvent($e->getMessage()));
			return false;
		}

		return true;
	}

}

class RemoveErrorEvent extends \Poodle\Events\Event
{
	public
		$type = 'error',
		$message;

	function __construct($message)
	{
		$this->message = $message;
	}
}

class RemoveProgressEvent extends \Poodle\Events\Event
{
	public
		$type = 'progress',
		$task,
		$message,
		$value,
		$max;

	function __construct($task, $message, $value = null, $max = null)
	{
		$this->task = $task;
		$this->message = $message;
		$this->value = $value;
		$this->max = $max;
	}
}
