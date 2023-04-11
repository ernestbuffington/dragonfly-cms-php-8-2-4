<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	wiki.zope.org/ZPT/TAL
*/

namespace Poodle\TPL;

class Parser
{
	public
		$data,
		$errors;

	private
		$parser,
		$level, // How deep the current rabbit hole goes
		$skip,  // Skip inner nodes
		$nodes,
		$character_data,
		$tal_conditions, // tal:condition level
		$xsl_foreach,    // xsl:for-each level
		$xsl_choose,

		$file = null,
		$line = 0,

		$DTD = null,
		$DTD_type = null,
		$old_dtd_type = null,
		$HTML = false;

	private static
		$form_elements = array('button','input','select','textarea'),
		$form_methods  = array('reset','submit'),
		$uri_attribs   = array('action','formaction','href','src','poster'),
		$table_struct  = array('thead','tbody','tfoot');

	const
		XMLNS = 'xmlns:tal="http://xml.zope.org/namespaces/tal"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:i18n="http://xml.zope.org/namespaces/i18n"';

	function __construct(\Poodle\TPL $TPL)
	{
		$this->TPL = $TPL;
		if ($TPL->DTD) {
			$this->load_dtd($TPL->DTD);
		}
		$this->free();
	}
	function __destruct()  { $this->free(); }

	public function getCurrentFilename()
	{
		return $this->file;
	}

	public function free($skip_error=false)
	{
		if ($this->parser) {
			if (!xml_parse($this->parser, '</tal>', true) && !$skip_error) {
				$this->log_error(
					xml_error_string(xml_get_error_code($this->parser)),
					xml_get_current_line_number($this->parser) - $this->line
//					,xml_get_current_column_number($this->parser)
				);
			}
			xml_parser_free($this->parser);
			$this->parser = null;
		}
		$this->line  = 0;
		$this->level = 0;
		$this->skip  = 0;
		$this->nodes = array();
		$this->tal_conditions = array();
		$this->xsl_foreach = array();
		$this->xsl_choose = array();
		$this->character_data = '';
	}

	public function load_dtd($type)
	{
		$DTD = array();
		include(__DIR__.'/dtd/'.$type.'.php');
		if ($DTD && is_array($DTD)) { $this->DTD = $DTD; }
		$this->DTD_type = $type;
		$this->HTML = stripos($this->doctype(),'DOCTYPE html');
	}

	public function doctype() { return ($this->DTD && isset($this->DTD['DOCTYPE']) ? $this->DTD['DOCTYPE']."\n" : null); }

	public function isXML()   { return ($this->DTD && !empty($this->DTD['XML'])); }

	public function isCaseInsensitive() { return ($this->DTD && !empty($this->DTD['CASE-INSENSITIVE'])); }

