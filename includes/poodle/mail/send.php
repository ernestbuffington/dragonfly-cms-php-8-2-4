<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	NOTE: The mailer works according to RFC 2822 which supersedes RFC 822
		This also means that LineEndings are CR+LF (\r\n)
		It does not support RFC 6854
		https://tools.ietf.org/html/rfc5322 Internet Message Format
	Additional:
		https://tools.ietf.org/html/rfc8098 Message Disposition Notification

	Thanks to: Brent R. Matzelle to give me the idea about attachments as he used in PHPMailer http://phpmailer.sourceforge.net/
*/

namespace Poodle\Mail;

abstract class Send extends \Poodle\TPL implements \ArrayAccess
{
	public const
		HEADER_ADD_TO = 1,
		HEADER_ADD_BCC = 2,
		HEADER_NO_SUBJECT = 4;

	public
		$DTD  = 'html4-loose',

		$charset = 'utf-8',

		$body       = '', // The message itself.  This can be either an HTML or text body.
		$body_plain = '', // text-only version of the message.  This automatically sets the email to multipart/alternative.

		$msg_id   = null,    // Set when using SMTP mode
		$bad_rcpt = array(), // Failed recipients

		$tpl_layout = 'email';

/*
 * 3.6. Field definitions
	protected $headers = array(
		'orig-date'   => '', // REQUIRED
		'from'        => '', // REQUIRED = mailbox-list
		'sender'      => '', // REQUIRED when multi-address from
		'reply-to'    => '', // Optional = address-list
		'to'          => '', // Optional
		'cc'          => '', // Optional
		'bcc'         => '', // Optional
		'message-id'  => '', // SHOULD be present - see 3.6.4
		'in-reply-to' => '', // SHOULD occur in some replies - see 3.6.4
		'references'  => '', // SHOULD occur in some replies - see 3.6.4
		'subject'     => '', // Optional
		'comments'    => '', // unlimited
		'keywords'    => '', // unlimited
		'optional-field' => '' // unlimited
	);
*/

	protected static
		// Microsoft Outlook mail priority
		$MS_Priorities = array(1=>'Highest', 'High', 'Normal', 'Low', 'Lowest');

	protected
		$from,             // Addresses, (multiple) Sender email address + name.
		$sender,           // Address,   Sender email address + name.
		$reply_to,         // Addresses
		$notify_to,        // Addresses, Email address(es) where a reading notification will be sent to.

		$encoding    = '8bit', // Encoding of the message (8bit, base64 or quoted-printable).
		$priority    = 3,      // Email priority (1 = High, 3 = Normal, 5 = low).
		$subject     = '',     // Subject of the message.
		$wordwrap    = 0,      // Sets word wrapping on the body of the message to a given number of characters.

		$recipients,       // ['To'] = Addresses, ['Cc'] = Addresses, ['Bcc'] = Addresses,
		$attachments = array(),
		$CHeaders    = array(), // Custom headers
		$boundary    = array(),
		$content_type, // Content-type of the message.

		$errno = null,
		$error = null,

		$cfg = '';

	function __construct($cfg='', $html=true)
	{
		parent::__construct();
		$K = \Poodle::getKernel();
		$this->clear();
		$this->from = new Addresses();
		$this->from->append('noreply@'.$K->host);
		$this->tpl_type = $html ? 'html' : 'txt';
		$this->cfg = $cfg;
		if ($K->CFG) {
			$this->setFrom($K->CFG->mail->from ?: "noreply@{$K->host}", $K->CFG->global->sitename);
			if (!empty($K->CFG->output->template)) {
				$this->tpl_path = "tpl/{$K->CFG->output->template}/";
			}
			if (!empty($K->CFG->mail->encoding)) {
//				$this->__set('encoding', $K->CFG->mail->encoding);
			}
			if (!empty($K->CFG->mail->return_path)) {
				$this->setSender($K->CFG->mail->return_path);
			}
		}
	}

	function __get($k)
	{
		if ('sender' === $k) {
			return $this->sender ?: clone $this->from[0];
//			if (count($this->from) > 1) return $this->sender;
		}
		if (isset($this->$k)) { return $this->$k; }
		return parent::__get($k);
	}

	function __isset($k)
	{
		return isset($this->$k);
	}

