<?php

namespace Dragonfly\Blocks;

class Admin extends Block
{

	function __construct($bid=0)
	{
		parent::__construct($bid);
		$this->data['view_to'] = array(2);
	}

	function __get($k)
	{
		if ('body' === $k || 'S_CONTENT' === $k) {
			if (!is_admin()) { return false; }
			$waitlist = \Dragonfly::getKernel()->CACHE->get('waitlist');
			if (!$waitlist) {
				$waitlist = array();
				if (is_dir(BASEDIR. 'admin/wait') && $waitdir = dir(BASEDIR. 'admin/wait')) {
					while ($waitfile = $waitdir->read()) {
						if (preg_match('/^wait_(.*?)\.php$/', $waitfile, $match)) {
							$waitlist[$match[1]] = "admin/wait/{$waitfile}";
						}
					}
					$waitdir->close();
				}
				// Dragonfly system
				$waitlist += \Dragonfly\Modules::ls('admin/adwait.inc');
				\Dragonfly::getKernel()->CACHE->set('waitlist', $waitlist);
			}
			$content = '';
			foreach ($waitlist as $module => $file) {
				if (can_admin($module)) {
					$db = \Dragonfly::getKernel()->SQL;
					$prefix = substr($db->TBL->prefix,0,-1);
					$MAIN_CFG = \Dragonfly::getKernel()->CFG;
					require $file;
				}
			}
			return \Dragonfly\Page\Menu\Admin::display('blockgfx')
				. '<hr/>' . $this->data['content']
				. '<hr/>' . $content;
		}

		return parent::__get($k);
	}

	function __set($k, $v)
	{
		if ('view_to' === $k) { return; }
		parent::__set($k, $v);
	}

}
