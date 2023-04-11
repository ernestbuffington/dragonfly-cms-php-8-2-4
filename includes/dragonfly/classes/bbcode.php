<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

New way:
	\Dragonfly\BBCode::pushHeaders($smilies=false);
	<textarea class="bbcode">
**********************************************/

namespace Dragonfly;

abstract class BBCode
{
	protected static $bb_codes = array();

	public static function pushHeaders($smilies=false)
	{
		\Dragonfly\Output\Css::add('bbcode');
		\Dragonfly\Output\Css::add('poodle/emoji');
		\Dragonfly\Output\Js::add('includes/poodle/javascript/bbcode.js');
		if ($smilies) {
			\Dragonfly\Output\Js::inline('Poodle_BBCode.emoticons = '.\Dragonfly::dataToJSON(Smilies::get()).';');
		}
		\Dragonfly\Output\Css::add('poodle/syntaxhighlight');
		\Dragonfly\Output\js::add('includes/poodle/javascript/syntaxhighlight.js');
	}

	public static function decodeAll($text, $allowed=0, $allow_html=false, $url='')
	{
		return Smilies::parse(static::decode($text, $allowed, $allow_html), $url);
	}

	public static function setSmilies($message, $url='')
	{
		return Smilies::parse($message, $url);
	}

	public static function encode_html($text)
	{
		return (false !== strpos($text, '<')) ? htmlprepare($text, false, ENT_NOQUOTES) : $text;
	}

	public static function encode($text)
	{
		return $text;
	}