	function __set($k, $v)
	{
		switch ($k)
		{
		case 'encoding':   $this->$k = self::validate_encoding($v); break;
		case 'priority':   $this->$k = min(5, max(1, (int)$v,1)); break;
		case 'subject':    $this->$k = \Poodle\Mail::removeCRLF($v); break;
		case 'body':       $this->$k = $v; break;
		case 'wordwrap':   $this->$k = min(998, max(0, (int)$v)); break; # RFC 2822: 2.3
		default:
			if (property_exists($this, $k)) {
				trigger_error('Failed to set \Poodle\Mail\Send.'.$k);
			} else {
				$this->$k = $v;
			}
		}
	}

	protected function validate_encoding($encoding)
	{
		$encoding = strtolower($encoding);
		switch ($encoding)
		{
		case 'base64':
		case '7bit':
		case '8bit':
		case 'binary':
		case 'quoted-printable':
			return $encoding;
		default:
			throw new \Exception(sprintf(self::l10n('Unknown encoding %s'), $encoding), E_USER_ERROR);
		}
	}

	public static function getConfigOptions($cfg) { return array(); }
	public static function getConfigAsString($data) { return false; }
	abstract public function send();
	abstract public function close();

	# Adds a custom header.
	public function addHeader($headers)
	{
		if (empty($headers)) return;
		if (is_array($headers)) {
			foreach ($headers as $header) { $this->CHeaders[] = explode(':', $header, 2); }
		} else {
			$this->CHeaders[] = explode(':', $headers, 2);
		}
	}

	public function setPriority($priority) { self::__set('priority', $priority); }
	public function setSubject($subject) { self::__set('subject', $subject); }
	public function setBody($body) { self::__set('body', $body); }

	public function setFrom($address, $name='')
	{
		if ($address instanceof Address) {
			$this->from[0] = $address;
		} else {
			$this->from[0]->address = $address;
			$this->from[0]->name    = $name;
		}
	}

	/**
	 * Specifies the mailbox of the agent responsible for the actual transmission of the message
	 * Bounced non-delivery reports will be send to this Return-Path
	 */
	public function setSender($address, $name='')
	{
		$this->sender = new Address($address, $name);
	}

	### RECIPIENT METHODS ###

	public function addReplyTo($address, $name='')
	{
		$this->reply_to->append($address, $name);
	}

	public function addTo($address, $name='') { self::add_addr($this->recipients['To'], $address, $name); }
	# CC & BCC work with SMTP mailer on win32, not with the mail() mailer.
	public function addCC($address, $name='')  { self::add_addr($this->recipients['Cc'], $address, $name); }
	public function addBCC($address, $name='') { self::add_addr($this->recipients['Bcc'], $address, $name); }
	protected function add_addr(Addresses $array, $address, $name)
	{
		if (is_array($address)) {
			foreach ($address as $email => $name) {
				$array->append($email, $name);
			}
		} else {
			$array->append($address, $name);
		}
	}

	### MAIL PREPAIR METHOD ###

