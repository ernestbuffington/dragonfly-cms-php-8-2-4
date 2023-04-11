<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	$_FILES = Array(
		<input type="file" name="file1"/><br/>
		[file1] => Array (
			[name] =>
			[type] =>
			[tmp_name] =>
			[error] => 4
			[size] => 0
		)

		<input type="file" name="file2[]"/><br/>
		<input type="file" name="file2[foo]"/><br/>
		[file2] => Array(
			[name] => Array(
				[0] =>
				[foo] =>
			)
			[type] => ...
		)

		<input type="file" name="file3[foo][]"/><br/>
		[file3] => Array(
			[name] => Array(
				[foo] => Array(
					[0] =>
					[1] =>
				)
			)
			[type] => ...
	)
*/

namespace Poodle\Input;

class FILES extends \ArrayIterator
{
	public function append($v)        { throw new \BadMethodCallException(); }
	public function offsetSet($k, $v) { throw new \BadMethodCallException(); }
	public function offsetUnset($k)   { throw new \BadMethodCallException(); }

	public function getAsFileObject()
	{
		$a = func_get_args();
		if (!empty($a)) {
			$k = $a[0];
			if (isset($this[$k])) {
				$f = $this[$k];
				$c = count($a);
				for ($i=1; $i < $c; ++$i) {
					$k = $a[$i];
					if (!is_string($k) && !is_int($k)) {
						throw new \InvalidArgumentException("Parameter {$i} is not a string or integer. Type is: ".gettype($k));
					}
					if (!is_array($f['name']) || !isset($f['name'][$k])) {
						return null;
					}
					$f = array(
						'name'     => $f['name'][$k],
						'type'     => $f['type'][$k],
						'tmp_name' => $f['tmp_name'][$k],
						'error'    => $f['error'][$k],
						'size'     => $f['size'][$k]
					);
				}
				if (isset($f['name']) && !is_array($f['name'])) {
					return new File($f);
				}
			}
		}
		return null;
	}

	public static function active()
	{
		return !!\Poodle\PHP\INI::get('file_uploads', '1');
	}

	public static function max_filesize()
	{
		return min(
			\Poodle\PHP\INI::getInt('upload_max_filesize', '2M'),
			\Poodle\PHP\INI::getInt('post_max_size', '8M')
		);
	}

	public static function max_uploads()
	{
		return (int)\Poodle\PHP\INI::get('max_file_uploads', '20');
	}
}