	public function parse_chunk($file, $data, $final=true)
	{
		if (!$data) {
			$this->file = $file;
			$data = file_get_contents($file);
		}
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
			xml_parse($this->parser, '<tal>', false);
		}
		$this->data   = '';
		$this->errors = array();
		$data = preg_replace('#<(\\?xml|\\!DOCTYPE)[^>]*>\\r?\\n?#s', '', $data);
		if (xml_parse($this->parser, $data, false)) {
			if ($final) { $this->free(); }
			else { $this->line = xml_get_current_line_number($this->parser); }
			$this->cleanupData();
		} else {
			$this->log_error(
				xml_error_string(xml_get_error_code($this->parser)),
				xml_get_current_line_number($this->parser) - $this->line
//				,xml_get_current_column_number($this->parser)
			);
			$this->free(true);
		}
		return empty($this->errors);
	}

	protected function cleanupData()
	{
		$this->data = ltrim(preg_replace('#\\R#u', "\n", $this->data));
		$this->data = preg_replace('#>[ \\t]+<#u','> <',$this->data);
		$this->data = preg_replace('#\\s*\\?><\\?php\\s*#u',' ',$this->data);
		$this->data = preg_replace('#\\?>(\\s*)<\\?php else#su','$1 else',$this->data);
	}

	protected function DTD_has_tag_attribute($tag, $attr)
	{
		return (!$this->DTD
			|| isset($this->DTD[$tag][$attr])
			|| isset($this->DTD['STANDARD_ATTRIBUTES'][$attr])
			// HTML5 data-*, WAI-ARIA or 'on*' HTML Event-handler attributes
			|| preg_match('#^(data-|aria-|on[a-z]+$)#D',$attr)
		);
	}

	protected function DTD_is_empty_tag($name) { return ($this->DTD && isset($this->DTD['EMPTY-TAGS']) && in_array($name, $this->DTD['EMPTY-TAGS'])); }

	protected function log_error($msg, $line, $column = 0)
	{
		$i = count($this->nodes)-1;
		$this->errors[] = array(
			'message'=> $msg,
			'file'   => $this->file,
			'line'   => $line,
//			'column' => $column,
			'node'   => isset($this->nodes[$i]) ? $this->nodes[$i] : ''
		);
	}

	protected function error($parser, $msg, $type=E_USER_WARNING)
	{
		\Poodle\Debugger::error($type, $msg, $this->file,
			is_resource($parser)
				? xml_get_current_line_number($parser) - $this->line
				: (($node = $parser->expand()) ? $node->getLineNo() : 0)
		);
	}

	// XML Parser: Node value
	protected function xml_character_data($parser, $data)
	{
		$this->character_data .= $data;
	}

	protected function push_character_data($data=null, $encode=true)
	{
		if (isset($data)) {
			$this->data .= $data;
//		} else if ($encode && false !== strpbrk($this->character_data,'&<>')) { slower ???
		} else if ($encode && (false !== strpos($this->character_data,'&')
		 || false !== strpos($this->character_data,'<')
		 || false !== strpos($this->character_data,'>'))
		) {
			$this->data .= '<?php static::echo_data('.self::data2Str($this->character_data).');?>';
		} else {
			$this->data .= $this->character_data;
		}
		$this->character_data = '';
	}

	// XML Parser: Node start
	protected function xml_node_start($parser, $name, $attribs)
	{
		$level = $this->level++;
		if ('tal' === $name) { return; }
		if ($this->skip && $level > $this->skip) { return; }
		if ($this->isCaseInsensitive()) {
			$name = strtolower($name);
		}

		/** XSLT */
		if ($this->xslt_node_start($name, $attribs, $level)) { return; }

		if ($this->HTML && 'svg' === $name) {
			$this->old_dtd_type = $this->DTD_type;
			$this->load_dtd('svg');
		}

		if ($this->HTML && 'tr' === $name) {
			$c = count($this->nodes);
			if (!in_array($this->nodes[$c]['name'],self::$table_struct)
			 && (!isset($this->nodes[$c-1]) || !in_array($this->nodes[$c-1]['name'],self::$table_struct)))
			{
				$this->error($parser, "Table TR started outside thead, tbody or tfoot");
			}
		}

		$this->push_character_data(/*isset($attribs['tal:omit-tag'])?'':null*/);

		$code = array();
		$node = array(
			'name'      => $name,
			'php'       => array(),
			'value'     => null,
			'omit-tag'  => false,
			'translate' => false
		);

		/**
		 * TAL
		 * Attribute execution order:
		 *     define
		 *     condition
		 *     repeat
		 *     content or replace
		 *     attributes
		 *     omit-tag
		 */

		unset($attribs['xmlns:i18n']);
		unset($attribs['xmlns:metal']);
		unset($attribs['xmlns:tal']);
		unset($attribs['xmlns:xsl']);

		if (isset($attribs['tal:include'])) {
			if (isset($attribs['tal:content'])) {
				$this->error($parser, "'tal:include' and 'tal:content' attributes may not appear together.");
				unset($attribs['tal:content']);
			}
			if (isset($attribs['tal:replace'])) {
				$this->error($parser, "'tal:include' and 'tal:replace' attributes may not appear together.");
				unset($attribs['tal:replace']);
			}
		}
		if (isset($attribs['tal:content']) && isset($attribs['tal:replace'])) {
			$this->error($parser, "'tal:content' and 'tal:replace' attributes may not appear together.");
			unset($attribs['tal:replace']);
		}

		if (isset($attribs['tal:define'])
		 && preg_match_all('#([a-z][0-9a-z_]+)\\s+([^;]*)(?:$|;)#D', $attribs['tal:define'], $m, PREG_SET_ORDER))
		{
			foreach ($m as $def) {
				$code[] = '$ctx->'.$def[1].' = ('.self::parse_expression($def[2]).');';
			}
		}

		if (isset($attribs['tal:condition-else'])) {
			if (isset($this->tal_conditions[$level])) {
				if (strlen(trim($attribs['tal:condition-else']))) {
					$code[] = 'else if ('.self::parse_expression($attribs['tal:condition-else']).') {';
				} else {
					$code[] = 'else {';
					unset($this->tal_conditions[$level]);
				}
				$node['php'][] = '}';
			}
		} else
		if (isset($attribs['tal:condition'])) {
//			if (!empty($this->tal_conditions[$level])) { $code[] = ' else'; }
			$code[] = 'if ('.self::parse_expression($attribs['tal:condition']).') {';
			$node['php'][] = '}';
			$this->tal_conditions[$level] = true;
		} else {
			unset($this->tal_conditions[$level]);
		}

		if (isset($attribs['tal:repeat'])) {
			// (dest, value) else (dest)
			$code[] = preg_match('#^\\s*([a-z0-9:\\-_]+)\\s+(.*)$#si', $attribs['tal:repeat'], $m)
				? self::create_foreach($m[1], trim($m[2]))
				: self::create_foreach(trim($attribs['tal:repeat']), null);
			$node['php'][] = '} $ctx = $ctx->parent;';
		}

		/**
		 * Replace content including start and end tags
		 * else continue processing
		 */

		$tag = '';
		if (isset($attribs['tal:replace'])) {
			$this->skip = $level;
			$node['omit-tag'] = true;
			$node['value'] = self::getValue($attribs['tal:replace'], $attribs);
		} else {
			/**
			 * Replace inner content
			 */

			if (isset($attribs['tal:content'])) {
				$node['value'] = self::getValue($attribs['tal:content'], $attribs);
				$this->skip = $level;
			}
			else if (isset($attribs['tal:include'])) {
				$node['value'] = '<?php echo $this->toString(\''.$attribs['tal:include'].'\'); ?>';
				$this->skip = $level;
			}

			/**
			 * tal:attributes
			 */
			$parse_attribs = array();
			if (isset($attribs['tal:attributes'])
			 && preg_match_all('#([a-z\\-_]+)\\s+((?:\'[^\']*\'|[^\';]*)*)(?:$|;)#D', $attribs['tal:attributes'], $m, PREG_SET_ORDER))
			{
				foreach ($m as $attr) {
					$k = $attr[1];
					$v = $attr[2];
					$parse_attribs[$k] = self::parse_expression($v, isset($attribs[$k])?self::data2Str($attribs[$k]):null);
					if (in_array($k,self::$uri_attribs) && false === stripos($parse_attribs[$k], 'static::resolveURI')) {
						$parse_attribs[$k] = 'static::resolveURI('.$parse_attribs[$k].')';
					}
				}
			}
			unset($attribs['tal:attributes']);

			/**
			 * i18n:attributes
			 */
			if (isset($attribs['i18n:attributes'])
			 && preg_match_all('#([a-z\\-]+)(\\s+[^;]*)?(?:$|;)#D', $attribs['i18n:attributes'], $m, PREG_SET_ORDER))
			{
				foreach ($m as $attr) {
					$k = $attr[1];
					$attr[2] = isset($attr[2]) ? trim($attr[2]) : '';
					if (empty($attr[2])) {
						if (isset($parse_attribs[$k])) {
							$parse_attribs[$k] = '$this->L10N->get('.$parse_attribs[$k].')';
							continue;
						} else if (isset($attribs[$k])) {
							$attr[2] = self::data2Str($attribs[$k]);
						} else {
							$this->error($parser, "Undefined attribute '{$k}' in node '{$name}'", E_USER_NOTICE);
							continue;
						}
					}
					$parse_attribs[$k] = self::l10n($attr[2], true);
				}
			}
			unset($attribs['i18n:attributes']);

			// Not handled
			unset($attribs['tal:on-error']);

			/**
			 * Check for required attributes
			 */

			if (self::DTD_has_tag_attribute('STANDARD_ATTRIBUTES', 'xml:lang')) {
				if (isset($attribs['lang']) && !isset($attribs['xml:lang'])) {
					$attribs['xml:lang'] = $attribs['lang'];
					unset($attribs['lang']);
				}
			} else if (isset($attribs['xml:lang']) && !isset($attribs['lang'])) {
				$attribs['lang'] = $attribs['xml:lang'];
				unset($attribs['xml:lang']);
			}

			if ($this->DTD && !empty($this->DTD[$name])) {
				foreach ($this->DTD[$name] as $attr => $required) {
					if (0 !== $required && !isset($attribs[$attr])) {
						if (!isset($parse_attribs[$attr])) {
							$this->error($parser, "Adding missing required attribute '{$attr}' to node '{$name}'", E_USER_NOTICE);
						}
						$attribs[$attr] = $required;
					}
				}
				if ($this->HTML) {
					// Set type="submit" because this is default for all browsers, except Internet Explorer
					if ('button' === $name && !isset($attribs['type'])) {
						$attribs['type'] = 'submit';
					}
					// Only firefox supports td/th colspan="0" and rowspan="0", so set a really big value
					if ('th' === $name || 'td' === $name) {
						if (isset($attribs['colspan']) && '0' === $attribs['colspan']) {
							$attribs['colspan'] = 99;
						}
						if (isset($attribs['rowspan']) && '0' === $attribs['rowspan']) {
							$attribs['rowspan'] = 999;
						}
					}
					if ('form' === $name && empty($attribs['action']) && empty($parse_attribs['action'])) {
						$parse_attribs['action'] = '$_SERVER[\'REQUEST_URI\']';
					}
				}
			}

			/**
			 * Make static attributes list
			 */

			$tag_attribs = $attrs = array();
			foreach ($attribs as $attr => $value) {
				if (0 !== strpos($attr,'tal:') && 0 !== strpos($attr,'i18n:')) {
					if ($this->HTML) {
						// If a form control (such as a submit button) has a name or id of submit it will mask the form's submit method.
						if (('name' === $attr || 'id' === $attr) && in_array($value, self::$form_methods) && in_array($name, self::$form_elements)) {
							$this->error($parser, "{$name}'s attribute '{$attr}' value '{$value}' changed to 'form-{$value}' or it will mask the form's method");
							$value = 'form-'.$value;
						}
						if ($value && in_array($attr,self::$uri_attribs) && preg_match('#^(cdn://|\\?|([a-z]*)/)#',$value)) {
							$value = \Poodle\URI::resolve($value);
						}
					}
					if (!strpos($attr,':') && !self::DTD_has_tag_attribute($name, $attr)) {
						$this->error($parser, "Unknown attribute '{$attr}' in node '{$name}'", E_USER_NOTICE);
					}
					$attrs[] = var_export($attr, true).'=>'.var_export($value, true);
					if (!$parse_attribs) {
						$tag_attribs[$attr] = $value;
					}
				}
			}
			$code[] = '$ctx->attrs = array('.implode(',',$attrs).');';
			unset($attrs);

			/**
			 * http://wiki.zope.org/zope3/ZPTInternationalizationSupport
			 */
			if (isset($attribs['i18n:domain'])) { trigger_error('i18n:domain not supported'); }
			if (isset($attribs['i18n:source'])) { trigger_error('i18n:source not supported'); }
			if (isset($attribs['i18n:target'])) { trigger_error('i18n:target not supported'); }
			if (isset($attribs['i18n:name']))   { trigger_error('i18n:name not supported'); }
			if (isset($attribs['i18n:data']))   { trigger_error('i18n:data not supported'); }
			if (isset($attribs['i18n:translate'])) {
				$attribs['i18n:translate'] = trim($attribs['i18n:translate']);
				if (strlen($attribs['i18n:translate'])) {
					$node['value'] = '<?php static::echo_data($this->L10N->dbget('.self::data2Str($attribs['i18n:translate']).')); ?>';
				} else {
					$node['translate'] = true;
				}
			}

			$echo_attribs = '';
			if ($parse_attribs) {
				foreach ($parse_attribs as $attr => $value) {
					if (!strpos($attr,':') && !self::DTD_has_tag_attribute($name, $attr)) {
						$this->error($parser, "Unknown attribute '{$attr}' in node '{$name}'", E_USER_NOTICE);
					}
					$parse_attribs[$attr] = "'{$attr}'=>{$value}";
				}
				$echo_attribs = '<?php echo static::parseAttributes(\''.$name.'\', array_merge($ctx->attrs, array('.implode(',', $parse_attribs).')));?>';
			} else if ($tag_attribs) {
				$echo_attribs = $this->TPL->parseAttributes($name, $tag_attribs);
			}

			unset($tag_attribs);
			unset($parse_attribs);

			/**
			 * Recreate a proper tag
			 */

			$tag = '<'.$name . $echo_attribs;
			if (self::DTD_is_empty_tag($name)) {
				$tag .= '/';
				$node['omit-tag'] = true;
			}
			$tag .= '>';
			if ('script' === $name && $this->DTD && isset($this->DTD['CDATA'])) {
				$tag .= $this->DTD['CDATA'][0].' ';
			}

			/**
			 * omit-tag option?
			 */

			if (isset($attribs['tal:omit-tag'])) {
				$exp = trim($attribs['tal:omit-tag']);
				if (strlen($exp)) {
					$exp = self::parse_expression($exp);
					$code[] = 'if (!('.$exp.')) { ?>'.$tag.'<?php }';
					$node['php'][] = 'if (!('.$exp.')) { ?></'.$name.'><?php }';
				}
				$tag = '';
				$node['omit-tag'] = true;
			} else if ($this->DTD && !isset($this->DTD[$name])) {
				$this->error($parser, "Unknown node '{$name}'");
			}
		}

		if ($code) {
			$this->data .= '<?php '.implode(' ', $code).' ?>';
		}
		$this->data .= $tag;
		$this->nodes[$level] = $node;
	}

	// XML Parser: Node end
	protected function xml_node_end($parser, $name)
	{
		$level = --$this->level;
		if ('tal' === $name) {
			$this->push_character_data();
			return;
		}
		if ($this->skip) {
			if ($level > $this->skip) { return; }
			$this->skip = 0;
		}
		if ($this->isCaseInsensitive()) {
			$name = strtolower($name);
		}

		/** XSLT */
		if ($this->xslt_node_end($name, $level)) { return; }

		/**
		 * Default
		 */

		if ($this->old_dtd_type && 'svg' === $name) {
			$this->load_dtd($this->old_dtd_type);
		}

		$node = array_pop($this->nodes);

		if ($node['translate']) {
			$node['value'] = '<?php static::echo_data('.self::l10n($this->character_data).'); ?>';
		}
		$this->push_character_data($node['value'], 'script' !== $name);

		if (!$node['omit-tag']) {
			if ('script' === $name && $this->DTD && isset($this->DTD['CDATA'])) { $this->data .= ' '.$this->DTD['CDATA'][1]; }
			$this->data .= "</{$name}>";
		}
		if ($node['php']) {
			$this->data .= '<?php '.implode(' ', array_reverse($node['php'])).' ?>';
		}
	}

	protected function xslt_node_start($name, $attribs, $level)
	{
		if (0 !== strpos($name, 'xsl:')) { return false; }
		if ('choose' !== substr($name,4)) {
			$this->push_character_data();
		}
		switch (substr($name,4))
		{
		case 'choose':
			$this->xsl_choose[$this->level] = true;
			break;

		case 'for-each':
			$var = isset($attribs['as']) ? $attribs['as'] : preg_replace('#^.*/([^/]+)$#D', '$1', $attribs['select']);
			$this->push_character_data();
			$this->data .= '<?php ' . self::create_foreach($var, $attribs['select']) . ' ?>';
			$this->xsl_foreach[] = $var;
			break;

		case 'when': if (empty($this->xsl_choose[$level])) { break; }
		case 'if':
			$exp = $attribs['test'];
			$var = ($i = count($this->xsl_foreach)) ? $this->xsl_foreach[$i-1].'->' : '';
			$exp = preg_replace('#([^!=><])=#', '$1==', $exp);
			$exp = preg_replace('#(^|[^a-z>$])([a-z][a-z0-9_]+)(?=[^a-z0-9_]|$)#Di', '$1$ctx->'.$var.'$2', html_entity_decode($exp));
			$exp = str_replace($var.'position()', 'repeat->'.$var.'index', $exp);
			$exp = str_replace($var.'last()', 'repeat->'.$var.'last', $exp);
			$this->data .= '<?php if ('.$exp.') { ?>';
			break;
		case 'otherwise':
			if (!empty($this->xsl_choose[$level])) {
				$this->data .= '<?php else { ?>';
			}
			break;

		case 'value-of':
			$this->data .= '<?php static::echo_data('.self::parse_expression((($i = count($this->xsl_foreach)) ? $this->xsl_foreach[$i-1] . '/' : '') . $attribs['select']).'); ?>';
			break;
		}
		return true;
	}

	protected function xslt_node_end($name, $level)
	{
		if (0 !== strpos($name, 'xsl:')) { return false; }
		switch (substr($name,4))
		{
		case 'for-each':
			array_pop($this->xsl_foreach);
			$this->push_character_data();
			$this->data .= '<?php } $ctx = $ctx->parent; ?>';
			break;

		case 'choose': unset($this->xsl_choose[$level+1]); break;
		case 'otherwise':
		case 'when': if (empty($this->xsl_choose[$level])) { break; }
		case 'if':
			$this->push_character_data();
			$this->data .= '<?php } ?>';
			break;

		case 'text': $this->push_character_data(); break;
		}
		return true;
	}

	protected static function create_foreach($var, $exp)
	{
		return '$ctx = $ctx->new_context_repeat(\''.$var.'\', '.self::parse_expression($exp).'); foreach ($ctx->repeat->'.$var.' as $ctx->'.$var.') {';
	}

	protected static function parse_expression($exp, $def=null)
	{
		return Tales::translate_expression($exp, $def);
	}

	protected static function getValue($expr, &$attribs)
	{
		if (!strlen($expr)) return '';

		preg_match('/^(?:(text|structure|bbcode)\\s+)?(.+)/', $expr, $match);

		if (isset($attribs['i18n:translate'])) {
			unset($attribs['i18n:translate']);
			$expr = self::l10n($match[2], true, true);
		} else {
			$expr = self::parse_expression($match[2]);
		}

		if ('structure' === $match[1]) {
			return '<?php echo '.$expr.'; ?>';
		}
		if ('bbcode' === $match[1]) {
			return '<?php echo \Dragonfly\BBCode::decode('.$expr.', 1); ?>';
		}
		return '<?php static::echo_data('.$expr.');?>';
	}

	protected static function l10n($str, $parse=false, $dbget=false)
	{
		$str = trim($str);
		# Look for tales modifier (not:, path:, string:, php:, etc...)
		if ($parse) {
			$str = self::parse_expression($str);
		} else {
			$str = Tales::string($str);
		}
		return '$this->L10N->'.($dbget?'dbget':'get')."({$str})";
	}

	protected static function data2Str($data)
	{
		return '\''.addcslashes($data,"'\\").'\'';
	}

	public function parse_xml($file, $data)
	{
		if (!$data) {
			$this->file = $file;
			$data = file_get_contents($file);
		}
		$parser = new \XMLReader;
		try {
			libxml_clear_errors();
			libxml_use_internal_errors(true);
			libxml_disable_entity_loader(true);
			$data = preg_replace('#<(\\?xml|\\!DOCTYPE)[^>]*>#s', '', $data);
			if (!$parser->xml('<tal '.static::XMLNS.'>'.$data.'</tal>', null, LIBXML_COMPACT)) {
				$this->setXMLErrors();
				return false;
			}
			while ($parser->read()) {
				switch ($parser->nodeType)
				{
				case \XMLReader::ELEMENT: // Start element
					$attributes = array();
					if ($parser->hasAttributes) {
						while ($parser->moveToNextAttribute()) {
							$attributes[$parser->name] = $parser->value;
						}
						$parser->moveToElement();
					}
					$this->xml_node_start($parser, $parser->name, $attributes);
					if ($parser->isEmptyElement) {
						$this->xml_node_end($parser, $parser->name);
					}
					break;

				case \XMLReader::TEXT:
				case \XMLReader::CDATA:
				case \XMLReader::WHITESPACE:
				case \XMLReader::SIGNIFICANT_WHITESPACE:
					if ($parser->hasValue) {
						$this->xml_character_data($parser, $parser->value);
					}
					break;

				case \XMLReader::END_ELEMENT:
					$this->xml_node_end($parser, $parser->name);
					break;
				}
			}
			$this->cleanupData();
			$this->setXMLErrors();
		} finally {
			$parser->close();
		}
		return empty($this->errors);
	}

	protected function setXMLErrors()
	{
		foreach (libxml_get_errors() as $error) {
			$this->log_error($error->message, $error->line, $error->column);
		}
		libxml_clear_errors();
	}

}