	public static function decode($text, $allowed=0, $allow_html=false)
	{
		if (!static::$bb_codes) {
			\Dragonfly::getKernel()->L10N->load('bbcode');
			global $bbcode_common;
			$theme = \Dragonfly::getKernel()->OUT->theme;
			if (is_file("themes/{$theme}/bbcode.inc")) {
				require_once "themes/{$theme}/bbcode.inc";
			} else {
				require_once 'themes/default/bbcode.inc';
			}
			static::$bb_codes = $bb_codes;
			\Dragonfly\Output\Css::add('poodle/syntaxhighlight');
			\Dragonfly\Output\js::add('includes/poodle/javascript/syntaxhighlight.js');
		}

		# First: If there isn't a "[" and a "]" in the message, don't bother.
		if (!(strpos($text, '[') !== false && strpos($text, ']'))) {
			if ($allow_html) {
				return (false !== strpos($text, '<') ? $text : nl2br($text));
			}
			return nl2br(strip_tags($text));
		}

		// strip the obsolete bbcode_uid
		$text = preg_replace("/:([a-z0-9]+:)?[a-z0-9]{10}\\]/si", ']', $text);

		# pad it with a space so we can distinguish between FALSE and matching the 1st char (index 0).
		$text = static::split_on_bbcodes($text, $allowed, $allow_html);

		# Patterns and replacements for URL, email tags etc.
		$patterns = $replacements = array();

		# colours
		$patterns[] = '#\[color=(\#[0-9A-F]{6}|[a-z]+)\](.*?)\[/color\]#si';
		$replacements[] = '<span style="color: \\1">\\2</span>';

		# size
		$patterns[] = '#\[size=([1-2]?[0-9])\](.*?)\[/size\]#si';
		$replacements[] = '<span style="font-size: \\1px">\\2</span>';
		$patterns[] = '#\[size=(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger)\](.*?)\[/size\]#si';
		$replacements[] = '<span style="font-size: \\1">\\2</span>';

		# [b] and [/b] for bolding text.
		$patterns[] = '#\[b\](.*?)\[/b\]#si';
		$replacements[] = '<span style="font-weight: bold">\\1</span>';

		# [u] and [/u] for underlining text.
		$patterns[] = '#\[u\](.*?)\[/u\]#si';
		$replacements[] = '<span style="text-decoration: underline">\\1</span>';

		# [i] and [/i] for italicizing text.
		$patterns[] = '#\[i\](.*?)\[/i\]#si';
		$replacements[] = '<span style="font-style: italic">\\1</span>';

		# align
		$patterns[] = '#\[align=(left|right|center|justify)\](.*?)\[/align\]#si';
		$replacements[] = '<div style="text-align:\\1">\\2</div>';

		# [search(=google)?]search string[/search]
		$text = preg_replace_callback(
			'#\\[search(=google)?\\](.*?)\\[/search\\]#is',
			function($m){
				$m[2] = str_replace('"', '&quot;', $m[2]);
				if ('=google' == $m[1]) {
//					return '<a href="http://google.com/search?q='.urlencode(htmlspecialchars_decode($m[2])).'" target="_blank" rel="nofollow">'.$m[2].'</a>';
					return '<form action="https://google.com/search" method="get"><input type="text" name="q" value="'.$m[2].'"/><input type="submit" value="Search Google"/></form>';
				}
				return '<form action="search.html" method="post"><input type="text" name="search" value="'.$m[2].'"/><input type="submit" value="Search"/></form>';
			},
			$text);

		# [pretty] local
		$text = preg_replace_callback(
			'#\\[pretty\\]([\\w]+[^\\["\\s<>]*?)\\[/pretty\\]#is',
			function($m){return '<a href="'.\URL::index($m[1]).'" title="'.$m[1].'">'.\URL::shrink(\URL::index($m[1])).'</a>';},
			$text);
		$text = preg_replace_callback(
			'#\\[pretty=([\\w]+[^\\]"\\s<>]*?)\\](.*?)\\[/pretty\\]#is',
			function($m){return '<a href="'.\URL::index($m[1]).'" title="'.$m[1].'">'.htmlspecialchars($m[2]).'</a>';},
			$text);

		# [url] local
		$text = preg_replace_callback(
			'#\\[url\\]([\\w]+(\\.html|\\.php|/)[^\\["\\s<>]*?)\\[/url\\]#is',
			function($m){return '<a href="'.$m[1].'" title="'.$m[1].'">'.\URL::shrink($m[1]).'</a>';},
			$text);
		$text = preg_replace_callback(
			'#\\[url=([\\w]+(?:\\.html|\\.php|/)[^\\]"\\s<>]*?)\\](.*?)\\[/url\\]#is',
			function($m){return '<a href="'.$m[1].'" title="'.$m[1].'">'.htmlspecialchars($m[2]).'</a>';},
			$text);

		# [url]xxxx://www.cpgnuke.com[/url]
		$text = preg_replace_callback(
			'#\\[url\\]([\\w]+?://[^\\["\\s<>]+)\\[/url\\]#is',
			function($m){return '<a href="'.$m[1].'" target="_blank" title="'.$m[1].'" rel="nofollow">'.\URL::shrink($m[1]).'</a>';},
			$text);
		# [url]www.cpgnuke.com[/url] (no xxxx:// prefix).
		$text = preg_replace_callback(
			'#\\[url\\]((www|ftp)\\.[^\\["\\s<>]+)\\[/url\\]#is',
			function($m){return '<a href="http://'.$m[1].'" target="_blank" title="'.$m[1].'" rel="nofollow">'.\URL::shrink($m[1]).'</a>';},
			$text);
		# [url=www.cpgnuke.com]cpgnuke[/url] (no xxxx:// prefix).
		$patterns[] = '#\\[url=((www|ftp)\\.[^\\]"\\s<>]+)\\](.*?)\\[/url\\]#is';
		$replacements[] = "<a href=\"http://\\1\" target=\"_blank\" title=\"\\1\" rel=\"nofollow\">\\3</a>";
		# [url=xxxx://www.cpgnuke.com]cpgnuke[/url]
		$patterns[] = '#\\[url=([\\w]+://[^\\]"\\s<>]+)\\](.*?)\\[/url\\]#is';
		$replacements[] = "<a href=\"\\1\" target=\"_blank\" title=\"\\1\" rel=\"nofollow\">\\2</a>";

		# [email]user@domain.tld[/email] code..
		$patterns[] = "#\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/email\]#si";
		$replacements[] = "<a href=\"mailto:\\1\">\\1</a>";

		if ($allowed) {
			# [hr]
			$patterns[] = "#\[hr\]#si";
			$replacements[] = '<hr/>';

			# marquee
			$patterns[] = "#\[marq=(left|right|up|down)\](.*?)\[/marq\]#si";
			$replacements[] = '<marquee direction="\\1" scrolldelay="60" scrollamount="1" onmouseover="this.stop()" onmouseout="this.start()">\\2</marquee>';

			# [img]image_url_here[/img] code..
			$text = preg_replace_callback(
				'#\\[img\\](([\\w]+(?:://|\\.|/))?(?:[^:"\\p{C}<>\\\\\\r\\n\\t])+?)\\[/img\\]#siu',
				function($m){return '<img src="'.str_replace(' ', '%20', $m[1]).'" alt=""/>';},
				$text);

			# [flash width= height= loop= ] and [/flash] code..
			$patterns[] = "#\[flash width=([0-6]?[0-9]?[0-9]) height=([0-4]?[0-9]?[0-9])\]((ht|f)tp://)([^ \?&=\"\n\r\t<]*?(\.(swf|fla)))\[/flash\]#si";
			$replacements[] = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="\\1" height="\\2">
	<param name="movie" value="\\3\\5"/>
	<param name="quality" value="high"/>
	<param name="scale" value="noborder"/>
	<param name="wmode" value="transparent"/>
	<param name="bgcolor" value="#000000"/>
  <embed src="\\3\\5" quality="high" scale="noborder" wmode="transparent" bgcolor="#000000" width="\\1" height="\\2" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash">
</embed></object>';

			# [video width= height= loop= ] and [/video] code..
			$patterns[] = "#\[video width=([0-9]+) height=([0-9]+)\]([\w]+?://[^ \?&=\"\n\r\t<]*?(\.(avi|mpg|mpeg|wmv)))\[/video\]#si";
			$replacements[] = '<embed src="\\3" width="\\1" height="\\2"></embed>';

			# HTML5 [video width= height=]
			$patterns[] = "#\[video width=([0-9]+) height=([0-9]+)\]([\w]+?://[^ \?&=\"\n\r\t<]*?(?:\.(mp4|webm)))\[/video\]#si";
			$replacements[] = '<video width="\\1" height="\\2" controls=""><source type="video/\\4" src="\\3"></source></video>';
			$patterns[] = "#\[video width=([0-9]+) height=([0-9]+)\]([\w]+?://[^ \?&=\"\n\r\t<]*?(?:\.ogv))\[/video\]#si";
			$replacements[] = '<video width="\\1" height="\\2" controls=""><source type="video/ogg" src="\\3"></source></video>';
			# HTML5 [video]
			$patterns[] = "#\[video[^\\]]*\]([\w]+?://[^ \?&=\"\n\r\t<]*?(?:\.(mp4|webm)))\[/video\]#si";
			$replacements[] = '<video controls=""><source type="video/\\2" src="\\1"></source></video>';
			$patterns[] = "#\[video[^\\]]*\]([\w]+?://[^ \?&=\"\n\r\t<]*?(?:\.ogv))\[/video\]#si";
			$replacements[] = '<video controls=""><source type="video/ogg" src="\\1"></source></video>';
		}

		$text = preg_replace($patterns, $replacements, $text);

		# Fix linebreaks on important items
		$text = preg_replace('#<br[^>]*>#si', '<br/>', $text);
		$text = preg_replace('#(</?ul>|</(ol|table|div)>)<br/>#si', '$1', $text);
		$text = preg_replace('#<br/><table#si', '<table', $text);

		# replace single & to &amp;
		$text = preg_replace('/&(?![a-z]{2,6};|#[0-9]{1,4};)/is', '&amp;', $text);

		# Remove our padding from the string..
		return $text;
	}

