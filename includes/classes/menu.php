<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Source: /public_html/includes/classes/menu.php,v $
  $Revision: 1.1 $
  $Author: nanocaiordo $
  $Date: 2007/09/02 14:49:55 $
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

class Menu {

	public static function mmimage($image) {
		global $CPG_SESS;
		if (isset($CPG_SESS['mmimages'][$CPG_SESS['theme']][$image])) {
			return $CPG_SESS['mmimages'][$CPG_SESS['theme']][$image];
		} else {
			if (file_exists('themes/'.$CPG_SESS['theme'].'/images/blocks/CPG_Main_Menu/'.$image)) {
				$img = 'themes/'.$CPG_SESS['theme'].'/images/blocks/CPG_Main_Menu/'.$image;
			} else {
				$img = 'images/blocks/CPG_Main_Menu/'.$image;
			}
			$CPG_SESS['mmimages'][$CPG_SESS['theme']][$image] = $img;
			return $img;
		}
	}
}
