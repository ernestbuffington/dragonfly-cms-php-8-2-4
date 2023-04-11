<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	Not implemented:
		ftp_alloc
		ftp_cdup
		ftp_exec
		ftp_mdtm
		ftp_nb_continue
		ftp_nb_fget
		ftp_nb_fput
		ftp_nb_get
		ftp_nb_put
		ftp_site
*/

namespace Poodle;

class FTP implements \Poodle\FTP\Interfaces\Adapter
{
	protected
		$connection;

	function __construct($adapter = null)
	{
		if (!$adapter) {
			if (extension_loaded('ftp')) {
				$adapter = 'extension';
			} else {
				$adapter = 'socket';
			}
		}
		$adapter = 'Poodle\\FTP\\Adapter\\'.$adapter;
		$this->connection = new $adapter();
	}

	function __destruct()
	{
		$this->disconnect();
	}

	public function connect($host, $username, $password)
	{
		return $this->connection->connect($host, $username, $password);
	}

	public function disconnect()
	{
		$this->connection->disconnect();
	}

	public function chdir($directory)
	{
		return $this->connection->chdir($directory);
	}

	public function chmod($filename, $mode)
	{
		return $this->connection->chmod($filename, $mode);
	}

	public function delete($path)
	{
		return $this->connection->delete($path);
	}

	public function fileSize($remote_file)
	{
		return $this->connection->fileSize($path);
	}

	public function fget($handle, $remote_file, $resumepos = 0)
	{
		return $this->connection->fget($handle, $remote_file, $resumepos);
	}

	public function fput($remote_file, $handle, $startpos = 0)
	{
		return $this->connection->fput($remote_file, $handle, $startpos);
	}

	public function get($local_file, $remote_file, $resumepos = 0)
	{
		return $this->connection->get($local_file, $remote_file, $resumepos);
	}

	public function put($remote_file, $local_file, $startpos = 0)
	{
		return $this->connection->put($remote_file, $local_file, $startpos);
	}

	public function mkdir($directory)
	{
		return $this->connection->mkdir($directory);
	}

	public function rename($oldname, $newname)
	{
		return $this->connection->rename($oldname, $newname);
	}

	public function rmdir($directory)
	{
		return $this->connection->rmdir($directory);
	}

	public function getSystemType()
	{
		return $this->connection->getSystemType();
	}

	public function getCWD()
	{
		return $this->connection->getCWD();
	}

	public function raw($command)
	{
		return $this->connection->raw($command);
	}

	public function rawlist($directory = null, $recursive = false)
	{
		return $this->connection->rawlist($directory, $recursive);
	}

	public function scanDir($directory = null)
	{
		return $this->connection->scanDir($directory);
	}

	public function setPassiveMode($pasv)
	{
		return $this->connection->setPassiveMode($pasv);
	}

	public function exists($name)
	{
		return $this->connection->exists($name);
	}

	public function isDir($directory)
	{
		return $this->connection->isDir($directory);
	}

	public function listDirectories($directory = null)
	{
		$files = $this->rawlist($directory);
		if ($files) {
			$dirs = array();
			foreach ($files as $file) {
				if ('d' === $file[0] || ('x' === $file[3] && 'l' === $file[0])) {
					$file = preg_split('/\\s+/', $file);
					if ('.' !== $file[8][0]) {
						$dirs[] = $file[8];
					}
				}
			}
			sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
			return $dirs;
		}
		return false;
	}

	public function uploadFile($remote_file, $handle)
	{
		return $this->chdirFile($remote_file) && $this->connection->fput(basename($remote_file), $handle);
	}

	public function uploadData($remote_file, $data)
	{
		$result = false;
		if ($this->chdirFile($remote_file)) {
			$fp = fopen('data:text/plain;base64,'.base64_encode($data), 'rb');
			if ($fp) {
				try {
					$result = $this->connection->fput(basename($remote_file), $fp);
				} finally {
					fclose($fp);
				}
			}
		}
		return $result;
	}

	protected function chdirFile($remote_file)
	{
		if (preg_match('#^(.*/)?([^/]+)$#D', $remote_file, $m) && $m[1]) {
			$this->connection->chdir($m[1]);
		}
		return true;
	}

}
