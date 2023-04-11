<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin('social')) { die('Access Denied'); }

$Social = Dragonfly::getKernel()->extend('SOCIAL', 'Dragonfly\\Social');
$Social->{$_SERVER['REQUEST_METHOD']}();
