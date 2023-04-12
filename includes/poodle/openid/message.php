<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-authentication-2_0.html#rfc.section.4.1
*/

namespace Poodle\OpenID;

class Message implements \ArrayAccess
{
	public const V1_URL_LIMIT = 2047;

	protected
		$version = 0,
		$namespaces = array();

	function __construct($default_namespace = null)
	{
		if ($default_namespace) {
			$this->setDefaultNamespace($default_namespace);
		}
	}

	public function setDefaultNamespace($uri)
	{
		if (!in_array($uri, \Poodle\OpenID::getNamespaces()))
		{
			trigger_error("Invalid default namespace: {$uri}");
			return false;
		}
		$this->addNamespace($uri, null);
		if (\Poodle\OpenID::NS_1_0 === $uri) $this->version = 1.0;
		else if (\Poodle\OpenID::NS_1_1 === $uri) $this->version = 1.1;
		else if (\Poodle\OpenID::NS_2_0 === $uri) $this->version = 2.0;
		return true;
	}

	protected static $NS_CLASSES = array(
		\Poodle\OpenID::NS_2_0 => 'Poodle\\OpenID\\Message_Fields_v2',
		\Poodle\OpenID::NS_1_1 => 'Poodle\\OpenID\\Message_Fields_v1',
		\Poodle\OpenID::NS_1_0 => 'Poodle\\OpenID\\Message_Fields_v1',
	);
	public static function registerNamespaceClass($ns, $class) { self::$NS_CLASSES[$ns] = $class; }
	public function addNamespace($uri, $alias)
	{
		$ns = $this->getNamespaceByAlias($alias);
		if ($ns) {
			if ($ns->uri !== $uri) {
				throw new \Exception("Namespace alias '{$alias}' already assigned");
			}
		} else {
			$ns = $this->getNamespaceByURI($uri);
			if ($ns) {
				trigger_error("Cannot map {$uri} to alias {$alias}.");
			} else {
				$c = self::$NS_CLASSES[$uri] ?? 'Poodle\\OpenID\\Message_Fields';
				$ns = new $c($uri, $alias);
				$this->namespaces[$alias] = $ns;
			}
		}
		return $ns;
	}
	public function getNamespaceByAlias($alias=null) { return $this->namespaces[$alias] ?? null; }
	public function getNamespaceByURI($uri) {
		foreach ($this->namespaces as $ns) { if ($ns->uri === $uri) return $ns; }
		return null;
	}
	public function getDefaultNamespace() { return $this->getNamespaceByAlias()->uri; }

	public function isOpenIDv1() { return (1 === (int)$this->version); }
	public function isOpenIDv2() { return (2 === (int)$this->version); }

	public static function fromArray($args)
	{
		$openid_args = array();
		foreach ($args as $key => $value)
		{
			$parts = explode('.', $key, 2);
			if (2 === count($parts) && 'openid' === $parts[0] && is_string($value)) {
				$openid_args[$parts[1]] = $value;
			}
		}
		return $openid_args ? self::fromOpenIDArgs($openid_args) : false;
	}

	public static function fromKVForm($kvform_string)
	{
		// Create a Message from a KVForm string
		$lines = explode("\n", trim($kvform_string));
		$values = array();
		foreach ($lines as $line) {
			$kv = explode(':', $line, 2);
			if (empty($kv[1])) { continue; }
			$values[trim($kv[0])] = trim($kv[1]);
		}
		return self::fromOpenIDArgs($values);
	}

	public static function fromOpenIDArgs($openid_args)
	{
		// Takes an array.
		// Construct a Message from a parsed KVForm message
		$obj = new \Poodle\OpenID\Message();
		$obj->parseOpenIDArgs($openid_args);
		return $obj;
	}

