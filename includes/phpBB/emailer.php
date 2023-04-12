<?php
/***************************************************************************
								emailer.php
							 -------------------
	begin				 : Sunday Aug. 12, 2001
	copyright			 : (C) 2001 The phpBB Group
	email				 : support@phpbb.com

	Modifications made by CPG Dev Team http://cpgnuke.com
	Last modification notes:

	$Id: emailer.php,v 9.7 2006/01/06 13:28:44 djmaze Exp $

***************************************************************************/

/***************************************************************************
 *
 *	 This program is free software; you can redistribute it and/or modify
 *	 it under the terms of the GNU General Public License as published by
 *	 the Free Software Foundation; either version 2 of the License, or
 *	 (at your option) any later version.
 *
 ***************************************************************************/

if (!defined('IN_PHPBB')) { exit; }

//
// The emailer class has support for attaching files, that isn't implemented
// in the 2.0 release but we can probable find some way of using it in a future
// release
//

class emailer
{
	var $msg, $subject, $extra_headers;
	var $addresses, $reply_to, $from;
	var $use_smtp;

	var $tpl_msg = array();

	function emailer()
	{
		global $MAIN_CFG;
		$this->reset();
		$this->use_smtp = $MAIN_CFG['email']['smtp_on'];
		$this->reply_to = $this->from = '';
	}

	// Resets all the data (address, template file, etc etc to default
	function reset()
	{
		$this->addresses = array();
		$this->vars = $this->msg = $this->extra_headers = '';
	}

	// Sets an email address to send to
	function email_address($address)
	{
		$this->addresses['to'] = trim($address);
	}

	function cc($address)
	{
		$this->addresses['cc'][] = trim($address);
	}

	function bcc($address)
	{
		$this->addresses['bcc'][] = trim($address);
	}

	function replyto($address)
	{
		$this->reply_to = trim($address);
	}

	function from($address)
	{
		$this->from = trim($address);
	}

	// set up subject for mail
	function set_subject($subject = '')
	{
		$this->subject = trim(preg_replace('#[\r]+#s', '', $subject));
	}

	// set up extra mail headers
	function extra_headers($headers)
	{
		$this->extra_headers .= trim($headers);
	}

	function use_template($template_file, $template_lang = '')
	{
		global $board_config, $phpbb_root_path;

		if (trim($template_file) == '')
		{
			message_die(GENERAL_ERROR, 'No template file set', '', __LINE__, __FILE__);
		}

		if (trim($template_lang) == '')
		{
			$template_lang = $board_config['default_lang'];
		}

		if (empty($this->tpl_msg[$template_lang.$template_file]))
		{
			$tpl_file = 'language/'.$template_lang.'/Forums/email/'.$template_file.'.tpl';

			if (!file_exists(realpath($tpl_file)))
			{
				$tpl_file = 'language/'.$board_config['default_lang'].'/Forums/email/'.$template_file.'.tpl';

				if (!file_exists(realpath($tpl_file)))
				{
					message_die(GENERAL_ERROR, 'Could not find email template file :: '.$template_file, '', __LINE__, __FILE__);
				}
			}

			if (!($fd = fopen($tpl_file, 'r')))
			{
				message_die(GENERAL_ERROR, 'Failed opening template file :: '.$tpl_file, '', __LINE__, __FILE__);
			}

			$this->tpl_msg[$template_lang.$template_file] = fread($fd, filesize($tpl_file));
			fclose($fd);
		}

		$this->msg = $this->tpl_msg[$template_lang.$template_file];

		return true;
	}

	// assign variables
	function assign_vars($vars)
	{
		$this->vars = (empty($this->vars)) ? $vars : $this->vars.$vars;
	}

	// Send the mail out to the recipients set previously in var $this->address
	function send()
	{
		global $board_config, $lang, $phpbb_root_path, $db;
		// Escape all quotes, else the eval will fail.
		$this->msg = str_replace ("'", "\'", $this->msg);
		$this->msg = preg_replace('#\{([a-z0-9\-_]*?)\}#is', "'.$\\1.'", $this->msg);
		// Set vars
		foreach ($this->vars AS $key => $val) { $$key = $val; }
		eval("\$this->msg = '$this->msg';");
		// Clear vars
		foreach ($this->vars AS $key => $val) { unset($$key); }

		// We now try and pull a subject from the email body ... if it exists,
		// do this here because the subject may contain a variable
		$drop_header = '';
		$match = array();
		if (preg_match('#^(Subject:(.*?))$#m', $this->msg, $match)) {
			$this->subject = (trim($match[2]) != '') ? trim($match[2]) : (($this->subject != '') ? $this->subject : 'No Subject');
			$drop_header .= '[\r\n]*?'.phpbb_preg_quote($match[1], '#');
		} else {
			$this->subject = (($this->subject != '') ? $this->subject : 'No Subject');
		}

		if (preg_match('#^(Charset:(.*?))$#m', $this->msg, $match)) {
			$this->encoding = (trim($match[2]) != '') ? trim($match[2]) : trim($lang['ENCODING']);
			$drop_header .= '[\r\n]*?'.phpbb_preg_quote($match[1], '#');
		} else {
			$this->encoding = trim($lang['ENCODING']);
		}

		if ($drop_header != '') {
			$this->msg = trim(preg_replace('#'.$drop_header.'#s', '', $this->msg));
		}

		// use Dragonfly mailer
        if ((isset($this->addresses['cc']) && count($this->addresses['cc'])) ||
		    (isset($this->addresses['bcc']) && count($this->addresses['bcc']))) {
			$to = array(); // bcc array($to_email => $to_name);
			if ($this->addresses['to']) $to[$this->addresses['to']] = '';
			if (isset($this->addresses['cc']) && count($this->addresses['cc'])) {
				foreach($this->addresses['cc'] as $cc) { $to[$cc] = '';	}
			}
			if (isset($this->addresses['bcc']) && count($this->addresses['bcc'])) {
				foreach($this->addresses['bcc'] as $cc) { $to[$cc] = '';	}
			}
		} else {
			$to = $this->addresses['to'];
		}
		$email_headers = (empty($this->extra_headers) ? false : explode("\n", $this->extra_headers));
		$to_name = $from_name = $mailer_message = '';
		$result = send_mail($mailer_message, $this->msg, false, $this->subject, $to, $to_name, $this->from, $from_name, $email_headers);

		// Did it work?
		if (!$result) {
			message_die(GENERAL_ERROR, 'Failed sending email :: '.(($this->use_smtp) ? 'SMTP' : 'PHP').' :: '.$mailer_message, '', __LINE__, __FILE__);
		}
		return true;
	}

} // class emailer
