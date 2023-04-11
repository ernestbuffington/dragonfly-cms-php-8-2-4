<?php
/*********************************************
  Dragonfly CMS, Copyright (c) 2004 by CPGNuke Dev Team
  http://dragonflycms.org
  Released under GNU GPL version 2 or any later version
**********************************************/
namespace Dragonfly;

class Page {

	private static
		# head
		$title = array(),
		$metaTags = array(),
		$data = array(),
		#footer
		$signature = array();

	/*
	 * Header
	 */

	public static function title($str, $translate=false)
	{
		if (empty($str) || !is_string($str)) { return; }
		static $L10N;
		if (!isset($L10N)) {
			$L10N = \Dragonfly::getKernel()->L10N;
		}

		if (empty(self::$title[0])) {
			self::$title[0] = $L10N->dbget(\Dragonfly::getKernel()->CFG->global->sitename);
		}

		self::$title[] = $translate ? $L10N->dbget($str) : $str;
	}

	# set or overwrite the page title
	# use '.' as very first char inside the string to extend the title
	public static function setTitle($str)
	{
		$str = is_string($str) ? strip_tags($str) : '';
		if (empty($str)) { return; }
		if ('.' === $str[0]) {
			self::$title[0] = self::$title[0] ?: \Dragonfly::getKernel()->CFG->global->sitename;
			$str = substr($str, 1);
			if ($str) { self::$title[] = $str; }
		} else {
			self::$title = array($str);
		}
	}

	# meta tags
	public static function metatag($name, $content, $rename='')
	{
		if (empty($content)) return;
		$content = htmlprepare(\Dragonfly\Output\HTML::minify($content), false, ENT_QUOTES, true);
		self::$metaTags[$name]['name'] = $rename ? $rename : 'name';
		self::$metaTags[$name]['content'] = $content;
	}

	# link tags
	public static function link($rel, $href, $lang='')
	{
		$lang = $lang ? " hreflang=\"{$lang}\"" : '';
		self::$data[] = "<link rel=\"{$rel}\"{$lang} href=\"{$href}\"/>";
	}

	# any custom tag
	public static function tag($str)
	{
		self::$data[] = '<'.strip_tags($str).'/>';
	}

	# custom data
	public static function headerData($str)
	{
		self::$data[] = $str;
	}

	# retrive it all
	public static function getHeaders()
	{
		global $header, $modheader;
		foreach (self::$metaTags as $k => $v) {
			self::$data[] = '<meta '.$v['name'].'="'.$k.'" content="'.$v['content'].'"/>';
		}
		if (DF_MODE_DEVELOPER) self::$data[] = '<!-- still using $header -->';
		self::$data[] = $header;
		if (DF_MODE_DEVELOPER) self::$data[] = '<!-- still using $modheader -->';
		self::$data[] = $modheader;
		if (DF_MODE_DEVELOPER) self::$data[] = '<!-- end -->';
		return \Dragonfly\Output\HTML::minify(implode("\n", self::$data)."\n");
	}

	# access allowed private properties, read only
	public static function get($k)
	{
		if ('title' === $k) {
			return implode(' '._BC_DELIM.' ', array_reverse(self::$title));
		}
		if (isset(self::$metaTags[$k])) return self::$metaTags[$k]['content'];
	}

	/*
	 * Body
	 */

	public static function confirm($link, $msg, $hidden = null)
	{
		$OUT = \Dragonfly::getKernel()->OUT;
		$OUT->MESSAGE_TEXT = $msg;
		$OUT->S_CONFIRM_ACTION = $link;
		if (is_array($hidden)) {
			foreach ($hidden as &$input) {
				$input = '<input type="hidden" name="'.htmlspecialchars($input['name']).'" value="'.htmlspecialchars($input['value']).'"/>';
			}
			$hidden = implode('',$hidden);
		}
		$OUT->S_HIDDEN_FIELDS = $hidden;
		$OUT->display('confirm');
		require('footer.php');
	}

	/*
	 * Footer
	 */

}