	public static function fromHTML($text)
	{
		$text = str_replace('<', ' <', $text);
		$text = preg_replace('/<ol type="([a1])">/si', '/\[list=\\1\]', $text);
		$text = preg_replace_callback('/<(\/?(?:b|u|i|hr))>/si', function($m){return '['.strtolower($m[1]).']';}, $text);
		$text = preg_replace('#<img(.*?)src="(.*?)\.(gif|png|jpg|jpeg)"(.*?)>#si', '[img]\\2.\\3[/img]', $text);
		$text = str_replace('<ul>', '[list]', $text);
		$text = str_replace('<li>', '[*]', $text);
		$text = str_replace('</ul>', '[/list]', $text);
		$text = str_replace('</ol>', '[/list]', $text);
		$text = strip_tags($text, '<br><p><strong>');
		return trim($text);
	}

	protected static function split_bbcodes($text)
	{
		$curr_pos = 0;
		$str_len = strlen($text);
		$text_parts = array();
		while ($curr_pos < $str_len) {
			# Find bbcode start tag, if not found end the loop.
			$curr_pos = strpos($text, '[', $curr_pos);
			if (false === $curr_pos) { break; }
			$st_end = strpos($text, ']', $curr_pos);
			if (false === $st_end) { break; }

			$code = substr($text, $curr_pos+1, $st_end-$curr_pos-1);
			$ctag = strtolower(preg_replace('/^([a-z]+)?.*$/Di', '\\1', $code));
			$code_len = strlen($ctag);

			$end_pos = empty($ctag) ? false : $st_end;
			$depth = 0;
			while ($end_pos) {
				# Find bbcode end tag, if not found end the loop.
				$end_pos = strpos($text, '[', $end_pos);
				if (false === $end_pos) { break; }
				$end = strpos($text, ']', $end_pos);
				if (false === $end) { break; }

				$code_end = strtolower(substr($text, $end_pos+1, $code_len+1));
				if ($code_end == "/{$ctag}") {
					if ($depth > 0) {
						--$depth;
						++$end_pos;
					} else {
						if ($curr_pos > 0) {
							$text_parts[] = substr($text, 0, $curr_pos);
						}
						if ('php' === $ctag) {
							$ctag = 'code';
							$code = 'code=php';
						}
						$text_parts[] = array(
							'tag'  => $ctag,
							'code' => $ctag.substr($code,strlen($ctag)),
							'subc' => 'code' == $ctag
								? substr($text, $st_end+1, $end_pos-$st_end-1)
								: static::split_bbcodes(substr($text, $st_end+1, $end_pos-$st_end-1)),
						);
						$text = substr($text, $end+1);
						$str_len = strlen($text);
						$curr_pos = 0;
						break;
					}
				} else {
					if (substr($code_end, 0, -1) == $ctag) {
						++$depth;
					}
					++$end_pos;
				}
			}
			++$curr_pos;
		}
		if ($str_len > 0) {
			$text_parts[] = $text;
		}
		return $text_parts;
	}

