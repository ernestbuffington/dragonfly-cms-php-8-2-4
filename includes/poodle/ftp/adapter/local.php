<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\FTP\Adapter;

class Local implements \Poodle\FTP\Interfaces\Adapter
{
	protected
		$connection;

	public function connect($host, $username, $password)
	{
		$this->connection = true;
		return true;
	}

	public function disconnect()
	{
		$this->connection = false;
	}

	public function fget($handle, $remote_file, $resumepos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$fp = fopen($remote_file, 'rb');
		if (!$fp || !stream_copy_to_stream($fp, $handle, -1, $resumepos)) {
			throw new \Exception('FTP fget failed');
		}
		fclose($fp);
		return true;
	}

	public function fput($remote_file, $handle, $startpos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$fp = fopen($remote_file, $startpos ? 'cb' : 'wb');
		if ($fp && $startpos) {
			fseek($fp, $startpos);
		}
		if (!$fp || !stream_copy_to_stream($handle, $fp, -1, $startpos)) {
			throw new \Exception('FTP fput failed');
		}
		fclose($fp);
/*
		if (false === file_put_contents($remote_file , $handle)) {
			throw new \Exception('FTP fput failed');
		}
*/
		return true;
	}

	public function get($local_file, $remote_file, $resumepos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!copy($remote_file, $local_file)) {
			throw new \Exception('FTP get failed');
		}
		return true;
	}

	public function put($remote_file, $local_file, $startpos = 0)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!copy($local_file, $remote_file)) {
			throw new \Exception('FTP put failed');
		}
		return true;
	}

	public function getSystemType()
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		return 'UNIX';
	}

	public function raw($command)
	{
		throw new \Exception('FTP raw not supported');
	}

	public function rawlist($directory = null, $recursive = false)
	{
		throw new \Exception('FTP rawlist not supported');
	}

	public function scanDir($directory = null)
	{
		throw new \Exception('FTP scanDir not supported');
	}

	public function setPassiveMode($pasv)
	{
		return true;
	}

	public function exists($name)
	{
		if (!preg_match('#^(.*/)?([^/]+)$#D', $name, $m)) {
			throw new \Exception('Invalid name');
		}
		return file_exists($name);
	}

	public function isDir($directory)
	{
		if (!preg_match('#^(.*/)?([^/]+)$#D', $directory, $m)) {
			throw new \Exception('Invalid directory');
		}
		return is_dir($directory);
	}

	public function chdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!chdir($directory)) {
			throw new \Exception('chdir failed');
		}
		return true;
	}

	public function chmod($path, $mode)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!chmod($path, $mode)) {
			throw new \Exception('chmod failed');
		}
		return true;
	}

	public function delete($path)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!unlink($path)) {
			throw new \Exception('delete failed');
		}
		return true;
	}

	public function fileSize($remote_file)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		$result = filesize($remote_file);
		if (!$result) {
			throw new \Exception('size failed');
		}
		return $result;
	}

	public function mkdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!mkdir($directory)) {
			throw new \Exception('mkdir failed');
		}
		return true;
	}

	public function rename($oldname, $newname)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!rename($oldname, $newname)) {
			throw new \Exception('rename failed');
		}
		return true;
	}

	public function rmdir($directory)
	{
		if (!$this->connection) {
			throw new \Exception('Not connected');
		}
		if (!rmdir($directory)) {
			throw new \Exception('rmdir failed');
		}
		return true;
	}

	public function getCWD()
	{
		return getcwd();
	}

}
