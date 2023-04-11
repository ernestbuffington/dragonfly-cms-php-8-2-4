<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	NOTE: It is worth noting that the mail() function is not suitable for
	      larger volumes of email in a loop. This function opens and closes
	      a SMTP socket for each email, which is not very efficient.
*/

namespace Poodle\Mail\Send;

class PHP extends \Poodle\Mail\Send
{

	# Sends mail using the PHP mail() function.
	public function send()
	{
		$to = array();
		foreach ($this->recipients['To'] as $recipient) {
			$to[] = empty($recipient->name)
				? $recipient->address
				: $this->encodeHeader('', $recipient->name, true) . " <{$recipient->address}>";
		}
		$params = '';
		if (isset($this->sender)) {
			$old_from = \Poodle\PHP\INI::set('sendmail_from', $this->sender->address);
			$params = '-oi -f '.escapeshellarg($this->sender->address);
		}
		$this->prepare($header, $body, self::HEADER_ADD_BCC | self::HEADER_NO_SUBJECT);
		if (empty($body)) { return false; }

		$rt = mail(implode(', ', $to), $this->encodeHeader('', $this->subject), str_replace("\r\n","\n",$body), $header, $params);

		if (!empty($old_from)) {
			\Poodle\PHP\INI::set('sendmail_from', $old_from);
		}
		if (!$rt) {
			throw new \Exception($this->l10n('PHP mail() function failed'), E_USER_ERROR);
		}
		return true;
	}

	public function close() {}

}