	# split the bbcodes and use nl2br on everything except [php]
	protected static function split_on_bbcodes($text, $allowed=0, $allow_html)
	{
		# Split all bbcodes.
		$text_parts = is_array($text) ? $text : static::split_bbcodes($text);

		# Merge all bbcodes and do special actions depending on the type of code.
		$text = '';
		while ($part = array_shift($text_parts)) {
			if (!is_array($part)) {
				$text .= ($allow_html ? $part : nl2br($part));
			} else
			switch ($part['tag'])
			{
			case 'code':
				# [CODE]
				if (preg_match('/=([a-z]+)(?:\s+start=([0-9]+))?/', $part['code'], $m)) {
					$text .= '<code data-type="'.$m[1].(empty($m[2])?'':'" style="counter-reset: line '.($m[2]-1)).'">' . $part['subc'] . '</code>';
				} else {
					$text .= static::$bb_codes['code_start'] . static::decode_code($part['subc']) . static::$bb_codes['code_end'];
				}
				break;

			case 'quote':
				# [QUOTE] and [QUOTE=""]
				if (preg_match('/quote="?(.*?)"?$/Dsi', $part['code'], $m)) {
					$text .= str_replace(array('\\1','$1'), $m[1], static::$bb_codes['quote_name']);
				} else {
					$text .= static::$bb_codes['quote'];
				}
				$text .= static::split_on_bbcodes($part['subc'], $allowed, $allow_html)
					. static::$bb_codes['quote_close'];
				break;

			case 'list':
				$end = '';
				$innerContent = '';
				foreach ($part['subc'] as $litems) {
					if (is_array($litems)) {
						$innerContent .= static::split_on_bbcodes(array($litems), $allowed, $allow_html);
					} else {
						$litems = explode('[*]', $litems);
						$innerContent .= array_shift($litems);
						foreach ($litems as $litem) {
							$innerContent .= $end . '<li>' . nl2br($litem);
							$end = '</li>';
						}
					}
				}
				$innerContent .= $end;
				if (preg_match('#^list=([ai1])#', $part['code'])) {
					$text .= "<ol type=\"{$part['code'][5]}\">" . $innerContent . '</ol>';
				} else {
					$text .= '<ul>' . $innerContent . '</ul>';
				}
				break;

			default:
				$text .= "[{$part['code']}]"
					. static::split_on_bbcodes($part['subc'], $allowed, $allow_html)
					. "[/{$part['tag']}]";
				break;
			}
		}
		return $text;
	}

	protected static function decode_code($text)
	{
		return preg_replace(
			array('#<#',  '#>#',  '#"#',	'#:#',   '#\[#',  '#\]#',  '#\(#',  '#\)#',  '#\{#',   '#\}#'),
			array('&lt;', '&gt;', '&quot;', '&#58;', '&#91;', '&#93;', '&#40;', '&#41;', '&#123;', '&#125;'),
			$text);
	}

}
