<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2015 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\ModManager;

if (!defined('ADMIN_MOD_INSTALL')) { exit; }

class Your_Account extends SetupBase
{
	public
		$author      = 'DJ Maze',
		$description = 'Powerful member management system',
		$modname     = 'My Account',
		$version     = '10.0',
		$website     = 'dragonflycms.org',
		$dbtables    = array('users', 'users_fields');

	public function pre_install() {}

	public function post_install() {}

	public function pre_upgrade($prev_version) {}

	public function post_upgrade($prev_version) {}

	public function pre_uninstall() {}

	public function post_uninstall() {}

}