	# Creates message and assigns Mailer. If the message is not sent
	# successfully then it returns false.
	public function prepare(&$header, &$body, $flags=0)
	{
		$count_to = is_countable($this->recipients['To']) ? count($this->recipients['To']) : 0;
		$count_cc = is_countable($this->recipients['Cc']) ? count($this->recipients['Cc']) : 0;
		$count_bcc = is_countable($this->recipients['Bcc']) ? count($this->recipients['Bcc']) : 0;
		if ($count_to+$count_cc+$count_bcc < 1) {
			throw new \Exception(self::l10n('You must provide at least one recipient email address'), E_USER_ERROR);
		}

		// Wrap body inside layout
		if ('text/plain' !== $this->content_type
		 && false === strpos($this->body,'<html')
		 && preg_match('/<[a-z]+.*>/',$this->body))
		{
			$this->body = $this->toString('layouts/'.$this->tpl_layout);
		}

		# Set the message type.
		$msg_type = 0; // bitwise: 0=plain|html, 1=plain+html, 2=attachments
		if ('html' === $this->tpl_type && preg_match('#<html[^>]*>.*<body[^>]*>.*</body>.*</html>#si', $this->body))
		{
			$this->content_type = 'text/html';

			$fix = new FixBody();
			$html = $fix->HTML($this->body);
			if ($html) {
				$this->body = $html;
			} else {
				// Fixing html for email failed
				print_r($fix->errors);
				exit(htmlspecialchars($this->body));
				// trigger_error($fix->errors[0]);
			}

			// Make plain/text version
			if (!$this->body_plain) {
				$this->body_plain = $fix->HTMLToText($this->body);
			}
		}
		else
		{
			$this->content_type = 'text/plain';
		}

		if ($this->body_plain)
		{
			$msg_type |= 1;
			$this->content_type = 'multipart/alternative';
		}
		if (!empty($this->attachments)) {
			$msg_type |= 2;
		}

		### Create Header ###

		# Set the boundaries
		$uniq_id = md5(uniqid(random_int(0, mt_getrandmax()), true));
		$this->boundary = array('a1_'.$uniq_id, 'a2_'.$uniq_id);
		$header = array(
			'Date: '.gmdate('r'),
			'From: '.$this->from->asEncodedString(),
		);
		if (is_countable($this->reply_to) ? count($this->reply_to) : 0) {
			$header[] = 'Reply-to: '.$this->reply_to->asEncodedString();
		}
		if ($this->sender || (is_countable($this->from) ? count($this->from) : 0) > 1) {
			$header[] = 'Sender: '.$this->__get('sender')->asEncodedString();
		}
		# MAIL sets the to address itself ?
		if ($flags & self::HEADER_ADD_TO) {
			if ($count_to > 0) {
				$header[] = 'To: '.$this->recipients['To']->asEncodedString();
			} else if (!$count_cc) {
				$header[] = 'To: undisclosed-recipients:;';
			}
		}
		if ($count_cc > 0) {
			$header[] = 'Cc: '.$this->recipients['Cc']->asEncodedString();
		}
		# MAIL supports Bcc and extracts from the header before sending ?
		if (($flags & self::HEADER_ADD_BCC) && $count_bcc > 0) {
			$header[] = 'Bcc: '.$this->recipients['Bcc']->asEncodedString();
		}
		# MAIL sets the subject itself ?
		if (!($flags & self::HEADER_NO_SUBJECT)) {
			$header[] = $this->encodeHeader('Subject', $this->subject);
		}
		$header[] = "Message-ID: <{$uniq_id}@{$_SERVER['HTTP_HOST']}>";
		if (3 != $this->priority) {
			$header[] = "X-Priority: {$this->priority}";
			$header[] = "X-MSMail-Priority: ".self::$MS_Priorities[$this->priority];
		}
		$header[] = "X-MimeOLE: Produced By Poodle WCMS";
		$header[] = "X-Mailer: Poodle Mailer";
		$header[] = "X-Remote-Address: {$_SERVER["REMOTE_ADDR"]}";
		if (\Poodle\PHP\INI::get('mail.add_x_header')) {
			$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$header[] = "X-PHP-Originating-Script: ".fileowner($bt[1]['file']).":".basename($bt[1]['file']);
		}
		if (is_countable($this->notify_to) ? count($this->notify_to) : 0) {
			$header[] = "Disposition-Notification-To: {$this->notify_to}";
		}
		# Add custom headers
		foreach ($this->CHeaders as $ch) {
			$ch = trim($this->encodeHeader($ch[0], $ch[1]));
			if ($ch) { $header[] = $ch; }
		}
		unset($count_to, $count_cc, $count_bcc);
		$header[] = 'MIME-Version: 1.0';
		switch ($msg_type)
		{
			# plain | html
			case 0:
				$header[] = 'Content-Transfer-Encoding: '.$this->encoding;
				$header[] = 'Content-Type: '.$this->content_type.'; charset="'.$this->charset.'"';
				break;
			# plain & html
			case 1:
				$header[] = 'Content-Type: multipart/alternative;';
				$header[] = "\tboundary=\"".$this->boundary[0].'"';
				break;
			# + attachments
			case 2:
			case 3:
				if ($this->has_inline_image()) {
					$header[] = 'Content-Type: multipart/related;';
					$header[] = "\ttype=\"text/html\";";
				} else {
					$header[] = 'Content-Type: multipart/mixed;';
				}
				$header[] = "\tboundary=\"".$this->boundary[0].'"';
				break;
		}
		$header = implode("\r\n", $header);

		### Create Body ###

		// Stay Windows OS compatible
		$this->body = preg_replace('/\\R/', "\r\n", $this->body);
		$this->body_plain = preg_replace('/\\R/', "\r\n", $this->body_plain);

		$body = array();
		switch ($msg_type)
		{
			case 0:
				$body[] = $this->encodeBody($this->body);
				break;
			case 1:
				# Create text body
				$body[] = $this->getBoundary($this->boundary[0], 'text/plain');
				$body[] = $this->encodeBody($this->body_plain)."\r\n";
				# Create the HTML body
				$body[] = $this->getBoundary($this->boundary[0], 'text/html');
				$body[] = $this->encodeBody($this->body)."\r\n";
				$body[] = '--'.$this->boundary[0].'--';
				break;
			case 2:
				$body[] = $this->getBoundary($this->boundary[0]);
				$body[] = $this->encodeBody($this->body)."\r\n";
				$body[] = $this->attach_all();
				break;
			case 3:
				$body[] = '--'.$this->boundary[0];
				$body[] = sprintf("Content-Type: multipart/alternative;\r\n\tboundary=\"%s\"\r\n", $this->boundary[1]);
				# Create text body
				$body[] = $this->getBoundary($this->boundary[1], 'text/plain');
				$body[] = $this->encodeBody($this->body_plain)."\r\n";
				# Create the HTML body
				$body[] = $this->getBoundary($this->boundary[1], 'text/html');
				$body[] = $this->encodeBody($this->body)."\r\n";
				$body[] = '--'.$this->boundary[1].'--';
				$body[] = $this->attach_all();
				break;
		}
		$body = implode("\r\n", $body);
	}

