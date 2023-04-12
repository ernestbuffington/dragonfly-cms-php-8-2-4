<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /cvs/html/includes/classes/multibyte.php,v $
  $Revision: 1.6 $
  $Author: nanocaiordo $
  $Date: 2007/07/26 08:18:45 $
**********************************************/

class MB {

	function tolower($str) { return mb_strtolower($str); }
	function substr($str, $start, $end=0) { return mb_substr($str, $start, $end); }
	function rpos($str, $needle) { return mb_strrpos($str, $needle); }
	function len($str) { return mb_strlen($str); }
	function ucfirst($str) { return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1); }

	/*
	  This function replaces special UTF characters to their ANSI equivelant for
	  correct processing by SQL, keywords, search, etc.
	*/
	function fix_spaces($str)
	{
		# replace NO-BREAK and Ideographic space
		$str = preg_replace('#[\xC2\xA0]|[\xE3][\x80][\x80]#', ' ', $str);
		return $str;
	}

}

if (!function_exists('mb_strtolower')) { require CORE_PATH.'functions/mb.php'; }
if (function_exists('mb_internal_encoding')) { mb_internal_encoding('UTF-8'); }
