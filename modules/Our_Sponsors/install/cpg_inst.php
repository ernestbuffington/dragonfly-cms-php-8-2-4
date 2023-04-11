<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Our_Sponsors extends \Dragonfly\ModManager\SetupBase
{
	public
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('banner'),
		$description = 'Create and manage advertisements for your site',
		$modname     = 'Our Sponsors',
		$version     = '10.0.0',
		$website     = 'dragonflycms.org',
		$blocks      = true;

	public function pre_install()
	{
		return true;
	}

	public function post_install()
	{
		return true;
	}

	public function pre_upgrade($prev_version)
	{
		return true;
	}

	public function post_upgrade($prev_version)
	{
		return true;
	}

	public function pre_uninstall()
	{
		return true;
	}

	public function post_uninstall()
	{
		return true;
	}
}
