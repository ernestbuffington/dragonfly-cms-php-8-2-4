<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	wiki.zope.org/ZPT/TALESSpecification13
*/

namespace Poodle\TPL;

abstract class Tales
{
	const
		DEFAULT_KEYWORD = '__DEFAULT__';

	# T_STRING
	private static
		$PHP_ALLOWED_FUNCTIONS = array(
			'count|empty|is_(array|bool|float|int|null|numeric|object|scalar|string)|isset|defined|extension_loaded',
			'Arrays' => 'array_sum|in_array|print_r',
			'Date/Time' => 'gmdate|date|time|date_default_timezone_get',
			'Filesystem' => 'basename|is_dir|is_file',
			'Math'   => 'ceil|floor|is_finite|is_infinite|is_nan|max|min|rand|round|mt_rand',
			'PCRE'   => 'preg_(match|match_all|split)',
			'String' => 'bin2hex|explode|implode|json_encode|mb_(str|sub)[a-z_]+|nl2br|strtolower|strtoupper|substr|stri?pos|trim|ucfirst|ucwords|htmlspecialchars|strip_tags|strtotime|str_replace',
			'Dragonfly' => 'can_admin|is_active|sprintf|htmlprepare|footmsg',
		);

	# php.net/tokens
	private static
		$PHP_ALLOWED_TOKENS = array(
			T_ARRAY,
			T_ARRAY_CAST,
			T_BOOLEAN_AND,
			T_BOOLEAN_OR,
			T_CATCH,
			T_CONSTANT_ENCAPSED_STRING,
			T_CURLY_OPEN,
			T_DNUMBER,
			T_DOUBLE_COLON,
			T_EMPTY,
			T_ENCAPSED_AND_WHITESPACE,
			T_INLINE_HTML,
			T_INSTANCEOF,
			T_ISSET,
			T_IS_EQUAL,
			T_IS_GREATER_OR_EQUAL,
			T_IS_IDENTICAL,
			T_IS_NOT_EQUAL,
			T_IS_NOT_IDENTICAL,
			T_IS_SMALLER_OR_EQUAL,
			T_LNUMBER,
			T_LOGICAL_AND,
			T_LOGICAL_OR,
			T_LOGICAL_XOR,
			T_NS_SEPARATOR,
			T_NUM_STRING,
			T_OBJECT_OPERATOR,
			T_PAAMAYIM_NEKUDOTAYIM,
			T_STRING,
			T_STRING_VARNAME,
			T_TRY,
			T_VARIABLE,
			T_WHITESPACE,
		);

	/**
	 * translates TALES expression with alternatives into single PHP expression.
	 * e.g. "string:foo${bar}" may be transformed to "'foo'.tal_escape($ctx->bar)"
	 */
	public static function translate_expression($expression, $def = null)
	{
		$type = 'path';
		$expression = trim($expression);

		# Look for tales modifier (not:, path:, string:, php:, etc...)
		if (preg_match('#^([a-z][a-z0-9_]*[a-z0-9]):(.+)$#si', $expression, $m)) {
			list(, $type, $expression) = $m;
		}
		# may be a 'string'
		else if (preg_match('#^\'((?:[^\']|\\\\.)*)\'$#s', $expression, $m)) {
			$expression = stripslashes($m[1]);
			$type = 'string';
		}

		# is it a TALES expression modifier
		if (method_exists(__CLASS__, $type)) {
			return self::$type($expression, $def);
		}

		throw new \Exception("Unknown TALES modifier '{$type}'. Method does not exist");
	}

	protected static function not($expression, $def = null)
	{
		return '!(' . self::translate_expression($expression, $def) . ')';
	}

