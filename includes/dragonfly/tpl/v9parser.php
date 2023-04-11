<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly\TPL;

class v9parser
{

	// The all seeing all doing compile method. Parts are inspired by or directly
	// from Smarty
	public static function parse($filename)
	{
		// Remove SMARTY comments
		$code = preg_replace('#\{\*(.*?)\*\}#s', '', file_get_contents($filename));

		// Remove any "loose" php ... we want to give admins the ability
		// to switch on/off PHP for a given template. Allowing unchecked
		// php is a no-no. There is a potential issue here in that non-php
		// content may be removed ... however designers should use entities
		// if they wish to display < and >
		$match_php_tags = array('#\<\?php .*?\?\>#is', '#\<\script language="php"\>.*?\<\/script\>#is', '#\<\?.*?\?\>#s', '#\<%.*?%\>#s');
		$code = preg_replace($match_php_tags, '', $code);

		// Pull out all block/statement level elements and seperate plain text
		preg_match_all('#<!-- PHP -->(.*?)<!-- ENDPHP -->#s', $code, $matches);
		$php_blocks = $matches[1];
		$code = preg_replace('#<!-- PHP -->(.*?)<!-- ENDPHP -->#s', '<!-- PHP -->', $code);

		preg_match_all('#<!-- INCLUDE ([a-zA-Z0-9\_\-\+\.\/{}]+?) -->#', $code, $matches);
		$include_blocks = $matches[1];
		$code = preg_replace('#<!-- INCLUDE ([a-zA-Z0-9\_\-\+\.\/{}]+?) -->#', '<!-- INCLUDE -->', $code);

		preg_match_all('#<!-- INCLUDEPHP ([a-zA-Z0-9\_\-\+\.\\\\\/]+?) -->#', $code, $matches);
		$includephp_blocks = $matches[1];
		$code = preg_replace('#<!-- INCLUDEPHP ([a-zA-Z0-9\_\-\+\.\/]+?) -->#', '<!-- INCLUDEPHP -->', $code);

		preg_match_all('#<!-- (.*?) (.*?)?\s*-->#s', $code, $blocks);
		$text_blocks = preg_split('#<!-- (.*?) (.*?)?\s*-->#s', $code);

		$template_php = '';

		$block_else_level = $block_names = array();
		foreach ($text_blocks as $i => $text_block)
		{
			$compile_block = '';
			if (isset($blocks[1][$i]))
			switch ($blocks[1][$i])
			{
				case 'BEGIN':
					$block_else_level[] = false;
					// Allow for control of looping (indexes start from zero):
					// foo(2)    : Will start the loop on the 3rd entry
					// foo(-2)   : Will start the loop two entries from the end
					// foo(3,4)  : Will start the loop on the fourth entry and end it on the fourth
					// foo(3,-4) : Will start the loop on the fourth entry and end it four from last
					$tag_args = $blocks[2][$i];
					$skip = '';
					if (preg_match('#^(.*?)\(([\-0-9]+)(,([\-0-9]+))?\)$#', $tag_args, $match)) {
						exit('old template BEGIN '.$tag_args.' not supported yet');
						$tag_args = $match[1];
						if ($match[2]<0)     { $skip .= "if (\$ctx->repeat->{$tag_args}->index < \$ctx->repeat->{$tag_args}->length{$match[2]}) continue; "; }
						elseif ($match[2]>0) { $skip .= "if (\$ctx->repeat->{$tag_args}->index < {$match[2]}) continue; "; }
						if (!empty($match[4])) {
							if ($match[4]<0)     { $skip .= "if (\$ctx->repeat->{$tag_args}->index < \$ctx->repeat->{$tag_args}->length{$match[4]}) break; "; }
							elseif ($match[4]>0) { $skip .= "if (\$ctx->repeat->{$tag_args}->index < {$match[4]}) break; "; }
						}
					}
					if ($block_names) {
						$pblock = $block_names[count($block_names)-1];
						$compile_block = "<?php if (self::path(\$ctx->{$pblock},'{$tag_args}')) { \$ctx = \$ctx->new_context_repeat('{$tag_args}', self::path(\$ctx->{$pblock},'{$tag_args}')); foreach (\$ctx->repeat->{$tag_args} as \$ctx->{$tag_args}) { ?>";
					} else {
						$compile_block = "<?php if (!empty(\$ctx->{$tag_args})) { \$ctx = \$ctx->new_context_repeat('{$tag_args}', \$ctx->{$tag_args}); foreach (\$ctx->repeat->{$tag_args} as \$ctx->{$tag_args}) { ?>";
					}
					array_push($block_names, $tag_args);
					break;

				case 'BEGINELSE':
					$block_else_level[count($block_else_level) - 1] = true;
					$compile_block = '<?php } $ctx = $ctx->parent; } else { ?>';
					break;

				case 'END':
					$compile_block = '<?php ' . ((array_pop($block_else_level)) ? '}' : '} $ctx = $ctx->parent; }') . ' ?>';
					array_pop($block_names);
					break;

				case 'IF':
					$compile_block = '<?php if (' . self::compile_tag_if($blocks[2][$i], false) . ') { ?>';
					break;

				case 'ELSE':
					$compile_block = '<?php } else { ?>';
					break;

				case 'ELSEIF':
					$compile_block = '<?php } elseif (' . self::compile_tag_if($blocks[2][$i], true) . ') { ?>';
					break;

				case 'ENDIF':
					$compile_block = '<?php } ?>';
					break;

				case 'DEFINE':
					preg_match('#^(([a-z0-9\-_]+?\.)+?)?\$([A-Z][A-Z0-9_\-]*?) = (\'?)(.*?)(\'?)$#', $blocks[2][$i], $match);
					if (empty($match[3]) || empty($match[5])) {
						trigger_error("'{$blocks[2][$i]}' is no proper &lt;!-- DEFINE --&gt; format.", E_USER_WARNING);
					} else {
						// Are we a string?
						if ($match[4] && $match[6]) {
							// Can't use addslashes as we don't know what value has magic_quotes_sybase.
							$match[5] = "'" . addcslashes(str_replace(array('\\\'', '\\\\'), array('\'', '\\'), $match[5]), "\0..\37\177\\\"'") . "'"; //"
						} else {
							preg_match('#(true|false|\.)#i', $match[5], $type);
							switch (strtolower($type[1])) {
								case 'true':
								case 'false':
									$match[5] = strtoupper($match[5]);
									break;
								case '.':
									$match[5] = doubleval($match[5]);
									break;
								default:
									$match[5] = (int)$match[5];
									break;
							}
						}
						$compile_block = "<?php \$ctx->{$match[3]} = {$match[5]}; ?>";
					}
					break;

				case 'UNDEFINE':
//					trigger_error('cpgtpl UNDEFINE not supported');
					break;

				case 'INCLUDE':
					$file = preg_replace('#{([a-z0-9\-_\[\]\'\"\.]*\.)?([a-z0-9_]+)\.([a-z0-9_]+)}#i','".self::path($ctx->\2,\'\3\')."',array_shift($include_blocks));
					$file = preg_replace('#{([a-z0-9_]+)}#i', '{$ctx->\1}', $file);
					$compile_block = '<?php $this->display("'.str_replace('.html','',$file).'"); ?>';
					break;

				case 'INCLUDEPHP':
					/* $compile_block = "<?php include('".$this->root.'/'.array_shift($includephp_blocks))."'); ?>"; */
					break;

				case 'PHP':
					$compile_block = '<?php '.array_shift($php_blocks).' ?>';
					break;

				default:
					self::compile_var_tags($blocks[0][$i]);
					$compile_block = strlen(trim($blocks[0][$i])) ? $blocks[0][$i] : '';
					break;
			}

			self::compile_var_tags($text_block);
			$template_php .= $text_block . $compile_block;
		}

		$template_php = preg_replace('#;\\s+\\?>#', ';?>', $template_php);
		$template_php = preg_replace('#(<[^>]+href="<\\?php )echo ([^;\\?]+)(;\\?>)#', '$1static::echo_data($2)$3', $template_php);

		// There will be a number of occassions where we switch into and out of
		// PHP mode instantaneously. Rather than "burden" the parser with this
		// we'll strip out such occurences, minimising such switching
		return preg_replace('/\\s*\\?><\\?php\\s*/', ' ', $template_php);
	}

