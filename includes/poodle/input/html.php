<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Input;

class HTML
{

	private static
		$html_regex = array(
			// control regex, desired regex replacement
			'#<\\s*(/)?\\s*#' => '<$1',
			'#(</?[a-zA-Z0-1]+)#' => 'self::reCbTagLC', # don't match whole tag (</?[a-zA-Z]+)([^>]*>) or PHP adds slashes
			'#<!DOCTYPE[^>]*>#' => '',
			'#<head>.*?</head>#' => '',
			'#<title>.*?</title>#' => '',
			'#</?(html|head|title|body|link|meta)( [^>]*)?>#' => '',
			'#\\s+(xmlns="|class="apple|_moz_dirty=")[^"]*"#' => '', # WebKit/Gecko
			'#="[^"]*[<>&][^"]*"#i' => 'self::reAttributes',
			// MS Word
			'#<!--\\[(((?!-->).)*)\\]-->#s' => '',
			'#</?(xml|[ovwxp]:\\w+)[^>]*>#si' => '',
			'#mso-[^;]+;#si' => '',
			'#\\s+class="Mso[a-z]+"#si' => '',
			// Obsolete HTML
			'#(</?)font#' => '$1span',
			// IE
			// issue with this line, it's not 100% working
//			'#<([^>]+)>#e' => 'preg_replace(\'#(\s[a-z-]+)\s*=\s*([^"\\\'\s>]+)#i\', \'\\$1="\\$2"\', str_replace("\\\'","\'","<$1>"))',
			'#"[\t ]*([^"=\r\n><]+)[\t ]*"#' => '"$1"',
			'@(<[^>]+)\\s+(background-color)="(#[A-F0-9]+)"([^>]*>)@i' => 'self::reCbTagCSS',
			// Attributes to css
			'@(<[^>]+)\\s+(color)="(#[A-F0-9]+)"([^>]*>)@i' => 'self::reCbTagCSS',
			'@(<[^>]+)\\s+(bgcolor)="(#[A-F0-9]+)"([^>]*>)@i' => 'self::reCbTagCSS',
			'#(<[^>]+)\\s+(valign)="(top|middle|bottom|baseline)"([^>]*>)#i' => 'self::reCbTagCSS',
			'#(<[^>]+)\\s+(width)="([0-9]+)"([^>]*>)#i' => 'self::reCbTagCSS',
			'#(<[^>]+)\\s+(height)="([0-9]+)"([^>]*>)#i' => 'self::reCbTagCSS',
			'#(<[^>]+)\\s+(border)="([0-9]+)"([^>]*>)#i' => 'self::reCbTagCSS',
			'@(<[^>]+)\\s+(bordercolor)="(#[A-F0-9]+)"([^>]*>)@i' => 'self::reCbTagCSS',
			'#(<[^>]+)\\s+(cellspacing)="([0-9]+)"([^>]*>)#i' => 'self::reCbTagCSS',
			// HTML 5 compatibility
			'#(</?)acronym#' => '$1abbr',
			// deprecated/obsolete in XHTML & HTML 5:
			'#</?(basefont|big|center|dir|frame|frameset|noframes|s|strike|tt|u|xmp)[^a-z>]+>#' => '',
			// cleanup:
			'#style="\\s*"#i' => '',
			// self-contained elements to X(HT)ML
			'#<(area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)([^>]*)/?>#i' => '<$1$2/>',
			'#[\\s\\/]*\\/>#' => '/>',
		),

		$allowed_tags = 'a,abbr,address,article,aside,audio,b,bdo,blockquote,br,canvas,caption,cite,code,col,colgroup,command,dd,del,details,dfn,div,dl,dt,em,fieldset,figcaption,figure,footer,h1,h2,h3,h4,h5,h6,header,hgroup,hr,i,img,ins,kbd,legend,li,main,mark,menu,meter,nav,ol,p,param,pre,progress,q,samp,section,small,source,span,strong,sub,summary,sup,table,tbody,td,tfoot,th,thead,time,title,tr,ul,var,video',
//		area,base,body,button,datalist,embed,form,head,html,iframe,input,keygen,label,link,map,meta,noscript,object,optgroup,option,output,rp,rt,ruby,script,select,style,textarea
		$no_end_tags = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');

	public static function fix($value, $def='')
	{
		if (!is_string($value)) { return $value; }

		$value = trim(\Poodle\Input::fixSpaces(
			self::xml_entity_decode($value)
		));

		if (!strlen($value)) { return $def; }

		# repair XHTML stage 1
		foreach (self::$html_regex as $regex => $regex_cb) {
			if (is_callable($regex_cb)) {
				$value = preg_replace_callback($regex, $regex_cb, $value);
			} else {
				$value = preg_replace($regex, $regex_cb, $value);
			}
		}

		if (!defined('ADMIN_PAGES')) {
			$HTML = new static();
			$value = $HTML->parse($value);
		}

		# repair XHTML stage 2, buggy: puts line-endings where we don't want them
//		if (function_exists('tidy_repair_string')) { $value = tidy_repair_string($value, array('output-xhtml'=>1,'show-body-only'=>1,'newline'=>'LF'), \Poodle::CHARSET); }

		# repair uri's
		$c = 0;
		do {
			$value = preg_replace('#(<[^>]+(href|src|action)=["\'][^"\']+)/[^\'"/]+/\\.\\./#', '$1/', $value, -1, $c);
		} while ($c);
		// TODO: use current http scheme?
		$value = preg_replace('#(<[^>]+(href|src|action))="([a-z][a-z0-9\\+\\.\\-]+://'.\Poodle\URI::host().')?'.\Poodle::$URI_BASE.'/#', '$1="/', $value);

		return \Poodle\Input::fixEOL(trim($value));
	}