	/**
	 * path segments may contain: [a-zA-Z0-9\ _-.,~]
	 */
	protected static function path($expression, $def = null)
	{
		$expression = trim($expression);
		/**
		 * default  - special singleton value used by TAL to specify that existing text should not be replaced.
		 * nothing  - special singleton value used by TAL to represent a non-value (e.g. void, None, Nil, NULL).
		 * options  - the keyword arguments passed to the template.
		 * repeat   - the repeat variables (see RepeatVariable).
		 * attrs    - a dictionary containing the initial values of the attributes of the current statement tag.
		 * CONTEXTS - the list of standard names (this list). This can be used to access a builtin variable that
		 *            has been hidden by a local or global variable with the same name
		 */
		if ('default' === $expression) { return is_string($def) ? $def : self::DEFAULT_KEYWORD; }
		if ('nothing' === $expression || '' == $expression) { return 'null'; }

		$exps = explode('|', $expression);
		if (isset($exps[1])) {
			$result = array();
			foreach ($exps as $exp) {
				$result[] = self::translate_expression(trim($exp), $def);
			}
			return 'self::get_valid_option(array('.implode(',', $result).'))';
		}

		if (preg_match('#^([a-z_][a-z0-9_]*)(?:/(.+))?$#i', $expression, $m)) {
			if (!isset($m[2]) || !strlen($m[2])) {
				// Speed tales:
				if ('CAN_ADMIN' === $expression || 'S_CAN_ADMIN' === $expression) { return 'can_admin()'; }
				if ('IS_ADMIN' === $expression || 'S_IS_ADMIN' === $expression) { return 'is_admin()'; }
				if ('PAGE_TITLE' === $expression || 'S_PAGETITLE' === $expression) { return '\\Dragonfly\\Page::get(\'title\')'; }
				if ('ADMIN_PAGES' === $expression) { return 'defined(\'ADMIN_PAGES\')'; }
				if ('S_TEXTDIR' === $expression) { return '_TEXT_DIR'; }
				if ('S_LANGCODE' === $expression) { return '\\Dragonfly::getKernel()->L10N->lng'; }
				if ('S_DELIM' === $expression || 'BC_DELIM' === $expression) { return '_BC_DELIM'; }
				if ('S_STATIC_DOMAIN' === $expression) { return 'DF_STATIC_DOMAIN'; }
				if ('BASEHREF' === $expression) { return 'BASEHREF'; }
				// Normal:
				return '$ctx->'.$expression;
			}
			// Magic tales:
			if ('can_admin' === $m[1]) {
				return 'can_admin('.self::string($m[2]).')';
			}
			// Normal:
			if ('root' === $m[1]) {
				$m[1] = '\\Poodle::getKernel()';
//			} else if ('user' === $m[1]) {
//				$m[1] = '\\Poodle::getKernel()->IDENTITY';
			} else {
				$m[1] = '$ctx->' . $m[1];
			}
			return 'self::path('.$m[1].','.self::string($m[2]).')';
		}
		throw new \Exception("Invalid path expression: {$expression}");
		return "trigger_error('Invalid path expression: {$expression}')";
	}

	protected static function exists($expression, $def=null)
	{
		if (preg_match('#^([a-z_][a-z0-9_]*)(?:/(.+))?$#i', $expression, $m)) {
			return 'self::path_exists($ctx, '.self::string($expression).')';
		}
		throw new \Exception("Invalid path expression: {$expression}");
		return "trigger_error('Invalid path expression: {$expression}')";
	}