	private static function compile_var_tags(&$text_blocks)
	{
		// change template varrefs into PHP varrefs
		// This one will handle varrefs WITH namespaces
		$text_blocks = preg_replace('#{([a-z0-9\-_\[\]\'\"\.]*\.)?([a-z0-9_]+)\.([a-z0-9_]+)}#i','<?php echo self::path($ctx->\2,\'\3\'); ?>',$text_blocks);
		# This will handle URL::index()
		$text_blocks = preg_replace('#U_\(([a-z0-9\-_&;=]*?)(\,[0-1]{1}(\,[0-1]{1})?)?\)#is', '<?php echo URL::index(\'\\1\'\\2); ?>', $text_blocks);
		# This will handle URL::admin()
		/*$text_blocks = preg_replace('#UADMIN_\(([a-z0-9\-_&;=]*?)\<\?php echo (.*?); \?>\)#i', '<?php echo URL::admin(\'\\1\'.\\2); ?>', $text_blocks);*/
		$text_blocks = preg_replace('#UADMIN_\(([a-z0-9\-_&;=]*?)(\,[0-1]{1})?\)#is', '<?php echo URL::admin(\'\\1\'\\2); ?>', $text_blocks);
		# This will handle language strings
		$text_blocks = preg_replace('#L?_\(([a-z0-9\-_]+)\)#i','<?php echo $this->L10N->get(\'_\1\');?>',$text_blocks);
		// This will handle the remaining root-level varrefs
		$text_blocks = preg_replace_callback('#{([a-z0-9_]+)}#i',function($m){
			return '<?php echo '.static::ctxValue($m[1]).';?>';
		}, $text_blocks);
		return;
	}

