<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Crypt;

class Symmetric
{
	protected $options = array(
		'cipher'      => '',
		'salt'        => 'Poodle WCMS',
		'compression' => null,
	);

	protected static $compressors = array(
		'gz' => array(
			'encode' => array('gzcompress', 'args' => array(9)),
			'decode' => array('gzuncompress'),
			'desc'   => 'ZLib, RFC 1950'
		),
		'gz_deflate' => array(
			'encode' => array('gzdeflate',  'args' => array(9)),
			'decode' => array('gzinflate'),
			'desc'   => 'ZLib deflate, RFC 1951'
		),
		'gzip' => array(
			'encode' => array('zlib_encode','args' => array(ZLIB_ENCODING_GZIP, 9)),
			'decode' => array('zlib_decode'),
			'desc'   => 'Gzip, RFC 1952'
		),
		'bz' => array(
			'encode' => array('bzcompress', 'args' => array(9, 30)),
			'decode' => array('bzdecompress'),
			'desc'   => 'BZip2, using work factor 30'
		),
		'lzf' => array(
			'encode' => array('lzf_compress'),
			'decode' => array('lzf_decompress'),
			'desc'   => 'LZF'
		),
	);

	function __construct(array $options=array())
	{
		foreach ($this->options as $k => $v) {
			if (!empty($options[$k])) {
				$v = $options[$k];
			}
		}
		foreach (self::$compressors as $k => $v) {
			if (!function_exists($v['encode'][0]) || !function_exists($v['decode'][0])) {
				unset(self::$compressors[$k]);
			}
		}
	}

	public static function listCompressors()
	{
		$list = array(''=>'none');
		foreach (self::$compressors as $k => $v) {
			if (function_exists($v['encode'][0]) && function_exists($v['decode'][0])) {
				$list[$k] = $v['desc'];
			}
		}
		if (isset($list['lzf'])) {
			$list['lzf'] .= (lzf_optimized_for() ? ', optimized for speed' : ', optimized for compression');
		}
		return $list;
	}

	protected function compressor(&$data, $decode=false)
	{
		if (!$this->options['compression']) {
			return true;
		}
		if (!isset(self::$compressors[$this->options['compression']])) {
			return false;
		}
		$compressor = self::$compressors[$this->options['compression']][$decode?'decode':'encode'];
		if (isset($compressor['args'])) {
			$largs = $compressor['args'];
			if (2 === count($largs)) {
				$data = $compressor[0]($data, $largs[0], $largs[1]);
			} else {
				$data = $compressor[0]($data, $largs[0]);
			}
		} else {
			$data = $compressor[0]($data);
		}
		return true;
	}

	protected function hardenKey($weakkey)
	{
		return \Poodle\Hash::string('sha256', $this->options['salt'] . $weakkey, true);
	}

	protected function Scramble(&$data, &$weakkey)
	{
		$strongkey = $this->hardenKey($weakkey);
		$keysize   = strlen($strongkey);
		$datasize  = strlen($data);
		$output    = str_repeat(' ', $datasize);
		$di = $ki  = -1;
		while (++$di < $datasize) {
			if (++$ki >= $keysize) { $ki = 0; }
			$output[$di] = chr((ord($data[$di]) + ord($strongkey[$ki])) % 256);
		}
		return $output;
	}

	protected function Descramble(&$data, &$weakkey)
	{
		$strongkey = $this->hardenKey($weakkey);
		$output    = str_repeat(' ', strlen($data));
		$keysize   = strlen($strongkey);
		$datasize  = strlen($data);
		$di = $ki  = -1;
		while (++$di < $datasize) {
			if (++$ki >= $keysize) { $ki = 0; }
			$work = (ord($data[$di]) - ord($strongkey[$ki]));
			if ($work < 0) { $work += 256; }
			$output[$di] = chr($work);
		}
		return $output;
	}

	public static function listCiphers()
	{
		$list = array();
		if (function_exists('openssl_get_cipher_methods')) {
			$list = openssl_get_cipher_methods();
			$list = array_diff($list, array_map('strtoupper',$list));
			// ECB = insecure, GCM not supported
			$list = array_filter($list, function($v){return !(strpos($v,'-ecb') || strpos($v,'-gcm'));});
			natcasesort($list);
		}
		return $list;
	}

	public function Encrypt(&$Data, $WeakKey)
	{
		# Regenerate hardened key from weak key
		$key = $this->hardenKey($WeakKey);
		# Convert data into a serialized string for single packing
		$SecretData = serialize($Data);
		# handle potential compression
		$this->compressor($SecretData);
		if ($this->options['cipher'] && function_exists('openssl_encrypt')) {
			# Get the size of the appropriate local initialization vector
			$ivsz = openssl_cipher_iv_length($this->options['cipher']);
			# Generate an initialization vector
			$iv = random_bytes($ivsz);
			# Perform encryption
			$SecretData = openssl_encrypt($SecretData, $this->options['cipher'], $key, OPENSSL_RAW_DATA, $iv);
			# Prepend the IV to the data stream, CBC tamper protected
			$SecretData = $iv . $SecretData;
		}
		# Return the scrambled data stream
		return $this->Scramble($SecretData, $key);
	}

	public function Decrypt(&$SecretData, $WeakKey)
	{
		# Regenerate hardened key from weak key
		$key = $this->hardenKey($WeakKey);
		# Descramble data
		$data = $this->Descramble($SecretData, $key);
		if ($this->options['cipher'] && function_exists('openssl_decrypt')) {
			# Get the size of the appropriate local initialization vector
			$ivsz = openssl_cipher_iv_length($this->options['cipher']);
			# Recover the initialization vector
			$iv = substr($data, 0, $ivsz);
			# Recover the data block
			$data = substr($data, $ivsz);
			# Perform decryption
			$data = openssl_decrypt($data, $this->options['cipher'], $key, OPENSSL_RAW_DATA, $iv);
		}
		# handle potential decompression
		$this->compressor($data, true);
		# Convert data from a serialized string and return
		return unserialize($data);
	}
}

if (!function_exists('openssl_decrypt')) {
	trigger_error('openssl not installed');
}