	### MESSAGE CREATION METHODS ###

	# Returns the start of a message boundary.
	protected function getBoundary($boundary, $contentType='')
	{
		if (!$contentType) { $contentType = $this->content_type; }
		$result = "--{$boundary}\r\n";
		$result .= "Content-Transfer-Encoding: {$this->encoding}\r\n";
		return $result . "Content-Type: {$contentType}; charset=\"{$this->charset}\"\r\n";
	}

	### ATTACHMENT METHODS ###

	/**
	 * Adds an attachment from a path on the filesystem.
	 * Throws and exception if the file could not be found or accessed.
	 * @param string $file Path to the attachment.
	 * @param string $name Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 */
	public function addAttachment($file, $name = '', $encoding = '', $type = '')
	{
		$attachment = new Attachment\File($this, $file, $type);
		if ($encoding) { $attachment->encoding = $encoding; }
		if ($name)     { $attachment->name     = $name; }
		$this->attachments[] = $attachment;
	}

	/**
	 * Adds a string or binary attachment (non-filesystem) to the list.
	 * This method can be used to attach ascii or binary data,
	 * such as a BLOB record from a database.
	 * @param string $string String attachment data.
	 * @param string $filename Name of the attachment.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 * @return void
	 */
	public function addDataAttachment($string, $filename, $encoding = '', $type = '')
	{
		$attachment = new Attachment\Data($this, $string, $filename, $type);
		if ($encoding) { $attachment->encoding = $encoding; }
		$this->attachments[] = $attachment;
	}

	/**
	 * Adds an embedded attachment.  This can include images, sounds, and
	 * just about any other document.
	 * @param string $file Path to the attachment.
	 * @param string $cid Content ID of the attachment.  Use this to identify
	 *        the Id for accessing the image in an HTML form.
	 * @param string $name Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 */
	public function addEmbeddedImage($file, $cid, $name = '', $encoding = '', $type = '')
	{
		$attachment = new Attachment\File($this, $file, $type);
		if ($encoding) { $attachment->encoding = $encoding; }
		if ($name)     { $attachment->name     = $name; }
		$attachment->disposition = 'inline';
		$attachment->id          = $cid;
		$this->attachments[] = $attachment;
	}

	# Returns true if an inline attachment is present.
	protected function has_inline_image()
	{
		foreach ($this->attachments as $attachment) {
			if ('inline' === $attachment->disposition) { return true; }
		}
		return false;
	}

	# Attaches all fs, string, and binary attachments to the message. Returns an empty string on failure.
	protected function attach_all()
	{
		# Return text of body
		$mime = '';
		foreach ($this->attachments as $i => $attachment) {
			$mime .= '--'.$this->boundary[0]."\r\n".$attachment->__toString();
			# cleanup memory?
			//$this->attachments[$i] = '';
		}
		# cleanup memory?
		//$this->clearAttachments();
		return $mime.'--'.$this->boundary[0]."--\r\n";
	}

