<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	SMTP is rfc 821 compliant and implements some rfc 2821 commands.
	http://networksorcery.com/enp/protocol/smtp.htm
	https://tools.ietf.org/html/rfc5321 Simple Mail Transfer Protocol
*/

namespace Poodle\Mail\Send;

class SMTP extends \Poodle\Mail\Send
{

	protected
		$socket, # the socket to the server
		$extensions;

	protected static
		$AUTH_METHODS = array('LOGIN', 'PLAIN', 'SCRAM-SHA-1', 'CRAM-MD5');

	public static function getConfigOptions($cfg)
	{
		$cfg = static::parseConfig($cfg);

		$transports = ','.implode(',',stream_get_transports());
		$encryption = array(
			array('label' => 'none (port 25)', 'value' => '', 'selected' => !$cfg['scheme'])
		);
		if (false !== strpos($transports, ',ssl')) {
			$encryption[] = array('label' => 'SSL (port 465)', 'value' => 'ssl', 'selected' => ('ssl' === $cfg['scheme']));
		}
		if (false !== strpos($transports, ',tls')) {
			$encryption[] = array('label' => 'TLS (port 587)', 'value' => 'tls', 'selected' => ('tls' === $cfg['scheme']));
		}

		$auth_options = array();
		$auth_options[] = array('label' => 'auto-detect', 'value' => '', 'selected' => ('' === $cfg['auth']));
		foreach (static::$AUTH_METHODS as $method) {
			if (\Poodle\Auth\SASL::isSupported($method)) {
				$auth_options[] = array('label' => $method, 'value' => $method, 'selected' => ($method === $cfg['auth']));
			}
		}

		return array(
			array(
				'name'  => 'host',
				'type'  => 'text',
				'label' => 'Host',
				'value' => $cfg['host']
			),
			array(
				'name'  => 'scheme',
				'type'  => 'select',
				'label' => 'Encryption',
				'options' => $encryption
			),
			array(
				'name'  => 'port',
				'type'  => 'number',
				'label' => 'Port',
				'value' => $cfg['port']
			),
			array(
				'name'  => 'auth',
				'type'  => 'select',
				'label' => 'Authentication',
				'options' => $auth_options
			),
			array(
				'name'  => 'user',
				'type'  => 'text',
				'label' => 'Login',
				'value' => $cfg['user']
			),
			array(
				'name'  => 'pass',
				'type'  => 'text',
				'label' => 'Password',
				'value' => $cfg['pass']
			),
/*
			array(
				'name'  => 'timeout',
				'type'  => 'numer',
				'label' => 'timeout',
				'value' => $cfg['timeout']
			),
			array(
				'name'  => 'ehlo',
				'type'  => 'text',
				'label' => 'HELO',
				'value' => $cfg['ehlo']
			),
*/
		);
	}

	public static function getConfigAsString($data)
	{
		$data['query'] = http_build_query(array('auth' => $data['auth']), '', '&');
		return \Poodle\URI::unparse($data);
	}

	protected static function parseConfig($config)
	{
		$cfg = parse_url($config) ?: array();
		$options = array();
		if (isset($cfg['query'])) {
			parse_str($cfg['query'], $options);
			unset($cfg['query']);
		}
		$cfg = array_merge(array(
			'scheme'  => '',
			'host'    => '',
			'port'    => '',
			'user'    => '',
			'pass'    => '',
			'timeout' => '',
			'ehlo'    => '',
			'auth'    => '', // PLAIN, LOGIN
		), $options, $cfg);
		$cfg['user'] = rawurldecode($cfg['user']);
		$cfg['pass'] = rawurldecode($cfg['pass']);
		return $cfg;
	}

