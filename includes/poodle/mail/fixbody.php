<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail;

/*
http://msdn.microsoft.com/en-us/library/office/aa338201%28v=office.12%29.aspx
http://www.campaignmonitor.com/css/
http://24ways.org/2009/rock-solid-html-emails/

<style> & <link> not supported by gmail
font-size 13px minimum (iphone/ipad)
don't use background image (outlook, android 2.3)
*/

class FixBody
{
	public
		$errors = array();

	private
		$parser,
		$data;

	private static
		$EMPTY_TAGS = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'),

		$outlook_css_c = array('color','font','font-family','font-style','font-variant','font-size','font-weight','text-decoration','background','background-color','text-align','vertical-align','letter-spacing','line-height','white-space','display','border','border-color','border-style','border-width','src','size','marks','page-break-before','page-break-after','page-break-inside','list-style','list-style-type','unicode-bidi','border-collapse'),
		$outlook_css_e = array('text-indent','margin','margin-left','margin-right','margin-top','margin-bottom'),
		$outlook_css_f = array('width','height','padding','padding-left','padding-right','padding-top','padding-bottom','border-left','border-right','border-top','border-bottom','border-left-color','border-left-width','border-left-style','border-right-color','border-right-width','border-right-style','border-top-color','border-top-width','border-top-style','border-bottom-color','border-bottom-width','border-bottom-style'),
		$outlook_html = array(
			'a'          => array('href','hreflang','name','rel','rev','target','shape','style','type','urn'),
			'abbr'       => array('style'),
			'acronym'    => array('style'),
			'address'    => array('style','disabled'),
			'area'       => array('coords','href','nohref','shape','style','target'),
			'b'          => array('style'),
			'base'       => array('href','target'),
			'basefont'   => array('color','face','size','style'),
			'big'        => array('style'),
			'blockquote' => array('cite','style'),
			'body'       => array('alink','background','bgcolor','bgproperties','link','nowrap','text','vlink'), // css only through <style>  => $outlook_css_c
			'br'         => array('clear','style'),
			'caption'    => array('align','style','valign'),
			'center'     => array('style'),
			'cite'       => array('style'),
			'code'       => array('style'),
			'col'        => array('align','span','style','valign','width'),
			'colgroup'   => array('align','bgcolor','span','style','valign','width'),
			'comment'    => array('data','style'),
			'dd'         => array('style'),
			'del'        => array('cite','datetime','style'),
			'dfn'        => array('style'),
			'dir'        => array('style','type'),
			'div'        => array('align','nowrap','style'),
			'dl'         => array('compact','style'),
			'dt'         => array('style','nowrap'),
			'em'         => array('style'),
			'fieldset'   => array('align','style'),
			'font'       => array('color','face','size','style'),
			'h1'         => array('align','style'),
			'h2'         => array('align','style'),
			'h3'         => array('align','style'),
			'h4'         => array('align','style'),
			'h5'         => array('align','style'),
			'h6'         => array('align','style'),
			'head'       => array('lang'),
			'hr'         => array('align','color','size','style','width'),
			'html'       => array('lang'),
			'i'          => array('style'),
			'img'        => array('align','alt','border','dynsrc','height','hspace','ismap','longdesc','lowsrc','name','src','style','usemap','vspace','width'),
			'ins'        => array('cite','datetime','style'),
			'kbd'        => array('style'),
			'label'      => array('for','style'),
			'legend'     => array('align','style'),
			'li'         => array('style','type','value'),
			'map'        => array('name','style'),
			'meta'       => array('http-equiv','content'),
			'ol'         => array('compact','style','type'),
			'p'          => array('align','style'),
			'pre'        => array('style'),
			's'          => array('style'),
			'samp'       => array('style'),
			'small'      => array('style'),
			'span'       => array('disabled','style'),
			'strike'     => array('style'),
			'strong'     => array('style'),
			'sub'        => array('style'),
			'sup'        => array('style'),
			'table'      => array('align','bgcolor','border','bordercolor','cellpadding','cellspacing','cols','frame','height','rules','style','summary','width'),
			'tbody'      => array('align','bgcolor','ch','choff','cols','style','valign'),
			'td'         => array('abbr','align','axis','bgcolor','bordercolor','ch','choff','colspan','datafld','headers','height','nowrap','scope','style','width','valign'),
			'tfoot'      => array('bgcolor','ch','choff','cols','style','valign'),
			'th'         => array('abbr','axis','bgcolor','bordercolor','ch','choff','colspan','datafld','headers','height','nowrap','scope','style','width','valign'),
			'thead'      => array('bgcolor','ch','choff','cols','style','valign'),
			'tfoot'      => array('bgcolor','ch','choff','cols','style','valign'),
			'title'      => array('style'),
			'tr'         => array('align','bgcolor','bordercolor','height','style','width','valign'),
			'tt'         => array('style'),
			'u'          => array('style'),
			'ul'         => array('style','type'),
			'var'        => array('style'),
		);

