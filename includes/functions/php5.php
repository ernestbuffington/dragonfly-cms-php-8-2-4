<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/functions/php5.php,v $
  $Revision: 9.4 $
  $Author: nanocaiordo $
  $Date: 2007/04/07 16:04:38 $
**********************************************/

if (!function_exists('stripos')) {

	function stripos($str, $needle, $offset=0) {
		if (is_scalar($str) && is_scalar($str) && is_int($offset)) {
			if (false === $pos = strpos(strtolower($str), strtolower($needle), $offset)) {
				return false;
			}
			#else if ($pos == 0) {
			else if ($pos == 0 || empty($pos)) {
				return true;
			}
			return $pos;
		}
		return false;
	}
}