	protected function parseOpenIDArgs($openid_args)
	{
		$ns_args = array();
		foreach ($openid_args as $key => $value)
		{
			list($ns_alias, $key) = self::splitNS($key);
			if ('ns' === $ns_alias) {
				$this->addNamespace($value, $key);
			} else if (null === $ns_alias && 'ns' === $key) {
				$this->setDefaultNamespace($value);
			} else {
				if (!isset($ns_args[$ns_alias])) $ns_args[$ns_alias] = array();
				$ns_args[$ns_alias][$key] = $value;
			}
		}

		// OpenID v1 doesn't know namespace, so we create them
		if (!isset($this->namespaces[null])) {
			$this->setDefaultNamespace(\Poodle\OpenID::NS_1_0);
			if (isset($ns_args['pape'])) {
				$this->addNamespace(\Poodle\OpenID\Extensions\PAPE::NS_1_0, 'pape');
			}
			if (isset($ns_args['sreg'])) {
				$this->addNamespace(\Poodle\OpenID\Extensions\SREG::NS_1_0, 'sreg');
			}
		}

		foreach ($ns_args as $ns_alias => $fields) {
			if ($ns = $this->getNamespaceByAlias($ns_alias)) {
				foreach ($fields as $k => $v) $ns[$k] = $v;
			}
		}
	}

	protected static $REGISTERED_ALIASES = array();
	public static function getRegisteredNS($alias)
	{
		return self::$REGISTERED_ALIASES[$alias] ?? null;
	}
	/**
	 * Registers a (namespace URI, alias) mapping in a global namespace
	 * alias map.  Raises NamespaceAliasRegistrationError if either the
	 * namespace URI or alias has already been registered with a different
	 * value.  This function is required if you want to use a namespace
	 * with an OpenID 1 message.
	 */
	public static function registerNamespaceAlias($uri, $alias)
	{
		if (self::getRegisteredNS($alias) === $uri) {
			return true;
		}
		if (in_array($uri, self::$REGISTERED_ALIASES)) {
			return false;
		}
		if (in_array($alias, self::$REGISTERED_ALIASES)) {
			return false;
		}
		self::$REGISTERED_ALIASES[$alias] = $uri;
		return true;
	}
	/**
	 * Removes a (uri, alias) registration from the global
	 * namespace alias map.  Returns true if the removal succeeded; false
	 * if not (if the mapping did not exist).
	 */
	public static function unregisterNamespaceAlias($uri, $alias)
	{
		if (self::getRegisteredNS($alias) === $uri) {
			unset(self::$REGISTERED_ALIASES[$alias]);
			return true;
		}
		return false;
	}

	public function getFields()
	{
		$a = array(array(),array());
		$set_ns_key = !$this->isOpenIDv1(); // OpenID v1 doesn't know namespaces
		foreach ($this->namespaces as $ns)
		{
			if (is_countable($ns) ? count($ns) : 0)
			{
				if ($set_ns_key && $ns->ns_key) $a[0][$ns->ns_key] = $ns->uri;
				foreach ($ns as $k => $v) $a[1][$k] = $v;
			}
		}
		return array_merge($a[0],$a[1]);
	}

	public function asQuery()
	{
		return http_build_query($this->getFields(), '', '&');
	}

	# 4.1.1
	public function asKVForm()
	{
		$kv = '';
		foreach ($this->getFields() as $k => $v) {
			if (0 !== strpos($k, 'openid.')) {
				throw new \Exception('This message can only be encoded as a POST, because it contains arguments that are not prefixed with "openid."');
			}
			if (false !== strpbrk($k, ":\n")) { throw new \Exception("Key '{$k}' contains an invalid character"); }
			if (false !== strpos($v,   "\n")) { throw new \Exception("Value of '{$k}' contains an invalid character"); }
			$kv .= substr($k, 7).':'.$v."\n";
		}
		return $kv;
	}

	protected static function splitNS($key)
	{
		if (!preg_match('#^([^\s\.]+)(?:\.([^\s]+))?$#Di', $key, $p)) { throw new \Exception("Invalid key name: {$key}"); }
		return (empty($p[2]) ? array(null, $p[1]) : array($p[1], $p[2]));
	}