	protected
		$html,
		$html_tags,
		$parser;

	function __construct()
	{
		$this->html_tags = explode(',',self::$allowed_tags);
	}

	function __destruct()
	{
		if ($this->parser) {
			xml_parser_free($this->parser);
			$this->parser = null;
		}
	}

	public function parse($html)
	{
		if (!$this->parser) {
			// Create and initialize parser
			$this->parser = xml_parser_create('UTF-8');
			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
			xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, false);
			xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');

			xml_set_object($this->parser, $this);
			xml_set_character_data_handler($this->parser, 'xml_character_data');
			xml_set_element_handler($this->parser, 'xml_node_start', 'xml_node_end');
/*
			xml_set_default_handler($this->parser, 'xml_default_handler');
			xml_set_end_namespace_decl_handler($this->parser, 'xml_end_namespace_decl');
			xml_set_processing_instruction_handler($this->parser, 'xml_processing_instruction');
			xml_set_start_namespace_decl_handler($this->parser, 'xml_start_namespace_decl');
			xml_set_unparsed_entity_decl_handler($this->parser, 'xml_unparsed_entity_decl');
			# xml_set_external_entity_ref_handler — Set up external entity reference handler
			# xml_set_notation_decl_handler — Set up notation declaration handler
*/
		}

		$this->html = '';
		if (xml_parse($this->parser, '<html>'.$html.'</html>', true)) {
			$html = $this->html;
			$this->html = '';
			return $html;
		}
		trigger_error(xml_error_string(xml_get_error_code($this->parser)) . ' on line ' . xml_get_current_line_number($this->parser));
//		throw new \Exception(xml_error_string(xml_get_error_code($this->parser)));
		return false;
	}

	// XML Parser: Node value
	protected function xml_character_data($parser, $data)
	{
		$this->html .= htmlspecialchars($data, ENT_NOQUOTES);
	}

	// XML Parser: Node start
	protected function xml_node_start($parser, $name, $attribs)
	{
		$name = strtolower($name);
		if (!in_array($name, $this->html_tags, true)) {
			return;
		}

		$this->html .= '<'.$name;

		$new_attribs = array();
		foreach ($attribs as $k => $v) {
			if ('on'!==substr($k,0,2) && false === strpos($k,':') && (''!=$v || !in_array($k,array('id','title','target')))) {
				$this->html .= ' '.$k.'="'.htmlspecialchars($v).'"';
			}
		}

		$this->html .= (in_array($name, self::$no_end_tags, true) ? '/>' : '>');
	}

	// XML Parser: Node end
	protected function xml_node_end($parser, $name)
	{
		$name = strtolower($name);
		if (in_array($name, self::$no_end_tags, true)
		 || !in_array($name, $this->html_tags, true))
		{
			return;
		}

		$this->html .= "</{$name}>";
	}

	public static function xml_entity_decode($string, $flags=ENT_NOQUOTES, $charset=\Poodle::CHARSET)
	{
		$flags |= ENT_HTML5;
		// replace numeric entities
		$string = preg_replace_callback('/&#x([0-9a-f]+);/i', function($m){return self::dec2utf8(hexdec($m[1]));}, $string);
		$string = preg_replace_callback('/&#([0-9]+);/', function($m){return self::dec2utf8($m[1]);}, $string);
		// replace literal entities
		$entities = get_html_translation_table(HTML_ENTITIES, $flags, $charset);
		if (!$entities) {
			throw new \Exception("get_html_translation_table(HTML_ENTITIES, {$flags}, {$charset}) is broken!");
		}
		unset($entities['&'],$entities['<'],$entities['>']);
		$entities['\''] = '&apos;';
		return strtr($string, array_flip($entities));
	}

	public static function dec2utf8($num)
	{
		if ($num <= 0x7F) {
			if (34 == $num) { return '&quot;'; }
			if (38 == $num) { return '&amp;'; }
			if (60 == $num) { return '&lt;'; }
			if (62 == $num) { return '&gt;'; }
			return chr($num);
		}
//		return mb_convert_encoding('&#x'.dechex($num).';', 'UTF-8', 'HTML-ENTITIES');
		if ($num <= 0x7FF) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
		if ($num <= 0xFFFF) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		if ($num <= 0x1FFFFF) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
		return '';
	}

	public static function utf8mb4ToEntities($str)
	{
		return preg_replace_callback(
			'#(\\xF0[\\x90-\\xBF][\\x80-\\xBF]{2}|[\\xF1-\\xF3][\\x80-\\xBF]{3}|\\xF4[\\x80-\\x8F][\\x80-\\xBF]{2})+#',
			function($m){return mb_convert_encoding($m[0],'HTML-ENTITIES','UTF-8');},
			$str);
	}

	private static function reAttributes($m)
	{
		return htmlspecialchars($m[0], ENT_NOQUOTES, \Poodle::CHARSET, false);
	}

	private static function reCbTagCSS($m)
	{
		$m[2] = strtolower($m[2]);
		$map = array(
			'bgcolor'=>'background-color',
			'border'=>'border-width',
			'bordercolor'=>'border-color',
			'cellspacing'=>'border-spacing',
			'valign'=>'vertical-align'
		);
		if (isset($map[$m[2]])) { $m[2] = $map[$m[2]]; }
		switch ($m[2])
		{
		case 'border-spacing':
		case 'border-width':
		case 'width':
		case 'height':
			$m[3] .= 'px';
			break;
		}
		$t = $m[1].$m[4];
		$v = "{$m[2]}:{$m[3]};";
		return strpos($t, 'style=') ? str_replace('style="','style="'.$v,$t) : str_replace('>',' style="'.$v.'">',$t);
	}

	private static function reCbTagLC($m) { return strtolower($m[1]); }

}
