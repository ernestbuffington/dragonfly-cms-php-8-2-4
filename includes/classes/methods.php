<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/classes/methods.php,v $
  $Revision: 1.1 $
  $Author: nanocaiordo $
  $Date: 2007/08/05 07:19:04 $
**********************************************/

class Method
{
	public static function priv($file, $class, $function)
	{
		if (PHPVERS >= 43) {
			$tmp = debug_backtrace();
			for ($i=0; $i<count($tmp); ++$i) {
				if ($function == $tmp[$i]['function'] && ($file != $tmp[$i]['file'] || $class != $tmp[$i]['class'])) {
					trigger_error('<br />Call to a private method '.$class.'::'.$function.' in '.$tmp[$i]['file'].' at line '.$tmp[$i]['line'], E_USER_ERROR);
				}
			}
		}
	}
} 