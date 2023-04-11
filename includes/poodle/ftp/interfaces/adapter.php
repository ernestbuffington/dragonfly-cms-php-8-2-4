<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\FTP\Interfaces;

interface Adapter
{

	public function connect($host, $username, $password);

	public function disconnect();

	public function chdir($directory);

	public function chmod($path, $mode);

	public function delete($path);

	// NOTE: Not all servers support this feature.
	public function fileSize($remote_file);

	public function fget($handle, $remote_file, $resumepos = 0);

	public function fput($remote_file, $handle, $startpos = 0);

	public function get($local_file, $remote_file, $resumepos = 0);

	public function put($remote_file, $local_file, $startpos = 0);

	public function mkdir($directory);

	public function rename($oldname, $newname);

	public function rmdir($directory);

	public function getSystemType();

	public function getCWD();

	public function raw($command);

	public function rawlist($directory = null, $recursive = false);

	public function scanDir($directory = null);

	public function setPassiveMode($pasv);

	public function exists($name);

	public function isDir($directory);

}
