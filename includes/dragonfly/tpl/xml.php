<?php

namespace Dragonfly\TPL;

class XML extends \Poodle\TPL
{
	protected
		$tpl_path = 'themes/default/',
		$tpl_type = 'xml';

	# TPL
	public function display($filename, $data=null, $mtime=0, $final=false)
	{
		parent::display($filename, $data, $mtime, \Poodle\TPL::OPT_PUSH_DOCTYPE | \Poodle\TPL::OPT_END_PARSER);
	}
}
