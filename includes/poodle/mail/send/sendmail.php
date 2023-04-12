<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle\Mail\Send;

class Sendmail extends SMTP
{

	public static function getConfigOptions($cfg)
	{
		$cfg = explode(' ', $cfg);
		return array(
			array(
				'name'  => 'command',
				'type'  => 'text',
				'label' => 'Command',
				'value' => $cfg[0]
			),
			array(
				'name'  => 'bs',
				'type'  => 'checkbox',
				'label' => 'SMTP server mode (preferred)',
				'checked' => in_array('-bs', $cfg)
			)
		);
	}

	public static function getConfigAsString($data)
	{
		$path = $data['command'] ?: ini_get('sendmail_path');
		$path = explode(' ', $path);
		return $path[0] . (empty($data['bs']) ? ' -i -t' : ' -bs');
	}

	# Sends mail using the sendmail program.
	public function send()
	{
		$cfg = null;
  $header = null;
  $body = null;
  $command = (escapeshellcmd($this->cfg) ?: ini_get('sendmail_path')) ?: '/usr/sbin/sendmail -i -t';
		if (false !== strpos($command, ' -t')) {
			if (isset($this->sender)) {
//				$command .= ' -F ' . escapeshellarg("{$this->sender->name} <{$this->sender->address}>");
				$command .= ' -f ' . escapeshellarg($this->sender->address);
			}
			if (!$mail = popen($command, 'w')) {
				throw new \Exception(sprintf($this->l10n('Failed to execute: %s'),$cfg), E_USER_ERROR);
			}
			$this->prepare($header, $body, self::HEADER_ADD_TO | self::HEADER_ADD_BCC);
			fwrite($mail, $header."\r\n\r\n");
			fwrite($mail, $body);
			if (0 !== pclose($mail) >> 8 & 0xFF) {
				throw new \Exception(sprintf($this->l10n('Failed to execute: %s'),$cfg), E_USER_ERROR);
			}
		} else if (false !== strpos($command, ' -bs')) {
			parent::send();
		}
		return true;
	}

	protected $proc;
	protected function smtp_connect($host, $port=25, $tval=5)
	{
		$command = escapeshellcmd($this->cfg) ?: ini_get('sendmail_path');
		$this->proc = proc_open($command, array(
		   0 => array('pipe', 'r'), // stdin
		   1 => array('pipe', 'w'), // stdout
		   2 => array('pipe', 'w')  // stderr
		), $this->socket);
		stream_set_blocking($this->socket[2], 0);
		list($code, $msg) = $this->getResponse();
		if (!$code) {
			throw new \Exception($this->l10n('connect_host'), E_USER_ERROR);
		}
	}

	protected function smtp_connected()
	{
		return !!$this->proc;
	}

	protected function smtp_disconnect()
	{
		if ($this->proc) {
			proc_close($this->proc);
			$this->proc = null;
			$this->socket = null;
		}
	}

	protected function socket_write($string)
	{
		return fwrite($this->socket[0], $string);
	}

	protected function getResponse()
	{
		if ($err = stream_get_contents($this->socket[2])) {
			return array(0, $err);
		}
		$lines = array();
		while ($data = trim(fgets($this->socket[1], 1024))) {
			$lines[] = substr($data, 4);
			if (!isset($data[3]) || $data[3] === ' ') { break; }
		}
		return $lines
			? array((int)substr($data,0,3), implode("\n",$lines))
			: array(0,'No response from server');
	}

}