	public function send()
	{
		$header = null;
  $body = null;
  # Try to make an SMTP connection when there's none
		$cfg = static::parseConfig($this->cfg);
		if ($this->smtp_connected()) {
			$this->smtp_reset();
		} else {
			if (!$cfg['host']) { $cfg['host'] = '127.0.0.1'; }
			if ('ssl' === $cfg['scheme']) {
				$cfg['host'] = 'ssl://'.$cfg['host'];
				if (!$cfg['port']) { $cfg['port'] = 465; }
			} else if (!$cfg['port']) {
				$cfg['port'] = ('tls' === $cfg['scheme']) ? 587 : 25;
			}
			if (!$cfg['timeout']) { $cfg['timeout'] = 15; }

			$this->smtp_connect($cfg['host'], $cfg['port'], $cfg['timeout']);

			if (!isset($cfg['ehlo'])) {
				$cfg['ehlo'] = $_SERVER['HTTP_HOST'];
			}
			$result = $this->smtp_ehlo($cfg['ehlo']);
			$this->extensions = explode("\n", $result);

			if ('tls' === $cfg['scheme']) {
				if (!in_array('STARTTLS', $this->extensions)) {
					throw new \Exception("SMTP Server {$cfg['host']} does not support STARTTLS");
				}
				$this->smtp_starttls();
				// We must resend EHLO after TLS negotiation
				$result = $this->smtp_ehlo($cfg['ehlo']);
				$this->extensions = explode("\n", $result);
			}

			if ('8bit' === $this->encoding && !in_array('8BITMIME', $this->extensions)) {
				$this->encoding = '7bit';
			}
			if (!in_array('SMTPUTF8', $this->extensions)) {
//				trigger_error("SMTP Server {$cfg['host']}  does not support SMTPUTF8");
//				throw new \Exception("SMTP Server {$cfg['host']} does not support SMTPUTF8");
			}

			if ($cfg['user'] && $cfg['pass']) {
				if (preg_match('/AUTH([^\\n]+)/', $result, $methods)) {
					$methods = preg_split('/[\\s=]+/', trim($methods[1]));
				} else {
					$methods = array();
				}
				if (empty($cfg['auth'])) {
					if ($methods) {
						foreach (static::$AUTH_METHODS as $method) {
							if (in_array($method, $methods) && \Poodle\Auth\SASL::isSupported($method)) {
								$cfg['auth'] = $method;
								break;
							}
						}
						if (empty($cfg['auth'])) {
							throw new \Exception("SMTP Server {$cfg['host']} does not support AUTH method(s): ".implode(',', $methods));
						}
					}
				} else if ($methods && !in_array($cfg['auth'], $methods)) {
					throw new \Exception("SMTP Server {$cfg['host']} does not support AUTH method {$cfg['auth']}, use: ".implode(' or ', $methods));
				}
				if (!empty($cfg['auth'])) {
					$this->smtp_auth($cfg['auth'], $cfg['user'], $cfg['pass']);
				}
			}
		}

		if ('smtp.office365.com' === $cfg['host']) {
			if ($this->sender) {
				$this->sender->address = $cfg['user'];
			} else {
				$this->setSender($cfg['user'], $this->from[0]->name);
			}
		}

		$this->prepare($header, $body, self::HEADER_ADD_TO);
		if (empty($body)) {
			$this->error = 'empty body';
			return false;
		}

		# Try to set sender address (translates to Return-Path)
		$this->smtp_mail($this->__get('sender')->address);

		# Try to send all recipients
		$this->bad_rcpt = array();
		foreach ($this->recipients['To'] as $recipient) {
			if (!$this->smtp_recipient($recipient->address)) {
				$this->bad_rcpt[] = $recipient->address;
			}
		}
		foreach ($this->recipients['Cc'] as $recipient) {
			if (!$this->smtp_recipient($recipient->address)) {
				$this->bad_rcpt[] = $recipient->address;
			}
		}
		foreach ($this->recipients['Bcc'] as $recipient) {
			if (!$this->smtp_recipient($recipient->address)) {
				$this->bad_rcpt[] = $recipient->address;
			}
		}
		if (is_countable($this->bad_rcpt) ? count($this->bad_rcpt) : 0) {
			throw new \Exception($this->l10n('recipients_failed').implode(', ', $this->bad_rcpt), E_USER_ERROR);
		}

		# Try to send the data and finalize
		if (!$this->smtp_data($header . "\r\n\r\n" . $body)) {
			throw new \Exception($this->l10n('data_not_accepted'), E_USER_ERROR);
		}

		return true;
	}