	//
	// Compile IF tags - much of this is from Smarty with
	// some adaptions for our block level methods
	//
	private static function compile_tag_if($tag_args, $elseif)
	{
		/* Tokenize args for 'if' tag. */
		preg_match_all('/(?:"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|[(),]|[^\s(),]+)/x', $tag_args, $match);
		$tokens = $match[0];
		$is_arg_stack = array();
		for ($i = 0, $c = count($tokens); $i < $c; ++$i) {
			$token = &$tokens[$i];
			switch ($token)
			{
				case '!':
				case '%':
				case '!==':
				case '==':
				case '===':
				case '>':
				case '<':
				case '!=':
				case '<>':
				case '<<':
				case '>>':
				case '<=':
				case '>=':
				case '&&':
				case '||':
				case '|':
				case '^':
				case '&':
				case '~':
				case ')':
				case ',':
				case '+':
				case '-':
				case '*':
				case '/':
				case '@':
					break;

				case 'eq':
					$token = '==';
					break;

				case 'ne':
				case 'neq':
					$token = '!=';
					break;

				case 'lt':
					$token = '<';
					break;

				case 'le':
				case 'lte':
					$token = '<=';
					break;

				case 'gt':
					$token = '>';
					break;

				case 'ge':
				case 'gte':
					$token = '>=';
					break;

				case 'and':
					$token = '&&';
					break;

				case 'or':
					$token = '||';
					break;

				case 'not':
					$token = '!';
					break;

				case 'mod':
					$token = '%';
					break;

				case '(':
					array_push($is_arg_stack, $i);
					break;

				case 'is':
					$is_arg_start = ($tokens[$i-1] == ')') ? array_pop($is_arg_stack) : $i-1;
					$is_arg       = implode('	', array_slice($tokens, $is_arg_start, $i - $is_arg_start));
					$new_tokens   = self::parse_is_expr($is_arg, array_slice($tokens, $i+1));
					array_splice($tokens, $is_arg_start, count($tokens), $new_tokens);
					$i = $is_arg_start;

				default:
					if (preg_match('#^(([a-z0-9\-_]+?\.)+?)?\$?([a-zA-Z]+[a-zA-Z0-9\-_]+)$#s', $token, $varrefs)) {
						$token = empty($varrefs[1])
							? static::ctxValue($varrefs[3])
							: preg_replace('#([a-z0-9\-_\[\]\'\"\.]*\.)?([a-z0-9_]+)\.([a-z0-9_]+)#i','self::path($ctx->\2,\'\3\')',$token);
					}
					break;
			}
		}
		return implode(' ', $tokens);
	}

