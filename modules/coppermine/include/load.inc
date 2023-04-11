<?php
/*********************************************
  CPG-Dragonfly™ CMS
  ********************************************
  Copyright (c) 2004-2005 by CPG Dev Team
  http://www.dragonflycms.com

  CPG-Nuke is released under the terms and conditions
  of the GNU GPL version 2 or any later version
****************************************************************************/
if (!class_exists('Dragonfly', false)) { exit; }

global $op, $module_name, $CONFIG, $USER, $USER_DATA;

require_once('includes/coppermine/functions.inc');

// Start loading viewer settings

$cpg_dir = defined('ADMIN_PAGES') ? $op : $module_name;

$USER_DATA = \Coppermine::getCurrentUserData();
define('USER_ID', $USER_DATA['ID']);
define('USER_IS_ADMIN', $USER_DATA['has_admin_access'] || can_admin($cpg_dir));
define('USER_CAN_CREATE_ALBUMS', $USER_DATA['can_create_albums']);
define('USER_ADMIN_MODE', (USER_CAN_CREATE_ALBUMS && USER_ID));
define('VIS_GROUPS', USER_IS_ADMIN ? 'visibility > -1' : 'visibility IN (0,'.$USER_DATA['GROUPS'].')');
if (!defined('PIC_VIEWS')) {
	\Dragonfly::getKernel()->L10N->load('coppermine');
}

$CONFIG = \Coppermine::getConfigInstance($cpg_dir);

// load the main template
if (!defined('NO_HEADER')) {
	// Decode the user profile contained in a cookie
	if (isset($_COOKIE[$CONFIG['cookie_name'] . '_data'])) {
		$USER = json_decode(base64_decode($_COOKIE[$CONFIG['cookie_name'] . '_data']), true);
	}
	if (!isset($USER) || !is_array($USER)) { $USER = array(); }

	if (!empty($_GET['meta']) && !preg_match('#^[a-z]+$#',  $_GET['meta'])) { cpg_error(PARAM_MISSING, 404); };
	if (!empty($_GET['sort']) && !preg_match('#^[a-z_]+$#', $_GET['sort'])) { cpg_error(PARAM_MISSING, 404); };

	if (!defined('ADMIN_PAGES')) {
		\Dragonfly\Output\Css::add('coppermine/style');
		\Dragonfly\Output\Js::add('modules/coppermine/javascript/scripts.js');
	}

	if (isset($_GET['file'])) {
		if ($_GET['file'] == 'displayimage' && isset($_GET['slideshow']) && $CONFIG['fullsize_slideshow']) {
			$Module->sides = \Dragonfly\Blocks::NONE;
		}
		else if ($_GET['file'] == 'displayimage' && $CONFIG['right_blocks'] == 0 && $Module->sides & 2) {
			$Module->sides = \Dragonfly\Blocks::LEFT;
		}
		else if (!($Module->sides & \Dragonfly\Blocks::RIGHT)) {
			$Module->sides &= \Dragonfly\Blocks::LEFT;
		}
	}
	if ($Module->sides) {
		\Dragonfly\Blocks::custom(array(
			'bid' => 20000,
			'view_to' => defined('ADMIN_PAGES') ? 2 : 1,
			'side' => $Module->sides & \Dragonfly\Blocks::LEFT ? 'l' : 'r',
			'title' => _coppermineLANG.' '._TB_TASKS,
			'content' => coppermine_block()
		));
	}

	\Dragonfly\Page::title($CONFIG['gallery_name']);
}
