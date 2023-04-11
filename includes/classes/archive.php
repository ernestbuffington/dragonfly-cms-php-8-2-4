<?php
/*
	Dragonfly™ CMS, Copyright ©  2004 - 2023
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

abstract class archive
{

	public static function load($filename)
	{
		trigger_deprecated('Change to \Poodle\File::open($filename)');
		return \Poodle\File::open($filename);
	}

	public static function get_type($filename)
	{
		trigger_deprecated('Change to \Poodle\File::getType($filename)');
		return \Poodle\File::getType($filename);
	}

}
