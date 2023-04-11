<?php

namespace Poodle\PackageManager;

class PackageData extends \PharData
{
	protected
		$packagePath;

	function __construct($fname)
	{
		parent::__construct($fname);
		$this->packagePath = $fname;
		$this->setInfoClass('Poodle\\PackageManager\\PackageFileInfo');
	}

	public function getChildren()
	{
		$obj = parent::getChildren();
		$obj->packagePath = $this->packagePath;
		return $obj;
	}

	public function current()
	{
		$obj = parent::current();
		$obj->phar = $this;
		return $obj;
	}

	public function key()
	{
		return substr($this->getPathname(), 8 + strlen($this->packagePath));
	}

	public function getPharPath()
	{
		return 'phar://'.$this->packagePath.'/';
	}

	public function getRecursiveIteratorIterator()
	{
		return new \RecursiveIteratorIterator(
			new PackageDataFilter($this),
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

}

class PackageFileInfo extends \PharFileInfo
{

	public function extractTo($dst, $mask = null)
	{
		$name = substr($this->getPathname(), strlen($this->phar->getPharPath()));
		try {
			if (is_string($dst)) {
				if ($this->isDir()) {
					if (!is_dir($dst)) {
						mkdir($dst, 0755, true);
					}
				} else if ($this->isFile()) {
					if (!is_dir(dirname($dst))) {
						mkdir(dirname($dst), 0755, true);
					}
					file_put_contents($dst, file_get_contents($this->getPathname(), 'r'));
				}
			} else
			if ($dst instanceof \Poodle\FTP\Interfaces\Adapter) {
				$mode = substr(sprintf('%o', $this->getPerms()), -1);
				if ($this->isDir()) {
					if (!$dst->isDir($name)) {
						$dst->mkdir($name);
						if (!$mask || 7 == $mode) {
							$dst->chmod($name, 0777);
						}
					}
				} else if ($this->isFile()) {
/*
					if ($dst->exists($name)) {
						$dst->rename($name, $name.$orig);
					}
*/
					$fp = fopen($this->getPathname(), 'r');
					try {
						$dst->fput($name, $fp);
						if (!$mask || 6 == $mode) {
							$dst->chmod($name, 0666);
						}
					} finally {
						fclose($fp);
					}
				}
			}
		} catch (\Exception $e) {
			throw new \Exception("{$name}: {$e->getMessage()}");
		}
	}
}

class PackageDataFilter extends \RecursiveFilterIterator
{

	public function accept()
	{
		static $ignore = array(
		'.DS_Store',
		'.directory',
		'Thumbs.db',
		'desktop.ini',
		'ehthumbs.db',
		'__MACOSX',
		'CVS',
		'.svn',
		'.hg',
		'.git',
		);
		$name = $this->current()->getFilename();
		return !in_array($name, $ignore, true)
			&& !preg_match('/^\\.(hg|cvs|git)/', $name)
			&& !preg_match('/(\\.bak|~)$/D', $name);
	}

}
