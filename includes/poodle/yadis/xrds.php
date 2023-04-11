<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://en.wikipedia.org/wiki/XRDS
	http://docs.oasis-open.org/xri/2.0/specs/xri-resolution-V2.0.html
	http://yadis.org/
*/

/**
 * This class performs parsing of XRDS documents.
 * Do not instantiate this class directly; rather, call parseXML:
 *
 * $XRDS = \Poodle\Yadis\XRDS::parseXML($xml_string);
 *
 * If the XRDS can be parsed and is valid, an instance of \Poodle\Yadis\XRDS
 * will be returned.  Otherwise, null will be returned.
 */

namespace Poodle\Yadis;

class XRDS
{
	const
		XMLNS_XRD_2_0 = 'xri://$xrd*($v*2.0)', // XRD XML namespace
		XMLNS_XRDS    = 'xri://$xrds',         // XRDS XML namespace

		/**
		 * A given service must match ALL filters that are
		 * passed to the XRDS::services() call.
		 */
		MATCH_ALL = 101,

		/**
		 * A given service must match at least one filter that is
		 * passed to the XRDS::services() call.
		 */
		MATCH_ANY = 102,

		/**
		 * Value used for service elements with no specified priority.
		 */
		MAX_PRIORITY = 2147483647;

	/**
	 * Instantiate a XRDS object.  Requires an XPath
	 * instance which has been used to parse a valid XRDS document.
	 */
	function __construct(XRDS_XMLParser $xmlParser, array $xrdNodes)
	{
		$this->parser   = $xmlParser;
		$this->xrdNode  = $xrdNodes[count($xrdNodes) - 1];
		$this->xrdNodes = $xrdNodes;
		/**
		 * Create the service list using nodes from the XRDS XML document.
		 */
		$this->services = array();
		foreach ($xmlParser->evalXPath('xrd:Service', $this->xrdNode) as $node) {
			$s = new XRDS_Service($node, $xmlParser);
			$prio = $s->getPriority();
			if (!isset($this->services[$prio])) {
				$this->services[$prio] = array();
			}
			$this->services[$prio][] = $s;
		}
	}

	public static function merge_priorities(array $array)
	{
		ksort($array);
		$result = array();
		foreach ($array as $k) {
			shuffle($k);
			$result = array_merge($result, $k);
		}
		return $result;
	}

	/**
	 * Parse an XML string (XRDS document) and return a XRDS
	 * object or null, depending on whether the XRDS XML is valid or not.
	 */
	public static function parseXML($xml_string, $extra_ns_map = null)
	{
		if (empty($xml_string) || !extension_loaded('dom')) {
			return null;
		}

		$parser = new XRDS_XMLParser();

		$ns_map = array(
			'xrds' => self::XMLNS_XRDS,
			'xrd'  => self::XMLNS_XRD_2_0
		);
		if ($extra_ns_map && is_array($extra_ns_map)) {
			$ns_map = array_merge($ns_map, $extra_ns_map);
		}

		if (!$parser->init($xml_string, $ns_map)) {
			return null;
		}

		// Try to get root element.
		$root = $parser->evalXPath('/xrds:XRDS[1]');
		if (!$root) {
			return null;
		}

		if (is_array($root)) {
			$root = $root[0];
		}

		$attrs = $parser->attributes($root);

		if (array_key_exists('xmlns:xrd', $attrs) && self::XMLNS_XRDS != $attrs['xmlns:xrd'])
		{
			return null;
		} else
		if (array_key_exists('xmlns', $attrs)
		 && preg_match('/xri/', $attrs['xmlns'])
		 && self::XMLNS_XRD_2_0 != $attrs['xmlns'])
		{
			return null;
		}

		// Get the last XRD node.
		$xrd_nodes = $parser->evalXPath('/xrds:XRDS[1]/xrd:XRD');

		if (!$xrd_nodes) {
			return null;
		}

		return new XRDS($parser, $xrd_nodes);
	}

	/**
	 * Returns a list of service objects which correspond to <Service>
	 * elements in the XRDS XML document for this object.
	 *
	 * Optionally, an array of filter callbacks may be given to limit
	 * the list of returned service objects.  Furthermore, the default
	 * mode is to return all service objects which match ANY of the
	 * specified filters, but $filter_mode may be
	 * self::MATCH_ALL if you want to be sure that the
	 * returned services match all the given filters.
	 */
	public function services($filters = null, $filter_mode = self::MATCH_ANY)
	{
		// If no filters are specified, return the entire service
		// list, ordered by priority.
		if (!$filters || !is_array($filters)) {
			return self::merge_priorities($this->services);
		}

		if (1 === count($filters)) {
			$filter_mode = self::MATCH_ALL;
		}
		switch ($filter_mode)
		{
		case self::MATCH_ALL:
			$filtered = self::merge_priorities($this->services);
			foreach ($filters as $filter) {
				$filtered = array_filter($filtered, $filter);
			}
			return array_values($filtered);

		case self::MATCH_ANY:
			$filtered = array();
			foreach ($this->services as $prio => $services) {
				foreach ($services as $service) {
					foreach ($filters as $filter) {
						if (call_user_func($filter, $service)) {
							if (!isset($filtered[$prio])) {
								$filtered[$prio] = array();
							}
							$filtered[$prio][] = $service;
							break;
						}
					}
				}
			}
			return self::merge_priorities($filtered);
		}
		// A bad filter mode is specified
		return null;
	}
}