	protected static function php($expression, $def=null)
	{
		if (is_array(self::$PHP_ALLOWED_FUNCTIONS)) {
			self::$PHP_ALLOWED_FUNCTIONS = '#^('.implode('|',self::$PHP_ALLOWED_FUNCTIONS).')$#i';
		}

		$tokens = token_get_all('<?php '.$expression);

		// Resolve dot to T_OBJECT_OPERATOR and T_DOLLAR_OPEN_CURLY_BRACES to Tales::path
		$n_tokens = array();
		$c_tokens = count($tokens);
		for ($i=1; $i<$c_tokens; ++$i) {
			$t = $tokens[$i];
			if ('.' === $t && is_array($tokens[$i-1]) && T_STRING === $tokens[$i-1][0]) {
				$t = array(T_OBJECT_OPERATOR,'->');
			} else
			if ('$' === $t && '{' === $tokens[$i+1]) {
				while ('}' !== $tokens[++$i] && $i < $c_tokens) {
					$t .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
				}
				$t .= $tokens[$i];
				if ('}' === $tokens[$i]) {
					$t = self::path(substr($t, 2, -1), $def);
				}
			}
			$n_tokens[] = $t;
		}
		$tokens = $n_tokens;

		// Build the PHP string with allowed tokens
		$php = '';
		foreach ($tokens as $i => &$a) {
			if (is_array($a)) {
				$error = false;
				if (!in_array($a[0], self::$PHP_ALLOWED_TOKENS)) {
					$error = token_name($a[0]);
				}
				// Check for disallowed functions
				else if (T_STRING === $a[0] && isset($tokens[$i+1]) && !is_array($tokens[$i+1]) && '(' === trim($tokens[$i+1])
				 && (0==$i || !(is_array($tokens[$i-1]) && (T_DOUBLE_COLON === $tokens[$i-1][0] || T_OBJECT_OPERATOR === $tokens[$i-1][0] || T_NS_SEPARATOR === $tokens[$i-1][0])))
				 && !preg_match(self::$PHP_ALLOWED_FUNCTIONS, $a[1]))
				{
					$error = "function {$a[1]}()";
				}
				else if (T_VARIABLE === $a[0] && '$_GET' !== $a[1] && '$_POST' !== $a[1] && 0 === strpos($a[1], '$_')
				 && !(is_array($tokens[$i+1]) && T_OBJECT_OPERATOR === $tokens[$i+1][0]))
				{
					$error = "T_VARIABLE {$a[1]}";
				}

				if ($error) {
					return self::triggerError("TALES php: Disallowed {$error}");
				}
				if (T_STRING === $a[0] && isset($tokens[$i+1]) && T_OBJECT_OPERATOR === $tokens[$i+1][0]) {
					if (!$i || !is_array($tokens[$i-1]) || T_WHITESPACE === $tokens[$i-1][0]) {
						if ('context' === $a[1]) $a[1] = '$ctx';
						else if ('here' === $a[1]) $a[1] = '$this';
						else if ('root' === $a[1]) $a[1] = '\\Poodle::getKernel()';
//						else if ('user' === $a[1]) $a[1] = '\\Poodle::getKernel()->IDENTITY';
					}
				}
				# T_STRING                   "parent" 307
				# T_CONSTANT_ENCAPSED_STRING 'string' 315
				# T_STRING_VARNAME           "${a}"
				if (T_CONSTANT_ENCAPSED_STRING === $a[0]) {
					$a = self::translate_expression($a[1]);
				}
			} else if ('=' === $a) {
				return self::triggerError("TALES php: Disallowed '='");
			} else if ('`' === $a) {
				$a = '\'';
			}
			$php .= is_array($a) ? $a[1] : $a;
		}
		$tokens = null;

//		$php = preg_replace('#\.([a-z])#i', '->$1', $php);
//		$php = preg_replace('#(^|[^\pL\pN])context->#', '$ctx->', $php);
//		$php = preg_replace('#(^|[^\pL\pN])here->#', '$this->', $php);

		return $php;
		return str_replace(".''", '', "'{$php}'");
	}

	/**
	 * string:
	 *
	 *      string_expression ::= ( plain_string | [ varsub ] )*
	 *      varsub            ::= ( '$' Path ) | ( '${' Path '}' )
	 *      plain_string      ::= ( '$$' | non_dollar )*
	 *      non_dollar        ::= any character except '$'
	 *
	 * Examples:
	 *
	 *      string:my string
	 *      string:hello, $username how are you
	 *      string:hello, ${user/name}
	 *      string:you have $$130 in your bank account
	 */
	public static function string($expression, $def=null)
	{
		$expression = addcslashes($expression, "'\\");
		if ($c = preg_match_all('#(\\$+)([a-z0-9_]+|{([a-z0-9_/]+)})#i', $expression, $m, PREG_SET_ORDER)) {
			foreach ($m as $match) {
				$s = strlen($match[1]);
				# push $$ as $
				$v = substr($match[1], 0, floor($s/2));
				# process expression
				if ($s % 2) {
					if (isset($match[3]) && strlen($match[3])) {
						$v .= '\'.' . self::path($match[3], $def) . '.\'';
					} else {
						$v .= '\'.' . self::path($match[2], $def) . '.\'';
					}
				}
				$expression = str_replace($match[0], $v, $expression);
			}
		}
		return str_replace(".''", '', "'{$expression}'");
	}

	protected static function admin_url($expression, $def = null)
	{
		return 'URL::admin(' . self::translate_expression($expression, $def) . ')';
	}

	protected static function index_url($expression, $def = null)
	{
		return 'URL::index(' . self::translate_expression($expression, $def) . ')';
	}

	protected static function triggerError($error)
	{
		$file = '';
		foreach (debug_backtrace() as $trace) {
			if (isset($trace['object']) && $trace['object'] instanceof \Poodle\TPL\Parser) {
				$file = $trace['object']->getCurrentFilename();
				\Poodle\Debugger::error(E_USER_WARNING, $error, $file, 0);
				break;
			}
		}
		return "\Poodle\Debugger::error(E_USER_WARNING,'".str_replace('\'','\\\'',$error)."','{$file}', __LINE__)";
	}

}