	### ENCODE METHODS ###

	/**
	 * http://www.faqs.org/rfcs/rfc2822.html 3.1
	 * Lines of characters in the body MUST be limited to 998 characters,
	 * and SHOULD be limited to 78 characters, excluding the CRLF.
	 * http://www.faqs.org/rfcs/rfc2045.html
	 * "7bit data" refers to data that is all represented as relatively short lines with 998 octets or less
	 * "8bit data" refers to data that is all represented as relatively short lines with 998 octets or less
	 * "Binary data" refers to data where any sequence of octets whatsoever is allowed.
	 */
	public function encodeBody($str, $encoding='', $lineLength = 76)
	{
		if (empty($encoding)) { $encoding = $this->encoding; }
		$encoding = self::validate_encoding($encoding);
		switch (strtolower($encoding))
		{
		case 'base64':
			return chunk_split(base64_encode($str), $lineLength, "\r\n");

		case '7bit':
		case '8bit':
			$length = max($lineLength, min(998, $this->wordwrap ?: 998));
			$str = preg_split('/\\R/', $str);
			foreach ($str as &$l) {
				$l = wordwrap($l, $length, "\r\n", true);
			}
			return implode("\r\n",$str);
//			return preg_replace("#(.{1,{$length}})(?:[ \r\n]+)|(.{1,{$length}})#","$1$2\r\n", $str);

		case 'binary':
			return $str;

		case 'quoted-printable':
			$str = preg_replace('#\\R#', "\r\n", $str);
			if (function_exists('quoted_printable_encode')) {
				return quoted_printable_encode($str);
			}
			if (function_exists('imap_8bit')) {
				return imap_8bit($str);
			}
			if (in_array('convert.*', stream_get_filters())) {
				$fp = fopen('php://temp', 'r+');
				stream_filter_append($fp,
					'convert.quoted-printable-encode',
					STREAM_FILTER_READ,
					array(
						'line-length' => $lineLength,
						'line-break-chars' => "\r\n"
					)
				);
				fwrite($fp, $str);
				rewind($fp);
				$str = stream_get_contents($fp);
				fclose($fp);
				return preg_replace('/^\\./m', '=2E', $str); # Encode . if it is first char on a line
			}
			throw new \Exception('quoted-printable encode failed');
		}
	}

	# Encode a header string to best of Q, B, quoted or none.
	protected function encodeHeader($name, $value, $phrase=false)
	{
		return \Poodle\Mail::encodeHeader($name, $value, $phrase, 'Q', $this->charset);
	}

	### MESSAGE RESET METHODS ###

	public function clear()
	{
		$this->attachments = $this->CHeaders = array();
		$this->clearNotifyTo();
		$this->clearRecipients();
		$this->clearReplyTo();
	}
	public function clearTo() { $this->recipients['To']  = new Addresses(); }
	public function clearCC() { $this->recipients['Cc']  = new Addresses(); }
	public function clearBCC(){ $this->recipients['Bcc'] = new Addresses(); }
	public function clearNotifyTo() { $this->notify_to = new Addresses(); }
	public function clearReplyTo()  { $this->reply_to  = new Addresses(); }
	public function clearRecipients() {
		$this->recipients = array(
			'To'  => new Addresses(),
			'Cc'  => new Addresses(),
			'Bcc' => new Addresses(),
		);
	}
	public function clearAttachments() { $this->attachments = array(); }
	public function clearCustomHeaders() { $this->CHeaders = array(); }

	### MISCELLANEOUS METHODS ###

	# Returns a message in the appropriate language.
	protected function l10n($key)
	{
		$K = \Poodle::getKernel();
		$K->L10N->load('poodle_mail');
		return $K->L10N['_mail'][$key] ?? 'MAIL Language string failed to load: '.$key;
	}

	# ArrayAccess
	public function offsetExists($k)  { return array_key_exists(strtolower($k), $this->headers); }
	public function offsetGet($k)     { return $this->headers[strtolower($k)]; }
	public function offsetSet($k, $v) {}
	public function offsetUnset($k)   {}
}
