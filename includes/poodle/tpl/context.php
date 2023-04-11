<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\TPL;

class Context
{
	public
		$repeat;

	protected
		$tpl_file;

	private
		$parent,
		$scope,
		$ref;

	function __construct(Context $parent=null, $scope='root')
	{
		$this->parent = $parent;
		$this->scope  = $scope;
		$this->repeat = $parent ? clone $parent->repeat : new \stdClass();
	}
	function __destruct()
	{
		$this->parent = null;
	}

	function __clone()
	{
		$this->parent = $parent;
		$this->repeat = clone $this->repeat;
	}

	function __set($name, $value)
	{
		if (!preg_match('#^[a-z_][a-z0-9_]*#i', $name)) {
			throw new \InvalidArgumentException('Template variable error \''.$name.'\' has an incorrect format, must be of: [a-z_][a-z0-9_]*');
		}
		if (property_exists($this, $name)) {
			if (!$this->ref) { $this->ref = new \ReflectionClass($this); }
			if (!$this->ref->getProperty($name)->isPublic()) {
				throw new \Exception('Template variable error \''.$name.'\' is not a public property and therefore may not be set');
			}
		}
		$this->$name = $value;
	}

	# no need to check isset($this->$name), PHP does that before calling __isset()
	function __isset($name)
	{
		return $this->parent ? isset($this->parent->$name) : false;
//		return defined($name);
	}

	function __get($name)
	{
		if (property_exists($this, $name)) { return $this->$name; }
		if ('SERVER'    === $name) { return $_SERVER; }
		if ('KERNEL'    === $name) { return \Poodle::getKernel(); }
		if ('RESOURCE'  === $name) { return \Poodle::getKernel()->RESOURCE; }
		if ('REQUEST'   === $name) { return array('GET'=>$_GET,'POST'=>isset($_POST)?$_POST:null); }
		if ('SQL'       === $name) { return \Poodle::getKernel()->SQL; }
		if ('CONFIG'    === $name) { return \Poodle::getKernel()->CFG; }
		if ('IDENTITY'  === $name) { return \Poodle::getKernel()->IDENTITY; }
		if ('URI_BASE'  === $name) { return \Poodle::$URI_BASE; }
		if ('URI_MEDIA' === $name) { return \Poodle::$URI_MEDIA; }
		if ($this->__isset($name)) { return $this->parent->$name; }

		$p = $this;
		$scope = $p->scope;
		while ($p = $p->parent) { $scope = $p->scope.':'.$scope; }
//		if (defined($name)) { return constant($name); }
		\Poodle\Debugger::error(E_USER_NOTICE, "Unable to find variable '{$name}' in current scope ({$scope})", $this->tpl_file, 0);
		return null;
	}

	public function toString($filename, $ctx=null)
	{
		if ($this->parent) {
			return $this->parent->toString($filename, $ctx?:$this);
		}
	}

	public function new_context_repeat($var, $exp)
	{
		$ctx = new Context($this, $var);
		$ctx->repeat->$var = new Context_Repeat($exp, $ctx);
		return $ctx;
	}

}

class Context_Repeat implements \Iterator
{
	protected
		$index,         # repetition number, starting from zero.
		$start,         # true for the starting repetition (index 0).
		$end,           # true for the ending, or final, repetition.
		$length = null, # length of the sequence, which will be the total number of repetitions.

		$iterator,
		$current,
		$key,
		$valid,
		$parent;

	function __construct($source, $ctx)
	{
		$this->parent = $ctx;
		if (is_array($source))                          { $this->iterator = new \ArrayIterator($source); }
		else if ($source instanceof \IteratorAggregate) { $this->iterator = $source->getIterator(); }
		else if ($source instanceof \Iterator)          { $this->iterator = $source; }
		else if ($source instanceof \Traversable)       { $this->iterator = new \IteratorIterator($source); }
		else if ($source instanceof \stdClass)          { $this->iterator = new \ArrayIterator((array)$source); }
		else                                            { $this->iterator = new \ArrayIterator(array()); }

		if ($this->iterator instanceof \Countable)      { $this->length = count($this->iterator); }
	}

	function __destruct()
	{
		$this->parent = null;
	}

	# repetition number, starting from one.
	public function number()
	{
		return 1 + $this->index;
	}

	# true for even-indexed repetitions (0, 2, 4, ...).
	public function even()
	{
		return 0 === ($this->index % 2);
	}

	# true for odd-indexed repetitions (1, 3, 5, ...).
	public function odd()
	{
		return 1 === ($this->index % 2);
	}

	# length of the sequence, which will be the total number of repetitions.
	public function length()
	{
		return $this->length;
	}

	/**
	 * Iterator
	 */

	protected function fetch()
	{
		if ($this->valid = $this->iterator->valid()) {
			$this->current = $this->iterator->current();
			$this->key     = $this->iterator->key();
			# Prefetch next
			$this->iterator->next();
			$this->end = !$this->iterator->valid();
		} else {
			$this->current = null;
			$this->key     = 0;
		}
		if ($this->end && null === $this->length) {
			$this->length = $this->valid ? 1 + $this->index : 0;
		}
	}
	public function current() { return $this->current; }
	public function key()     { return $this->key; }
	public function next()    { $this->fetch(); ++$this->index; }
	public function rewind()
	{
		$this->iterator->rewind();
		$this->index = 0;
		$this->fetch();
	}
	public function valid() { return $this->valid; }

	/**
	 * TAL
	 */

	function __get($key)
	{
		switch ($key)
		{
		case 'index':  return $this->index;
		case 'start':  return 0 === $this->index;
		case 'end':    return $this->end;

		# count reps with lower-case letters: "a" - "z", "aa" - "az", "ba" - "bz", ..., "za" - "zz", "aaa" - "aaz", and so forth.
		case 'letter': return $this->int2letter(1 + $this->index);
		# upper-case version of letter
		case 'Letter': return strtoupper($this->int2letter(1 + $this->index));
		}

		$p = $this->parent;
		$scope = 'repeat:'.$p->scope;
		while ($p = $p->parent) { $scope = $p->scope.':'.$scope; }
		\Poodle\Debugger::error(E_USER_NOTICE, "Undefined property '{$key}' in current scope ({$scope})", $this->parent->tpl_file, 0);
	}

	protected function int2letter($int)
	{
		static $alpha = 'abcdefghijklmnopqrstuvwxyz';
		$letters = '';
		while ($int--) {
			$letters = $alpha[$int % 26] . $letters;
			$int = floor($int / 26);
		}
		return $letters;
	}

}
