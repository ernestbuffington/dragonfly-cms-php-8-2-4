<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\FTP\Adapter;

class Socket implements \Poodle\FTP\Interfaces\Adapter
{
	protected
		$passive = true,
		$features = array(),
		$buffer_size = 4096,
		$timeout = 15,
		$host,
		$socket,
		$protocol = AF_INET, // AF_INET6
		$data_socket,
		$temp_socket,

		$use_stream;

	function __destruct()
	{
		$this->disconnect();
	}

	public function connect($host, $username, $password)
	{
		$this->disconnect();

		$scheme = parse_url($host, PHP_URL_SCHEME);
		$port = parse_url($host, PHP_URL_PORT) ?: 21;
		$host = parse_url($host, PHP_URL_HOST) ?: $host;
		$tls = ('tls' === $scheme || 'ftps' === $scheme);

		$this->use_stream = ($tls || !function_exists('socket_create'));

		try {
			if ($this->use_stream) {
				if (function_exists('fsockopen')) {
					$socket = fsockopen($host, $port, $errno, $errstr, $this->timeout);
				} else {
					$socket = stream_socket_client("{$host}:{$port}", $errno, $errstr, $this->timeout);
				}
				if (!$socket) {
					throw new \Exception("Socket connect failed: {$errno} {$errstr}");
				}
				$this->socket = $socket;
			} else {
				$socket = socket_create($this->protocol, SOCK_STREAM, SOL_TCP);
				if (!$socket) {
					throw new \Exception('Creating socket failed: '.socket_strerror(socket_last_error()));
				}
				$this->socket = $socket;
				$this->setSocketTimeout($socket);
				if (!socket_connect($socket, $host, $port)) {
					throw new \Exception('Socket connect failed: '.socket_strerror(socket_last_error($socket)));
				}
			}

			do {
				$result = $this->readMessage();
				if ($result['code'] >= 400 || $result['code'] < 1) {
					throw new \Exception('FTP Error: '.$result['code'].' '.$result['message']);
				}
			} while ($result['code'] < 200);

			if ($tls) {
				$result = $this->raw('AUTH TLS');
				if (!static::checkResult($result)) {
					throw new \Exception('FTP Error AUTH TLS: '.implode("\n", $result));
				}
				// Begin encrypted connection
				if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT)) {
					throw new \Exception('FTP Error AUTH TLS failed');
				}
			}

			$result = $this->raw("USER {$username}");
			if (!static::checkResult($result)) {
				throw new \Exception('FTP Error USER: '.implode("\n", $result));
			}
			$code = static::getResultCode($result);
			if (230 !== $code) {
				if (331 === $code) {
					$result = $this->raw("PASS {$password}");
				} else {
					$result = $this->raw("ACCT {$password}");
				}
				if (!static::checkResult($result)) {
					throw new \Exception('FTP Error PASS: '.implode("\n", $result));
				}
			}