	function __construct() { }

	function __destruct()  { $this->free(); }

	protected function create()
	{
		if ($this->parser) { return; }
		// Create and initialize parser
		$this->parser = xml_parser_create('UTF-8');
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, false);
		xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_object($this->parser, $this);
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

	public function free()
	{
		if ($this->parser) {
			xml_parser_free($this->parser);
			$this->parser = null;
		}
	}

	protected function log_error()
	{
		$code = xml_get_error_code($this->parser);
		$this->errors[] = array(
			'code'    => $code,
			'message' => xml_error_string($code),
			'line'    => xml_get_current_line_number($this->parser),
		);
	}

	protected static function HTML2XML($data)
	{
		// DOMDocument::loadHTML encoding fails when it doesn't detect UTF-8
//		$data = preg_replace('/(<head[^>]*>)/', '$1<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>',$data);
		$data = '<?xml encoding="UTF-8">' . $data;

		$dom = new \DOMDocument();
		$dom->formatOutput = false;
		$dom->preserveWhiteSpace = false;
		$dom->strictErrorChecking = false;
		libxml_use_internal_errors(true); // Disable errors due to HTML5 not being supported
		if ($dom->loadHTML($data)) {
			$xpath = new \DOMXPath($dom);
			$nodes = $xpath->query('//link');
			foreach ($nodes as $node) { $node->parentNode->removeChild($node); }
			$nodes = $xpath->query('//style');
			foreach ($nodes as $node) { $node->parentNode->removeChild($node); }
			$data = $dom->saveXML();
		} else {
			$data = preg_replace('#<link[^>]*?>#s','',$data);
			$data = preg_replace('#<style.*?</style>#s','',$data);
			// Try to fix empty tags
			$data = preg_replace('#<('.implode('|',self::$EMPTY_TAGS).')(\\s[^>]*)?>#is','<$1$2/>',$data);
			$data = str_replace('//>','/>',$data);
		}
		libxml_clear_errors();
		return $data;
	}

	public function HTML($data)
	{
		$this->create();
		xml_set_character_data_handler($this->parser, 'html_character_data');
		xml_set_element_handler($this->parser, 'html_node_start', 'html_node_end');
		$this->errors = array();

		$data = self::HTML2XML($data);

		$data = preg_replace('#<(\\?xml|\\!DOCTYPE)[^>]*>\\r?\\n?#s','',$data);
		$this->data = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n";
		if (!xml_parse($this->parser, $data, true)) {
			$this->log_error();
		}
		$this->free();
		if (empty($this->errors)) {
			$data = $this->data;
			$this->data = '';
			$data = preg_replace('#\\R+#', "\n", $data);
			$data = preg_replace('#>[ \\t]+<#','> <',$data);
			$data = preg_replace('#\\n[ \\t]+#',"\n",$data);
			// Empty TD's are not rendered in Thunderbird
			$data = preg_replace('#(<td[^>]*>)(</td>)#','$1<br>$2',$data);
			return preg_replace('#\\n+#',"\n",$data);
		}
		return false;
	}

	// XML Parser: Node value
	protected function html_character_data($parser, $data)
	{
		$this->data .= htmlspecialchars($data);
	}