	// This is from Smarty
	private static function parse_is_expr($is_arg, $tokens)
	{
		$expr_end = 0;
		$negate_expr = false;
		if (($first_token = array_shift($tokens)) == 'not') {
			$negate_expr = true;
			$expr_type = array_shift($tokens);
		} else {
			$expr_type = $first_token;
		}
		switch ($expr_type)
		{
			case 'even':
				if ($tokens[$expr_end] == 'by') {
					++$expr_end;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!(($is_arg / $expr_arg) % $expr_arg)";
				} else {
					$expr = "!($is_arg % 2)";
				}
				break;

			case 'odd':
				if ($tokens[$expr_end] == 'by') {
					++$expr_end;
					$expr_arg = $tokens[$expr_end++];
					$expr = "(($is_arg / $expr_arg) % $expr_arg)";
				} else {
					$expr = "($is_arg % 2)";
				}
				break;

			case 'div':
				if ($tokens[$expr_end] == 'by') {
					++$expr_end;
					$expr_arg = $tokens[$expr_end++];
					$expr = "!($is_arg % $expr_arg)";
				}
				break;

			default:
				break;
		}
		if ($negate_expr) { $expr = "!($expr)"; }
		array_splice($tokens, 0, $expr_end, $expr);
		return $tokens;
	}

	private static function ctxValue($key)
	{
		if ('CAN_ADMIN' === $key || 'S_CAN_ADMIN' === $key) { return 'can_admin()'; }
		if ('IS_ADMIN' === $key || 'S_IS_ADMIN' === $key) { return 'is_admin()'; }
		if ('PAGE_TITLE' === $key || 'S_PAGETITLE' === $key) { return '\\Dragonfly\\Page::get(\'title\')'; }
		if ('ADMIN_PAGES' === $key) { return 'defined(\'ADMIN_PAGES\')'; }
		if ('S_TEXTDIR' === $key) { return '_TEXT_DIR'; }
		if ('S_LANGCODE' === $key) { return '\\Dragonfly::getKernel()->L10N->lng'; }
		if ('S_DELIM' === $key || 'BC_DELIM' === $key) { return '_BC_DELIM'; }
		if ('S_STATIC_DOMAIN' === $key) { return 'DF_STATIC_DOMAIN'; }
		if ('BASEHREF' === $key) { return 'BASEHREF'; }
		if ('S_LEFTBLOCKS' === $key) { return '$ctx->leftblock'; }
		if ('S_RIGHTBLOCKS' === $key) { return '$ctx->rightblock'; }
		if ('IMPORTANT_MESSAGE' === $key) { return "''"; }
		if ('S_ADMIN_MENU' === $key) { return 'false'; }
		if ('S_ADMIN_CSSMENU' === $key) { return 'false'; }
		return '$ctx->'.$key;
	}

}
