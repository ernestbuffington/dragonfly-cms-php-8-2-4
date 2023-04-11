<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Dragonfly\L10N;

abstract class V9
{
	public static
		$browserlang = array(
		'af'    => 'afrikaans',
		'sq'    => 'albanian',
		'ar'    => 'arabic',
//		'hy'    => 'armenian',
//		'ast'   => 'asturian',
		'eu'    => 'basque',
//		'be'    => 'belarusian',
		'bs'    => 'bosanski',    // bosnian -bosanski is nuke lang name
		'bg'    => 'bulgarian',
		'ca'    => 'catalan',
		'zh'    => 'chinese_traditional', // Hong Kong, Taiwan or Malaysia
		'zh-cn' => 'chinese_simplified',  // China
//		'hr'    => 'croatian',
		'cs'    => 'czech',
		'da'    => 'danish',
		'dcc'   => 'desi',        // Deccan, India
		'nl'    => 'dutch',
		'en'    => 'english',
//		'eo'    => 'esperanto',
		'et'    => 'estonian',
//		'eu'    => 'euraska',
//		'fo'    => 'faroese',
		'fa'    => 'farsi',
		'fi'    => 'finnish',
		'fr'    => 'french',
		'gl'    => 'galego',      // galician- galego is nuke lang name
//		'ka'    => 'georgian',
		'de'    => 'german',
		'el'    => 'greek',
		'he'    => 'hebrew',
		'hi'    => 'hindi',
		'hu'    => 'hungarian',
		'is'    => 'icelandic',
		'id'    => 'indonesian',
//		'ga'    => 'irish',
		'it'    => 'italian',
		'ja'    => 'japanese',
		'ko'    => 'korean',
		'ku'    => 'kurdish',
		'lv'    => 'latvian',
		'lt'    => 'lithuanian',
		'mk'    => 'macedonian',
		'ms'    => 'malayu',
		'no'    => 'norwegian',
//		'nb'    => 'norwegian',   // bokmal
//		'nn'    => 'norwegian',   // nynorsk
		'pl'    => 'polish',
		'pt'    => 'portuguese',
		'pt-br' => 'brazilian',
		'ro'    => 'romanian',
		'ru'    => 'russian',
//		'gd'    => 'scots gealic',
		'sr'    => 'serbian',
		'sk'    => 'slovak',
		'sl'    => 'slovenian',
		'es'    => 'spanish',
		'es-es' => 'castellano',  // Spain
		'sv'    => 'swedish',
		'sw'    => 'swahili',     // Kenya and Tanzania
		'tl'    => 'tagalog',
		'th'    => 'thai',
		'tr'    => 'turkish',
		'ug'    => 'uighur',      // Turkish, Uzbek, China
		'uk'    => 'ukrainian',
		'vi'    => 'vietnamese',
//		'cy'    => 'welsh',
//		'xh'    => 'xhosa',
//		'yi'    => 'yiddish',
//		'zu'    => 'zulu',
	);

	public static function getV9Language($lng)
	{
		if (isset(self::$browserlang[$lng])) { return self::$browserlang[$lng]; }
		$lng = substr($lng,0,-3);
		if (isset(self::$browserlang[$lng])) { return self::$browserlang[$lng]; }
	}

}