	// XML Parser: Node start
	protected function html_node_start($parser, $name, $attribs)
	{
		$name = strtolower($name);

		// Get rid of image gap, display:block is also a possibility
		if ('img' === $name) {
			if (!isset($attribs['style'])) {
				$attribs['style'] = 'vertical-align:top;';
			} else {
				$attribs['style'] = 'vertical-align:top;'.$attribs['style'];
			}
		}
		// Outlook.com has no font-weight:bold on H1-6
		else if (preg_match('/h[1-6]/',$name)) {
			if (!isset($attribs['style'])) {
				$attribs['style'] = 'font-weight:bold;';
			} else {
				$attribs['style'] = 'font-weight:bold;'.$attribs['style'];
			}
		}

		// Handle CSS
		if (isset($attribs['style']) && isset(self::$outlook_html[$name]) && in_array('style', self::$outlook_html[$name]))
		{
			$props = self::$outlook_css_c;
			if ('span' !== $name) {
				$props = array_merge($props, self::$outlook_css_e);
				if ('div' !== $name && 'p' !== $name) {
					$props = array_merge($props, self::$outlook_css_f);
				}
			}
			if (preg_match_all('/(?:^|;|\\s)([a-z-]+)\\s*:([^:;]+)/', $attribs['style'], $m, PREG_SET_ORDER)) {
				$css = array();
				foreach ($m as $i => $v) {
					$v[2] = trim($v[2]);
					// CSS property allowed?
					if (in_array($v[1], $props)) {
						$css[$v[1]] = $v[1].':'.$v[2];
					}
					// CSS property to attribute
					switch ($v[1])
					{
					case 'background-color':
						$attribs['bgcolor'] = $v[2];
						break;
					case 'border':
					case 'border-width':
					case 'border-color':
						$attribs['border'] = (int)$v[2];
						if (preg_match('/(#[A-Z0-9]+)/i',$v[2],$c)) {
							$attribs['bordercolor'] = $c[1];
						}
						break;
					case 'border-spacing':
						$attribs['cellspacing'] = (int)$v[2];
						break;
					case 'color':
						$attribs['color'] = $v[2];
						break;
					case 'height':
					case 'width':
						$attribs[$v[1]] = (int)$v[2];
						break;
					case 'vertical-align':
						$attribs['valign'] = $v[2];
						break;
					// cellpadding="0"
					}
				}
				$attribs['style'] = implode(';',$css);
			} else {
				unset($attribs['style']);
			}
		}

		// Could enable this but backgrounds just don't work on outlook.com and kmail
		/*$body_style = '';
		if ('body' === $name) {
//			$body_style .= '-webkit-text-size-adjust:none;';
			// Background image fails in Outlook and Kmail
			// Background image works in Gmail and Thunderbird
			if (!empty($attribs['background'])) {
				$v = $attribs['background'];
				if (!strpos($v, '://')) {
					$v = 'http://' . self::host() . \Poodle\URI::resolve($v);
				}
				$body_style .= "background-image:url('{$v}');";
			}
			// Background color works in all tested email clients
			if (!empty($attribs['bgcolor'])) {
				$body_style .= "background-color:{$attribs['bgcolor']};";
			}
		}*/

		foreach ($attribs as $k => $v) {
			if (!isset(self::$outlook_html[$name]) || !in_array($k, self::$outlook_html[$name])) {
//				trigger_error("Attribute {$k} not allowed in {$name}");
				unset($attribs[$k]);
			} else {
				// Make URI's absolute
				if (('href'===$k || 'src'===$k || 'background'===$k) && !strpos($v, ':')) {
					$v = 'http://' . \Poodle\URI::host() . \Poodle\URI::resolve($v);
				}
				$attribs[$k] = $k.'="'.htmlspecialchars($v).'"';
			}
		}
		$attribs = $attribs ? ' '.implode(' ',$attribs) : '';

		//$this->data .= '<' . $name . $attribs . (in_array($name, self::$EMPTY_TAGS) ? '/' : '') . '>';
		$this->data .= "<{$name}{$attribs}>";

		// Could enable this but backgrounds just don't work on outlook.com and kmail
		//if ('body' === $name) { $this->data .= "<div style=\"{$body_style}\">"; }
	}

	// XML Parser: Node end
	protected function html_node_end($parser, $name)
	{
		$name = strtolower($name);
		if (in_array($name, self::$EMPTY_TAGS)) { return; }
		$this->data .= "</{$name}>";

		// Could enable this but backgrounds just don't work on outlook.com and kmail
		//if ('body' === $name) { $this->data .= "</div>"; }
	}

