<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2007 by CPG-Nuke Dev Team
  http://dragonflycms.org

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version
**********************************************/

namespace Dragonfly;

abstract class Email
{

	/******************************************************************************

	  Sends a email thru PHP or SMTP using plain text or html formatted
	  bool send_mail(
		&$mailer_message: returns info about the send mail or the error message
		$message  : the message that you want to send
		$subject  : the subject of the message, default = _FEEDBACK
		$to       : emailaddress of person to send to, default = admin mailaddress
		$to_name  : name of person to send to, default = sitename
		$from     : emailaddress of person who sends the message, default = admin mailaddress
		$from_name: name of person who sends the message, default = sitename
	  )

	*******************************************************************************/
	public static function send(&$mailer_message, $subject, $message,
					   $to='', $to_name='', $from='', $from_name='')
	{
		$CFG = \Dragonfly::getKernel()->CFG;
		\Dragonfly::getKernel()->L10N->load('Contact');

		if (!$to) { $to = $CFG->global->adminmail; }
		if (!$from) { $from = $CFG->global->adminmail; }

		$MAIL = static::getMailer();
		$MAIL->setFrom($from, $from_name ?: $CFG->global->sitename);
		if (is_array($to)) {
			foreach ($to as $to_email => $to_name) {
				$MAIL->addBCC($to_email, $to_name);
			}
		} else {
			$MAIL->addTo($to, $to_name);
		}
		$MAIL->subject = $subject;
		$MAIL->body    = $message;
		try {
			if (!$MAIL->send()) {
				$mailer_message = $MAIL->error;
				return false;
			}
		} catch (\Exception $e) {
			$mailer_message = $e->getMessage();
			return false;
		}
		return true;
	}

	public static function getMailer()
	{
		$CFG = \Dragonfly::getKernel()->CFG;
		if (\Dragonfly::isDemo()) {
			$mailer = new \Poodle\Mail\Send\None();
		} else {
			switch ($CFG->email->backend)
			{
			case 'php':
				$mailer = new \Poodle\Mail\Send\PHP();
				break;

			case 'smtp':
				$uri = '//';
				if ('tls' === $CFG->email->smtp_protocol) {
					$uri = 'tls://';
				} else if ('ssl' === $CFG->email->smtp_protocol) {
					$uri = 'ssl://';
				}
				if (!empty($CFG->email->smtp_uname)) {
					$uri .= rawurlencode($CFG->email->smtp_uname).':'.rawurlencode($CFG->email->smtp_pass).'@';
				}
				if (!empty($CFG->email->smtp_auth) && !is_numeric($CFG->email->smtp_auth)) {
					$uri .= "?auth={$CFG->email->smtp_auth}";
				}
				$uri .= $CFG->email->smtphost . ':' . $CFG->email->smtp_port;
				$mailer = new \Poodle\Mail\Send\SMTP($uri);
				break;

			case 'sendmail':
				$mailer = new \Poodle\Mail\Send\Sendmail(ini_get('sendmail_path'));
				break;

			case 'sendmail_bs':
				$mailer = new \Poodle\Mail\Send\Sendmail(preg_replace('/ .+$/D', ' -bs', ini_get('sendmail_path')));
				break;

			case 'qmail':
				$mailer = new \Poodle\Mail\Send\Sendmail(ini_get('sendmail_path'));
				break;

			default:
				trigger_error('Invalid mail backend: ' . $CFG->email->backend);
				$mailer = new \Poodle\Mail\Send\PHP();
				break;
			}
		}

		$mailer->setTPLName($CFG->global->Default_Theme);
//		$mailer->setFrom(preg_replace('#^.*@#', 'noreply@', $CFG->global->adminmail), $CFG->global->sitename);

		return $mailer;
	}

}