			$this->getFeatures();
		} catch (\Exception $e) {
			$this->disconnect();
			throw $e;
		}

		$this->host = $host;

		return true;
	}

	public function disconnect()
	{
		if ($this->socket) {
			if ($this->use_stream) {
				fclose($this->socket);
			} else {
				socket_close($this->socket);
			}
			$this->socket = null;
		}
	}

	public function chdir($directory)
	{
		$result = $this->raw("CWD {$directory}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error CWD: '.implode("\n", $result));
		}
		return true;
	}

	public function chmod($path, $mode)
	{
		$result = $this->raw(sprintf('SITE CHMOD %o %s', $mode, $path));
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error CHMOD: '.implode("\n", $result));
		}
		return true;
	}

	public function delete($path)
	{
		$result = $this->raw("DELE {$path}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error DELE: '.implode("\n", $result));
		}
		return true;
	}

	public function fileSize($remote_file)
	{
		if (!isset($this->features['SIZE'])) {
			throw new \Exception('SIZE not supported by server');
		}
		$result = $this->raw("SIZE {$remote_file}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error SIZE: '.implode("\n", $result));
		}
		return preg_replace('/^[0-9]{3} ([0-9]+).*$/s', '\\1', implode("\n", $result));
	}

	public function fget($handle, $remote_file, $resumepos = 0)
	{
		try {
			$this->startTransfer("RETR {$remote_file}", $handle, $resumepos);
			if ($this->use_stream) {
				do {
					$tmp = fread($this->data_socket, $this->buffer_size);
					if (false === $tmp) {
						throw new \Exception('Reading failed');
					}
					fwrite($handle, $tmp, strlen($tmp));
				} while (strlen($tmp));
			} else {
				do {
					$tmp = socket_read($this->temp_socket, $this->buffer_size, PHP_BINARY_READ);
					if (false === $tmp) {
						throw new \Exception('Reading failed: '.socket_strerror(socket_last_error($this->temp_socket)));
					}
					fwrite($handle, $tmp, strlen($tmp));
				} while (strlen($tmp));
			}
			$this->endTransfer();
		} catch (\Exception $e) {
			$this->closeTransfer();
			throw $e;
		}
		return true;
	}

	public function fput($remote_file, $handle, $startpos = 0)
	{
		try {
			$this->startTransfer("STOR {$remote_file}", $handle, $startpos);
			if ($this->use_stream) {
				while (!feof($handle)) {
					if (!fwrite($this->data_socket, fread($handle, $this->buffer_size))) {
						throw new \Exception('Writing failed');
					}
				}
			} else {
				while (!feof($handle)) {
					if (false === socket_write($this->temp_socket, fread($handle, $this->buffer_size))) {
						throw new \Exception('Writing failed: '.socket_strerror(socket_last_error($this->temp_socket)));
					}
				}
			}
			$this->endTransfer();
		} catch (\Exception $e) {
			$this->closeTransfer();
			throw $e;
		}
		return true;
	}

	public function get($local_file, $remote_file, $resumepos = 0)
	{
		$handle = fopen($local_file, 'w');
		if (!$handle) {
			throw new \Exception('Failed creating local file');
		}
		try {
			$this->fget($handle, $remote_file, $resumepos);
		} finally {
			fclose($handle);
		}
		return true;
	}

	public function put($remote_file, $local_file, $startpos = 0)
	{
		$handle = fopen($local_file, 'rb');
		if (!$handle) {
			throw new \Exception('Failed opening local file');
		}
		try {
			$this->fput($remote_file, $handle, $startpos);
		} finally {
			fclose($handle);
		}
		return true;
	}

	public function mkdir($directory)
	{
		$result = $this->raw("MKD {$directory}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error MKD: '.implode("\n", $result));
		}
		return true;
	}

	public function rename($oldname, $newname)
	{
		$result = $this->raw("RNFR {$oldname}");
		if (350 == static::getResultCode($result)) {
			$result = $this->raw("RNTO {$newname}");
		}
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error RNFR/RNTO: '.implode("\n", $result));
		}
		return true;
	}

	public function rmdir($directory)
	{
		$result = $this->raw("RMD {$directory}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error RMD: '.implode("\n", $result));
		}
		return true;
	}

	public function getSystemType()
	{
		$result = $this->raw('SYST');
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error SYST: '.implode("\n", $result));
		}
		$DATA = explode(' ', $result[0]);
		return $DATA[1];
	}

	public function getCWD()
	{
		$result = $this->raw('PWD');
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error PWD: '.implode("\n", $result));
		}
		return preg_replace('/^[0-9]{3} "(.+)".*$/s', '\\1', implode("\n", $result));
	}

	public function raw($command)
	{
		if (!$this->socket) {
			throw new \Exception('Not connected');
		}
		$result = '';
		if ($this->use_stream) {
			if (!fwrite($this->socket, "{$command}\r\n")) {
				throw new \Exception('Writing failed');
			}
		} else {
			if (false === socket_write($this->socket, "{$command}\r\n")) {
				throw new \Exception('Writing failed: '.socket_strerror(socket_last_error($this->socket)));
			}
		}
		$result = $this->readMessage();
		return preg_split('/\\R/', trim($result['message']));
	}

	public function rawlist($directory = null, $recursive = false)
	{
		return $this->_list('LIST', ' '.($directory?:$this->getCWD()));
	}

	public function scanDir($directory = null)
	{
		return $this->_list('NLST', ' '.($directory?:$this->getCWD()));
	}

	public function setPassiveMode($pasv)
	{
		$this->passive = !!$pasv;
	}

	public function exists($name)
	{
		$directory = preg_match('#^(.*/)[^/]+$#D', $name, $m) ? $m[1] : '.';
		return in_array($name, $this->scanDir($directory));
	}

	public function isDir($directory)
	{
		if (!preg_match('#^(.*/)?([^/]+)$#D', $directory, $m)) {
			throw new \Exception('Invalid directory');
		}
		$dir = $m[1];
		$name = $m[2];
		$files = $this->rawlist($m[1]);
		foreach ($files as $file) {
			if ('d' === $file[0]) {
				$file = preg_split('/\\s+/', $file);
				if ($name === $file[8][0]) {
					return true;
				}
			}
		}
		return false;
	}

	protected function getFeatures()
	{
		$result = $this->raw('FEAT');
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error FEAT: '.implode("\n", $result));
		}
		$this->features = array();
		foreach ($result as $v) {
			$v = explode(' ', trim($v));
			/*
				EPRT
				IDLE
				MDTM
				SIZE
				MFMT
				REST => STREAM
				MLST => type*;size*;sizd*;modify*;UNIX.mode*;UNIX.uid*;UNIX.gid*;unique*;
				MLSD
				AUTH => TLS
				PBSZ
				PROT
				UTF8
				TVFS
				ESTA
				PASV
				EPSV
				SPSV
				ESTP
			*/
			$this->features[$v[0]] = isset($v[1]) ? $v[1] : '';
		}
		$this->passive = isset($this->features['PASV']);
		return true;
	}

	protected function restore($from)
	{
		if (!isset($this->features['REST'])) {
			throw new \Exception('Restore not supported by server');
		}
		$result = $this->raw("REST {$from}");
		if (!static::checkResult($result)) {
			throw new \Exception('FTP Error REST: '.implode("\n", $result));
		}
		return true;
	}

	protected function setSocketTimeout($socket)
	{
		if ($this->use_stream) {
			if (!stream_set_timeout($socket, $this->timeout)) {
				throw new \Exception('Set socket send timeout failed');
			}
		} else {
			if (!socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$this->timeout, 'usec'=>0))) {
				throw new \Exception('Set socket receive timeout failed: '.socket_strerror(socket_last_error($socket)));
			}
			if (!socket_set_option($socket, SOL_SOCKET , SO_SNDTIMEO, array('sec'=>$this->timeout, 'usec'=>0))) {
				throw new \Exception('Set socket send timeout failed: '.socket_strerror(socket_last_error($socket)));
			}
		}
	}

	protected static function getResultCode($result)
	{
		return (int) substr(array_pop($result), 0, 3);
	}

	protected static function checkResult($result)
	{
		$code = static::getResultCode($result);
		return ($code > 0 && $code < 400);
	}

	protected function readMessage()
	{
		if (!$this->socket) {
			throw new \Exception('Not connected');
		}
		$message = '';
		if ($this->use_stream) {
			do {
				$tmp = fread($this->socket, 4096);
				if (false === $tmp) {
					throw new \Exception('Reading failed');
				}
				$message .= $tmp;
			} while (!preg_match('/([0-9]{3})(-.+\\1)? [^\\r\\n]+\\R$/Us', $message, $m));
		} else {
			do {
				$tmp = socket_read($this->socket, 4096, PHP_BINARY_READ);
				if (false === $tmp) {
					throw new \Exception('Reading failed: '.socket_strerror(socket_last_error($this->socket)));
				}
				$message .= $tmp;
			} while (!preg_match('/([0-9]{3})(-.+\\1)? [^\\r\\n]+\\R$/Us', $message, $m));
		}
		return array(
			'code' => (int)$m[1],
			'message' => $message,
		);
	}

	protected function _list($cmd, $arg='')
	{
		try {
			$code = $this->startTransfer($cmd.$arg);
			$out = '';
			if ($code < 200) {
				if ($this->use_stream) {
					do {
						$tmp = fread($this->data_socket, $this->buffer_size);
						if (false === $tmp) {
							throw new \Exception('Reading failed');
						}
						$out .= $tmp;
					} while (strlen($tmp));
				} else {
					do {
						$tmp = socket_read($this->temp_socket, $this->buffer_size, PHP_BINARY_READ);
						if (false === $tmp) {
							throw new \Exception('Reading failed: '.socket_strerror(socket_last_error($this->temp_socket)));
						}
						$out .= $tmp;
					} while (strlen($tmp));
				}
				$this->endTransfer();
				$out = preg_split('/\\R+/', $out, -1, PREG_SPLIT_NO_EMPTY);
			}
			return $out;
		} catch (\Exception $e) {
			$this->closeTransfer();
			throw $e;
		}
	}

	protected function startTransfer($cmd, $handle = null, $offset = 0)
	{
		$this->raw('TYPE I'); // FTP_BINARY

		try {
			if (!$this->use_stream) {
				$socket = socket_create($this->protocol, SOCK_STREAM, SOL_TCP);
				if (!$socket) {
					throw new \Exception('Creating socket failed: '.socket_strerror(socket_last_error()));
				}
				$this->data_socket = $socket;

				$this->setSocketTimeout($socket);
			}

			if ($this->passive) {
				if (isset($this->features['EPSV'])) {
					// https://tools.ietf.org/html/rfc2428
					$result = $this->raw('EPSV');
					if (!static::checkResult($result)) {
						throw new \Exception('FTP Error EPSV: '.implode("\n", $result));
					}
					// The delimiter character MUST be one of the ASCII characters in range 33-126 inclusive [\x21-\x7E].
					// The character "|" (ASCII 124) is recommended
					// |1|132.235.1.2|6275|
					// |2|1080::8:800:200C:417A|5282|
					if (!preg_match('/\\|([0-9]*)\\|([0-9\\.]*|[0-9A-Z:]*)\\|([0-9]*)\\|/si', implode("\n", $result), $m)) {
						throw new \Exception('FTP Error EPSV: '.implode("\n", $result));
					}
					$host = $m[2] ?: $this->host;
					$port = (int) $m[3];
				} else {
					$result = $this->raw('PASV');
					if (!static::checkResult($result)) {
						throw new \Exception('FTP Error PASV: '.implode("\n", $result));
					}
					$ip_port = explode(',', preg_replace('/^.+ \\(?([0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]+,[0-9]+)\\)?.*$/s', '\\1', implode("\n", $result)));
					$host = "{$ip_port[0]}.{$ip_port[1]}.{$ip_port[2]}.{$ip_port[3]}";
					$port = (((int)$ip_port[4])<<8) + ((int)$ip_port[5]);
				}
				if ($this->use_stream) {
					if (function_exists('fsockopen')) {
						$socket = fsockopen($host, $port, $errno, $errstr, $this->timeout);
					} else {
						$socket = stream_socket_client("{$host}:{$port}", $errno, $errstr, $this->timeout);
					}
					if (!$socket) {
						throw new \Exception("Socket connect failed: {$errno} {$errstr}");
					}
					$this->data_socket = $socket;
				} else {
					if (!socket_connect($socket, $host, $port)) {
						throw new \Exception('Socket connect failed: '.socket_strerror(socket_last_error($socket)));
					}
					$this->temp_socket = $socket;
				}
			} else {
				if (!socket_getsockname($this->socket, $addr, $port)) {
					throw new \Exception('Getting socket information failed: '.socket_strerror(socket_last_error($this->socket)));
				}
				if (!socket_bind($socket, $addr)) {
					throw new \Exception('Binding socket failed: '.socket_strerror(socket_last_error($socket)));
				}
				if (!socket_listen($socket)) {
					throw new \Exception('Listening to socket failed: '.socket_strerror(socket_last_error($socket)));
				}
				if (!socket_getsockname($socket, $host, $port)) {
					throw new \Exception('Getting socket information failed: '.socket_strerror(socket_last_error($socket)));
				}
				$result = $this->raw('PORT '.strtr($host.'.'.($port>>8).'.'.($port&0x00FF), '.', ','));
				if (!static::checkResult($result)) {
					throw new \Exception('FTP Error PORT: '.implode("\n", $result));
				}
			}

			if ($handle && $offset && isset($this->features['REST'])) {
				$this->restore($offset);
				fseek($handle, $offset);
			}

			$result = $this->raw($cmd);
			if (!static::checkResult($result)) {
				throw new \Exception("FTP Error {$cmd}: ".implode("\n", $result));
			}

			if (!$this->use_stream && !$this->passive) {
				$this->temp_socket = socket_accept($this->data_socket);
				if (false === $this->temp_socket) {
					throw new \Exception('Accepting socket failed: '.socket_strerror(socket_last_error($this->temp_socket)));
				}
			}

			return static::getResultCode($result);

		} catch (\Exception $e) {
			$this->closeTransfer();
			throw $e;
		}
	}

	protected function endTransfer()
	{
		$this->closeTransfer();
		$result = $this->readMessage();
		if ($result['code'] >= 400 || $result['code'] < 1) {
			throw new \Exception("FTP Error: {$result['code']} {$result['message']}");
		}
	}

	protected function closeTransfer()
	{
		if ($this->temp_socket) {
			if ($this->temp_socket != $this->data_socket) {
				socket_close($this->temp_socket);
			}
			$this->temp_socket = null;
		}
		if ($this->data_socket) {
			if ($this->use_stream) {
				fclose($this->data_socket);
			} else {
				socket_close($this->data_socket);
			}
			$this->data_socket = null;
		}
	}

}