	// ArrayAccess
	public function offsetExists($key)
	{
		$v = $this->offsetGet($key);
		return isset($v);
	}

	public function offsetGet($key)
	{
		list($ns_alias, $key) = self::splitNS($key);
		if ('ns' === $ns_alias) {
			return $this->getNamespaceByAlias($key)->uri;
		}
		if (null === $ns_alias && 'ns' === $key) {
			return $this->getNamespaceByAlias()->uri;
		}
		$ns = $this->getNamespaceByAlias($ns_alias);
		return $ns ? $ns[$key] : null;
	}

	public function offsetSet($key, $v)
	{
		list($ns_alias, $key) = self::splitNS($key);
		if ('ns' === $ns_alias) {
			$this->addNamespace($v, $key);
		} else
		if (null === $ns_alias && 'ns' === $key) {
			$this->setDefaultNamespace($v);
		} else
		if ($ns = $this->getNamespaceByAlias($ns_alias)) {
			if ($v) {
				$ns[$key] = $v;
			} else {
				unset($ns[$key]);
			}
		} else {
			throw new \Exception("Namespace alias '{$ns_alias}' doesn't exist");
		}
	}

	public function offsetUnset($key)
	{
		list($ns_alias, $key) = self::splitNS($key);
		if ('ns' !== $ns_alias
		 && !(null === $ns_alias && 'ns' === $key)
		 && $ns = $this->getNamespaceByAlias($ns_alias)
		) {
			unset($ns[$key]);
		}
	}

}



/**
 * Base object for the fields (parameters) of an (un)known namespace.
 * Class is extended by OpenID versions and extensions.
 * Fields are accessed like an array ($object['fieldname']),
 * anything else as properties and methods.
 */
class Message_Fields implements \ArrayAccess, \Countable, \Iterator
{
	protected
		$uri,
		$alias,
		$prefix,
		$fields = array(),
		$valid_keys = array();

	# 12
	protected static
		$NS_DISALLOWED_ALIASES = array(
		'openid',
		'ns', 'mode',                    # 4.1.2
		'error', 'contact', 'reference', # 5.1.2.2
		'assoc_type', 'session_type',    # 8.1.1
		'dh_modulus', 'dh_gen', 'dh_consumer_public', # 8.1.2
		'claimed_id', 'identity', 'assoc_handle', 'return_to', 'realm', # 9.1
		'op_endpoint', 'response_nonce', 'invalidate_handle', 'signed', 'sig', # 10.1
		'delegate', 'server', 'trust_root' # 14.2.1
		);

	function __construct($uri, $alias)
	{
		if (!is_string($uri) || !strlen($uri = trim($uri))) {
			throw new \Exception("Namespace URI is not a string or empty");
		}
		if (null !== $alias) {
			if (!is_string($alias) || !strlen($alias = trim($alias))) {
				throw new \Exception("Namespace alias is not a string or empty");
			}
			if (in_array($alias, self::$NS_DISALLOWED_ALIASES)) {
				throw new \Exception("Namespace alias '{$alias}' is disallowed");
			}
			if (false !== strpos($alias, '.')) {
				throw new \Exception("Namespace alias '{$alias}' must not contain a period");
			}
		}
		$this->uri    = $uri;
		$this->alias  = $alias;
		$this->prefix = 'openid.'.($alias?$alias.'.':'');
	}

	function __get($k)
	{
		switch ($k)
		{
		case 'uri':
		case 'alias':
		case 'prefix':    return $this->$k;
		case 'ns_key':    return ($this->alias ? "openid.ns.{$this->alias}" : 'openid.ns');
		}
		trigger_error("Property {$k} does not exist");
	}

	public function setFields(array $a) { foreach ($a as $k => $v) $this->offsetSet($k, $v); }

	protected function fixKey($k)
	{
		if ($this->valid_keys && !in_array($k, $this->valid_keys))
			trigger_error("Using unknown field key '{$k}' for ".get_class($this));
		return $k;
	}