	# Close the active SMTP session if one exists.
	public function close()
	{
		if ($this->smtp_connected()) {
			$this->smtp_reset();
			$this->smtp_quit();
		}
	}

	public function getSMTPExtensions()
	{
		return $this->extensions;
	}

	### CONNECTION FUNCTIONS ###

	protected function smtp_connect($host, $port=25, $tval=5)
	{
		if (587 === $port) {
			$options = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
			$this->socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $tval, STREAM_CLIENT_CONNECT, stream_context_create($options));
		} else {
			$this->socket = fsockopen($host, $port, $errno, $errstr, $tval);
		}
		if (!$this->socket) {
			throw new \Exception($errstr?:error_get_last()['message'], $errno);
		}
		if (!WINDOWS_OS) {
			stream_set_timeout($this->socket, $tval, 0);
		}
		list($code, $msg) = $this->getResponse();
		if (!$code) {
			throw new \Exception($this->l10n('connect_host'), E_USER_ERROR);
		}
	}

	protected function smtp_disconnect()
	{
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = 0;
		}
	}

	protected function smtp_command($cmd, $error_msg=null, $ok=250)
	{
		# See mail/l10n/en.php for all error codes
		$this->socket_write("{$cmd}\r\n");
		list($code, $msg) = $this->getResponse();
		$args = func_get_args();
		$args[] = $ok;
		if (!in_array($code, $args)) {
			$this->errno = $code;
			$this->error = $msg;
			\Poodle\Log::error('SMTP '.$code, "{$msg}\n\nCommand: {$cmd}");
			if ($error_msg) {
				throw new \Poodle\Mail\Exception($error_msg.' not accepted by server', $code, $msg);
			}
			return false;
		}
		return $msg;
	}

	protected function smtp_write($line)
	{
		if (strlen($line) && '.' === $line[0]) { $line = '.'.$line; }
		$this->socket_write($line."\r\n");
	}

	protected function socket_write($string)
	{
		return fwrite($this->socket, $string);
	}

	protected function smtp_connected()
	{
		if ($this->socket) {
			$status = socket_get_status($this->socket);
			if (!$status['eof']) { return true; }
			# hmm this is an odd situation... the socket is
			# valid but we aren't connected anymore
			$this->smtp_disconnect();
		}
		return false;
	}

	### SMTP COMMANDS ###

	protected function smtp_data($data)
	{
/*
		$this->smtp_command("SIZE=$numberofbytes");
*/
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_data() called without being connected', 0);
		}
		$this->smtp_command('DATA', 'DATA command', 354);
		# Cool, the server is ready to accept data!
		# Now normalize the line breaks so we know the explode works
		$data = str_replace("\r\n","\n",$data);
		$data = str_replace("\r","\n",$data);
		$data = explode("\n",$data);

		# according to rfc 821 we should not send more than 1000 characters
		# on a single line (including the CRLF), so we will break the data up
		# into lines by \r and/or \n then if needed we will break
		# each of those into smaller lines to fit within the limit.

		# we need to find a good way to determine if headers are
		# in the msg_data or if it is a straight msg body
		# currently I'm assuming rfc 822 definitions of msg headers
		# and if the first field of the first line (':' seperated)
		# does not contain a space then it _should_ be a header
		# and we can process all lines before a blank "" line as
		# headers.
		$field = substr($data[0], 0, strpos($data[0],':'));
		$in_headers = (!empty($field) && !strstr($field,' '));
		foreach ($data as $line)
		{
			if ($in_headers && '' === $line) { $in_headers = false; }
			# Check to break this line up into smaller lines
			while (strlen($line) > 998)
			{
				$pos = strrpos(substr($line,0,998),' ');
				if (!$pos) { $pos = 997; }
				$this->smtp_write(substr($line,0,$pos));
				$line = substr($line,$pos+1);
				# if we are processing headers we need to add a LWSP-char to
				# the front of the new line rfc 822 on long msg headers
				if ($in_headers) { $line = "\t{$line}"; }
			}
			$this->smtp_write($line);
		}
		/**
		 * All the message data has been sent so lets end it
		 * Data end responses could be:
		 *
		 *     250 Ok: queued as 7523C1BEFA
		 *     250 ok 1215687857 qp 1544
		 *     250 2.0.0 n54CEgvI018278 Message accepted for delivery
		 */
		$msg = $this->smtp_command("\r\n.");
		if (false !== $msg) {
			$msg = preg_replace('#\s+qp\s+#', '-', $msg);
			// postfix
			$msg = preg_replace('#^.+queued as\s#i', '', $msg);
			// exim
			$msg = preg_replace('#^.+id=#i', '', $msg);
			// sendmail
			$msg = preg_replace('#^.*\s([a-zA-Z0-9]+)\s+Message accepted.*$#Dis', '$1', $msg);

			$this->msg_id = trim($msg);
			return true;
		}
	}

	protected function smtp_mail($from)
	{
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_mail() called without being connected', 0);
		}
		$this->smtp_command('MAIL FROM:<'.$from.'>', 'MAIL');
	}

	protected function smtp_quit()
	{
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_quit() called without being connected', 0);
		}
		$this->smtp_command('QUIT', null, 250, 221);
		$this->smtp_disconnect();
	}

	protected function smtp_recipient($to)
	{
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_recipient() called without being connected', 0);
		}
		return false !== $this->smtp_command('RCPT TO:<'.$to.'>', null, 250, 251);
	}

	protected function smtp_reset()
	{
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_reset() called without being connected', 0);
		}
		$this->smtp_command('RSET', 'Reset');
	}

	protected function smtp_auth($type, $username, $password)
	{
		$SASL = \Poodle\Auth\SASL::factory($type);
		$SASL->base64 = true;
		// Start authentication
		$cmd = "AUTH {$type}";
		$result = $this->smtp_command($cmd, $cmd, 334);
		switch ($type)
		{
		// RFC 4616
		case 'PLAIN':
			$this->smtp_command($SASL->authenticate($username, $password), $cmd.' Username/Password', 235);
			break;

		case 'LOGIN':
			$result = $this->smtp_command($SASL->authenticate($username, $password, $result), $cmd.' Username', 334);
			$this->smtp_command($SASL->challenge($result), $cmd.' Password', 235);
			break;

		// RFC 2195
		case 'CRAM-MD5':
			$this->smtp_command($SASL->authenticate($username, $password, $result), $cmd, 235);
			break;

		// RFC 5802
		case 'SCRAM-SHA-1':
			$result = $this->smtp_command($SASL->authenticate($username, $password), $cmd, 234);
			$result = $this->smtp_command($SASL->challenge($result), $cmd.' Challenge', 235);
			$SASL->verify($result);
			break;
		}
	}

	protected function smtp_ehlo($host)
	{
		if (!$this->smtp_connected()) {
			throw new \Exception('smtp_ehlo() called without being connected', 0);
		}
		# if a hostname for the EHLO wasn't specified we force a default
		if (empty($host)) {
			$host = $_SERVER['SERVER_NAME'] ?: 'localhost';
		}
		# The SMTP command EHLO supersedes the earlier HELO
		$result = $this->smtp_command("EHLO {$host}", null, 250, 220);
		if (false === $result) {
			$result = $this->smtp_command("HELO {$host}", null, 250, 220);
		}
		if (false === $result) {
			throw new \Poodle\Mail\Exception('smtp_ehlo() failed', $this->errno, $this->error);
		}
		return $result;
	}

	protected function smtp_starttls()
	{
		$this->smtp_command('STARTTLS', 'STARTTLS', 220);
		// Begin encrypted connection
		if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
			if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT)) {
				throw new \Exception('smtp_starttls() failed', 0);
			}
			trigger_error('Using less secure SSL connection');
		}
	}

	protected function getResponse()
	{
		$lines = array();
		while ($data = trim(fgets($this->socket, 1024))) {
			$lines[] = substr($data, 4);
			if (!isset($data[3]) || $data[3] === ' ') { break; }
		}
		return $lines
			? array((int)substr($data,0,3), implode("\n",$lines))
			: array(0,'No response from server');
	}

}
