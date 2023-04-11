<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\File;

abstract class Archive
{
	public
		$toc,
		$filename;

	function __construct($filename)
	{
		$this->filename = $filename;
		$this->load_toc();
	}

	public function type()
	{
		$c = get_class($this);
		return strtolower(substr($c,strrpos($c,'\\')+1));
	}

	public function close() { return true; }

	abstract public function extract($id, $to=false);

	abstract protected function load_toc();
}