	// ArrayAccess
	public function offsetExists($k) { return array_key_exists($this->fixKey($k), $this->fields); }
	public function offsetGet($k)
	{
		$k = $this->fixKey($k);
		return $this->fields[$k] ?? null;
	}
	public function offsetSet($k,$v)
	{
		$k = $this->fixKey($k);
		if (is_string($v)) {
			$this->fields[$k] = $v;
		} else if (!$v) {
			$this->offsetUnset($k);
		}
	}
	public function offsetUnset($k) { unset($this->fields[$this->fixKey($k)]); }

	// Countable
	public function count()   { return is_countable($this->fields) ? count($this->fields) : 0; }

	// Iterator
	protected $iterator_valid;
	public function rewind()  { $this->iterator_valid = (false !== reset($this->fields)); }
	public function valid()   { return $this->iterator_valid; }
	public function current() { return current($this->fields); }
	public function key()     { return $this->prefix.key($this->fields); }
	public function next()    { $this->iterator_valid = (false !== next($this->fields)); }
}



/**
 * http://openid.net/specs/openid-authentication-2_0.html
 */
class Message_Fields_v2 extends Message_Fields
{
	protected $valid_keys = array(
		'mode',                          # 4.1.2
		'error', 'contact', 'reference', # 5.1.2.2
		'assoc_type', 'session_type', 'dh_modulus', 'dh_gen', 'dh_consumer_public', # 8.1
		'expires_in', 'mac_key', 'dh_server_public', 'enc_mac_key', 'error_code',   # 8.2
		'claimed_id', 'identity', 'assoc_handle', 'return_to', 'realm',             # 9.1
		'op_endpoint', 'response_nonce', 'invalidate_handle', 'signed', 'sig',      # 10.1
		'is_valid',                                                                 # 11.4.2.2.
	);

	function __construct($uri, $alias)
	{
		parent::__construct(\Poodle\OpenID::NS_2_0, $alias);
	}

	// 9.1: "claimed_id" and "identity" SHALL be either both present or both absent.
	public function offsetSet($k,$v)
	{
		parent::offsetSet($k,$v);
		if ('claimed_id' === $k && !parent::offsetGet('identity')) parent::offsetSet('identity',$v);
	}
	public function offsetUnset($k)
	{
		parent::offsetUnset($k);
		if ('claimed_id' === $k) parent::offsetUnset('identity');
		if ('identity'   === $k) parent::offsetUnset('claimed_id');
	}
}



/**
 * http://openid.net/specs/openid-authentication-1_1.html
 */
class Message_Fields_v1 extends Message_Fields
{
	protected $valid_keys = array(
		'mode', 'assoc_type','session_type', 'dh_modulus', 'dh_gen', 'dh_consumer_public', # 4.1.1
		'assoc_handle', 'expires_in', 'dh_server_public', 'enc_mac_key', 'mac_key',        # 4.1.2
		'identity', 'return_to', 'trust_root',                                             # 4.2.1
		'user_setup_url', 'signed', 'sig', 'invalidate_handle',                            # 4.2.2
		'error', 'contact', 'reference', # Taken from OpenIDv2 5.1.2.2
		'is_valid',                                                                        # 11.4.2.2.
	);

	function __construct($uri, $alias)
	{
		if (\Poodle\OpenID::NS_1_0 !== $uri && \Poodle\OpenID::NS_1_1 !== $uri)
			$uri = \Poodle\OpenID::NS_1_0;
		parent::__construct($uri, $alias);
	}

	protected function fixKey($k)
	{
		if ('claimed_id' === $k) $k = 'identity';
		if ('realm'      === $k) $k = 'trust_root';
		return parent::fixKey($k);
	}
}

// load all extensions
foreach (glob(__DIR__.DIRECTORY_SEPARATOR.'extensions'.DIRECTORY_SEPARATOR.'*.php') as $file)
{
	include_once($file);
}
