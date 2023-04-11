<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by DragonflyCMS Dev. Team.
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Groups extends \Dragonfly\ModManager\SetupBase
{
	public
		$author      = 'CPG-Nuke Dev Team',
		$dbtables    = array('bbgroups', 'bbuser_group'),
		$description = 'Manage user-based groups',
		$modname     = 'Groups',
		$radmin      = true,
		$version     = '1.1',
		$website     = 'dragonflycms.org';

	public function pre_install() { return true; }

	public function post_install() { return true; }

	public function pre_upgrade($prev_version) { return true; }

	public function post_upgrade($prev_version) { return true; }

	public function pre_uninstall() { return true; }

	public function post_uninstall() { return true; }
}
