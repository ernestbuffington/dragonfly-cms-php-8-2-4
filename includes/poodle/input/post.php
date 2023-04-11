<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Input;

class POST extends GET
{
	public function html() { return \Poodle\Input\HTML::fix(self::_get(func_get_args())); }

	public static function raw_data()
	{
		return file_get_contents('php://input');
	}

	public static function max_size()
	{
		return \Poodle\PHP\INI::getInt('post_max_size', '8M');
	}

	public function __toString() { return http_build_query($this, '', '&'); }
}
