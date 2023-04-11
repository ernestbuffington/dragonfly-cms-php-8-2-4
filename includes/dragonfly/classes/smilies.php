<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  https://dragonfly.coders.exchange

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

abstract class Smilies
{
	public static
		$path,
		$desc;

	public static function get($and_duplicates=false)
	{
		$K = \Dragonfly::getKernel();
		$smilies = $K->CACHE->get(__CLASS__);
		if (!$smilies) {
			$qr = $K->SQL->query("SELECT code, smile_url file, emoticon title FROM {$K->SQL->TBL->bbsmilies} ORDER BY pos");
			$smilies = array();
			while ($r = $qr->fetch_assoc()) {
				if (is_file('images/smiles/'.$r['file'])) {
					$smilies[] = $r;
				}
			}
			if ($smilies) {
				$K->CACHE->set(__CLASS__, $smilies);
			}
		}

		if ($and_duplicates) {
			return $smilies;
		}

		$icons = array();
		foreach ($smilies as $r) {
			if (!isset($icons[$r['file']])) {
				$icons[$r['file']] = $r;
			}
		}
		return array_values($icons);
	}

	public static function parse($message, $url='')
	{
		$message = \Poodle\Unicode\Emoji::aliasesToUTF8($message);
		if (!static::$path) {
			$theme = \Dragonfly::getKernel()->OUT->theme;
			static::$path = is_dir("themes/{$theme}/images/smiles") ? "themes/{$theme}/images/smiles/" : 'images/smiles/';
		}
		static $smiles, $regex;
		if (!isset($smiles)) {
			$smiles = $regex = array();
			$smilies = static::get(true);
			if (!$url) { $url = DF_STATIC_DOMAIN; }
			if ($url !== '') { $url = rtrim($url,'/') . '/'; }
			foreach ($smilies as $smiley) {
				$desc = static::getLang($smiley['title']);
				$regex[] = preg_quote($smiley['code'],'#');
				$smiles[$smiley['code']] = '<img class="bbsmilies" src="' . $url . static::$path . $smiley['file']	. '" alt="'.$desc.'" title="'.$desc.'"/>';
			}
			$regex = '#(^|>|\\s)(' . implode('|', $regex) . ')(?=\\s|<|$)#s';
		}
		if ($smiles) {
			return preg_replace_callback($regex, function($m) use ($smiles) {
				return $m[1] . $smiles[$m[2]];
			}, $message);
		}
		return $message;
	}

	protected static function getLang($var)
	{
		global $smilies_desc;
		return isset($smilies_desc[$var]) ? $smilies_desc[$var] : $var;
	}

}