class XRDS_Service
{
	public  $parser;
	private $element;

	function __construct(\DOMNode $node, XRDS_XMLParser $parser)
	{
		$this->element = $node;
		$this->parser  = $parser;
	}

	/**
	 * Return the URIs in the "Type" elements, if any, of this Service element.
	 */
	public function getTypes()
	{
		$t = array();
		foreach ($this->getElements('xrd:Type') as $elem) {
			$c = $this->parser->content($elem);
			if ($c) {
				$t[] = $c;
			}
		}
		return $t;
	}

	public function matchTypes($type_uris)
	{
		$result = array();
		foreach ($this->getTypes() as $typ) {
			if (in_array($typ, $type_uris)) {
				$result[] = $typ;
			}
		}
		return $result;
	}

	protected function getElementPriority($elem)
	{
		$attrs = $this->parser->attributes($elem);
		return isset($attrs['priority']) ? (int)$attrs['priority'] : XRDS::MAX_PRIORITY;
	}

	/**
	 * Return the URIs in the "URI" elements, if any, of this Service
	 * element.  The URIs are returned sorted in priority order.
	 */
	public function getURIs()
	{
		$uris = array();
		foreach ($this->getElements('xrd:URI') as $elem) {
			$prio = $this->getElementPriority($elem);
			if (!isset($uris[$prio])) {
				$uris[$prio] = array();
			}
			$uris[$prio][] = $this->parser->content($elem);
		}
		return XRDS::merge_priorities($uris);
	}

	/**
	 * Returns the "priority" attribute value of this <Service>
	 * element, if the attribute is present.
	 * Returns XRDS::MAX_PRIORITY if not.
	 */
	public function getPriority()
	{
		return $this->getElementPriority($this->element);
	}

	/**
	 * Used to get XML elements from this object's <Service> element.
	 *
	 * This is what you should use to get all custom information out
	 * of this element. This is used by service filter functions to
	 * determine whether a service element contains specific tags,
	 * etc.  NOTE: this only considers elements which are direct
	 * children of the <Service> element for this object.
	 *
	 * @param string $name The name of the element to look for
	 * @return array $list An array of elements with the specified
	 * name which are direct children of the <Service> element.  The
	 * nodes returned by this function can be passed to $this->parser
	 * methods (see XRDS_XMLParser).
	 */
	public function getElements($name)
	{
		return $this->parser->evalXPath($name, $this->element);
	}
}



/**
 * The base class for wrappers for available PHP XML-parsing
 * extensions.  To work with this Yadis library, subclasses of this
 * class MUST implement the API as defined in the remarks for this
 * class.  Subclasses of XRDS_XMLParser are used to wrap
 * particular PHP XML extensions such as 'domxml'.  These are used
 * internally by the library depending on the availability of
 * supported PHP XML extensions.
 */
class XRDS_XMLParser
{
	private
		$doc  = null,
		$xpath = null;

	final public function init($xml_string, $namespaces)
	{
		$this->doc = new \DOMDocument;
		if (!$this->doc || !$this->doc->loadXML($xml_string)) {
			return false;
		}

		$this->xpath = new \DOMXPath($this->doc);
		if (!$this->xpath) {
			return false;
		}

		foreach ($namespaces as $prefix => $uri) {
			if (!$this->registerNamespace($prefix, $uri)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register a namespace with the XML parser.
	 * @param string $prefix The namespace prefix to appear in XML tag names.
	 * @param string $uri The namespace URI to be used to identify the namespace in the XML.
	 * @return boolean $result True if the registration succeeded; false otherwise.
	 */
	public function registerNamespace($prefix, $uri)
	{
		return $this->xpath->registerNamespace($prefix, $uri);
	}

	/**
	 * Evaluate an XPath expression and return the resulting node list.
	 * @param string $xpath The XPath expression to be evaluated.
	 * @param mixed $node A node object resulting from a previous
	 * evalXPath call.  This node, if specified, provides the context
	 * for the evaluation of this xpath expression.
	 * @return array $node_list An array of matching opaque node
	 * objects to be used with other methods of this parser class.
	 */
	public function evalXPath($xpath, $node = null)
	{
		$n = array();
		$result = $node ? $this->xpath->query($xpath, $node) : $this->xpath->query($xpath);
		if ($result) {
			for ($i = 0; $i < $result->length; ++$i) {
				$n[] = $result->item($i);
			}
		}
		return $n;
	}

	/**
	 * Return the textual content of a specified node.
	 * @param mixed $node A node object from a previous call to $this->evalXPath().
	 * @return string $content The content of this node.
	 */
	public function content($node)
	{
		return $node ? trim($node->textContent) : null;
	}

	/**
	 * Return the attributes of a specified node.
	 * @param mixed $node A node object from a previous call to $this->evalXPath().
	 * @return array $attrs An array mapping attribute names to values.
	 */
	public function attributes($node)
	{
		if ($node) {
			$arr = $node->attributes;
			$result = array();
			if ($arr) {
				for ($i = 0; $i < $arr->length; ++$i) {
					$node = $arr->item($i);
					$result[$node->nodeName] = $node->nodeValue;
				}
			}
			return $result;
		}
	}
}
