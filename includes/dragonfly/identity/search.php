<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\Identity;

class Search
{

	public static function byName($name, $any=false)
	{
		$name = trim($name);
		if (empty($name)) { return array(); }

		$K   = \Poodle::getKernel();
		$SQL = $K->SQL;

		$name = $SQL->escape_string(mb_strtolower($name));
		if (false === strpos($name, '*')) { $name = "*{$name}*"; }
		$name = str_replace('*','%',$name);
/*
		if (!$any_type && (!isset($K->IDENTITY) || !$K->IDENTITY->isAdmin())) {
			$where .= ' AND user_level>0';
		}
*/
		return $SQL->query("SELECT username FROM {$SQL->TBL->users}
			WHERE user_nickname_lc LIKE '{$name}'
			  AND user_id > " . \Dragonfly\Identity::ANONYMOUS_ID . "
			ORDER BY username");
	}

}
