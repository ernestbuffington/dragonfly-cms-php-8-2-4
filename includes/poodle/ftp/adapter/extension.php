<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\FTP\Adapter;

class Extension implements \Poodle\FTP\Interfaces\Adapter
{
	protected
		$timeout = 15,
		$connection;

	function __destruct()
	{
		$this->disconnect();
	}

	public function connect($host, $username, $password)
	{
		if (!function_exists('ftp_connect')) { return false; }

		$this->disconnect();

		$tls = ('tls' === parse_url($host, PHP_URL_SCHEME));
		$port = parse_url($host, PHP_URL_PORT) ?: 21;
		$host = parse_url($host, PHP_URL_HOST) ?: $host;

		$conn_id = $tls ? ftp_ssl_connect($host, $port, $this->timeout) : ftp_connect($host, $port, $this->timeout);
		if (!$conn_id) {
			throw new \Exception("FTP connect failed");
		}

		if (!ftp_login($conn_id, $username, $password)) {
			ftp_close($conn_id);
			throw new \Exception("FTP login failed");
		}

		// Get features
		//$this->raw('FEAT');

		// Set binary mode
		//$this->raw('TYPE I');

		$this->connection = $conn_id;
		return true;
	}

	public function disconnect()
	{
		if ($this->connection) {
			ftp_close($this->connection);
			$this->connection = null;
		}
	}

	public function chdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_chdir($this->connection, $directory)) {
			throw new \Exception('FTP chdir failed');
		}
		return true;
	}

	public function chmod($path, $mode)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_chmod($this->connection, $mode, $path)) {
			throw new \Exception('FTP chmod failed');
		}
		return true;
	}

	public function delete($path)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_delete($this->connection, $path)) {
			throw new \Exception('FTP delete failed');
		}
		return true;
	}

	// NOTE: Not all servers support this feature.
	public function fileSize($remote_file)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_size($this->connection, $remote_file);
		if (!$result) {
			throw new \Exception('FTP size failed');
		}
		return $result;
	}

	public function fget($handle, $remote_file, $resumepos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_fget($this->connection, $handle, $remote_file, FTP_BINARY, $resumepos)) {
			throw new \Exception('FTP fget failed');
		}
		return true;
	}

	public function fput($remote_file, $handle, $startpos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_fput($this->connection, $remote_file, $handle, FTP_BINARY, $startpos)) {
			throw new \Exception('FTP fput failed');
		}
		return true;
	}

	public function get($local_file, $remote_file, $resumepos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_get($this->connection, $local_file, $remote_file, FTP_BINARY, $resumepos)) {
			throw new \Exception('FTP get failed');
		}
		return true;
	}

	public function put($remote_file, $local_file, $startpos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_put($this->connection, $remote_file, $local_file, FTP_BINARY, $startpos)) {
			throw new \Exception('FTP put failed');
		}
		return true;
	}

	public function mkdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_mkdir($this->connection, $directory)) {
			throw new \Exception('FTP mkdir failed');
		}
		return true;
	}

	public function rename($oldname, $newname)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_rename($this->connection, $oldname, $newname)) {
			throw new \Exception('FTP rename failed');
		}
		return true;
	}

	public function rmdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_rmdir($this->connection, $directory)) {
			throw new \Exception('FTP rmdir failed');
		}
		return true;
	}

	public function getSystemType()
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_systype($this->connection);
		if (!$result) {
			throw new \Exception('FTP systype failed');
		}
		return $result;
	}

	public function getCWD()
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_pwd($this->connection);
		if (!$result) {
			throw new \Exception('FTP pwd failed');
		}
		return $result;
	}

	public function raw($command)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_raw($this->connection, $command);
		if (!$result) {
			throw new \Exception('FTP raw failed');
		}
		return $result;
	}

	public function rawlist($directory = null, $recursive = false)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_rawlist($this->connection, $directory?:$this->getCWD(), $recursive);
		if (!$result) {
			throw new \Exception('FTP rawlist failed');
		}
		return $result;
	}

	public function scanDir($directory = null)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = ftp_nlist($this->connection, $directory?:$this->getCWD());
		if (!$result) {
			throw new \Exception('nlist failed');
		}
		return $result;
	}

	public function setPassiveMode($pasv)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!ftp_pasv($this->connection, !!$pasv)) {
			throw new \Exception('FTP pasv failed');
		}
		return true;
	}

	public function exists($name)
	{
		$directory = preg_match('#^(.*/)[^/]+$#D', $name, $m) ? $m[1] : '.';
		return in_array($name, $this->scanDir($directory));
	}

	public function isDir($directory)
	{
		if (!preg_match('#^(.*/)?([^/]+)$#D', $directory, $m)) {
			throw new \Exception('Invalid directory');
		}
		$dir = $m[1];
		$name = $m[2];
		$files = $this->rawlist($m[1]);
		foreach ($files as $file) {
			if ('d' === $file[0]) {
				$file = preg_split('/\\s+/', $file);
				if ($name === $file[8]) {
					return true;
				}
			}
		}
		return false;
	}

}