	public function HTMLToText($data)
	{
		$this->create();
		xml_set_character_data_handler($this->parser, 'text_character_data');
		xml_set_element_handler($this->parser, 'text_node_start', 'text_node_end');
		$this->errors = array();
		$this->data = '';
		$this->href = null;
		$this->numbers = array(
			'ol' => 0,
			'h1' => 0,
			'h2' => 0,
			'h3' => 0,
		);

		$data = self::HTML2XML($data);

		$data = preg_replace('#^.*<body[^>]*>(.*)</body.*$#Ds', '<body>$1</body>', $data);
		$data = preg_replace('/\\s+/su', ' ', $data);
//		'#<h[123][^>]*>(.*?)</h[123]>#ie' => "strtoupper(\"\n\n\\1\n\n\")",   // H1 - H3
//		'#<h[456][^>]*>(.*?)</h[456]>#ie' => "ucwords(\"\n\n\\1\n\n\")",      // H4 - H6
//		'#<(b|strong)[^>]*>(.*?)</\\1>#ie' => 'strtoupper("$2")', // <b> | <strong>
		if (!xml_parse($this->parser, $data, true)) {
			$this->log_error();
		}
		$this->free();
		if (empty($this->errors)) {
			$data = trim($this->data);
			$this->data = '';
			$data = preg_replace('/ +\\n/s', "\n", $data);
			$data = preg_replace('/\\n +/s', "\n", $data);
			$data = preg_replace('/ +/s', ' ', $data);
			$data = preg_replace('/\\n\\n+/s', "\n\n", $data);
			return $data;
		}
		return false;
	}

	// XML Parser: Node value
	protected function text_character_data($parser, $data)
	{
		$this->data .= trim($data).' ';
	}

	// XML Parser: Node start
	protected function text_node_start($parser, $name, $attribs)
	{
		$name = strtolower($name);

		if ('a' === $name && isset($attribs['href'])) {
			$this->href = $attribs['href'];
		}

		if (preg_match('/^(h[1-6]|p|ol|ul)$/',$name))
		{
			$this->data .= "\n\n";
			if ('h3' === $name) {
				$this->data .= $this->numbers['h1'].'.'.$this->numbers['h2'].'.'.(++$this->numbers['h3']).': ';
			}
			if ('h2' === $name) {
				$this->data .= $this->numbers['h1'].'.'.(++$this->numbers['h2']).': ';
			}
			if ('h1' === $name) {
				$this->data .= (++$this->numbers['h1']).': ';
			}
			if ('ol' === $name) {
				$this->numbers['ol'] = 1;
			}
		}
		else if (preg_match('/^(br|div|li)$/',$name))
		{
			$this->data .= "\n";
			if ('li' === $name)
			{
				if ($this->numbers['ol']) {
					$this->data .= "    ".($this->numbers['ol']++).". "; // or use \t
				} else {
					$this->data .= "    * "; // or use \t
				}
			}
		}
		else if ('hr' === $name) {
			$this->data .= "\n*----------------------------------------*\n";
		}
		else if ('legend' === $name) {
			$this->data .= '-- ';
		}
	}

	// XML Parser: Node end
	protected function text_node_end($parser, $name)
	{
		$name = strtolower($name);
		if ('a' === $name && $this->href) {
			$this->data .= ' '.$this->href.' ';
			$this->href = null;
		}
		else if ('h1' === $name || 'h2' === $name || 'h3' === $name) {
			$this->data .= "\n################################\n\n";
			if ('h2' === $name) { $this->numbers['h3'] = 0; }
			if ('h1' === $name) { $this->numbers['h2'] = $this->numbers['h3'] = 0; }
		}
		else if ('div'    === $name) { $this->data .= "\n"; }
		else if ('p'      === $name) { $this->data .= "\n\n"; }
		else if ('ul'     === $name) { $this->data .= "\n\n"; }
		else if ('ol'     === $name) { $this->data .= "\n\n"; $this->numbers['ol'] = 0; }
		else if ('legend' === $name) { $this->data .= " --\n"; }
	}

}
