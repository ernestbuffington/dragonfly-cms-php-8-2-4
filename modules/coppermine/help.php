<?php
/***************************************************************************
   Coppermine Photo Gallery for Dragonfly CMS™
  **************************************************************************
   Port Copyright © 2004-2015 CPG Dev Team
   https://dragonfly.coders.exchange/
  **************************************************************************
   v1.1 © by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
****************************************************************************/

require(__DIR__ . '/include/load.inc');
if (file_exists(__DIR__ . "/include/help-{$currentlang}.inc")) {
	require_once(__DIR__ . "/include/help-{$currentlang}.inc");
} else {
	require_once(__DIR__ . "/include/help-english.inc");
}

pageheader('Help');
$OUT = \Dragonfly::getKernel()->OUT;
$OUT->CPG_ADMIN = can_admin($module_name);
$OUT->display('coppermine/help');
pagefooter();
