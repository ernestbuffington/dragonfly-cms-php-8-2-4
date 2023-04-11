<?php

$LNG = array(
	'Command' => 'Command',
	'Site name' => 'Site name',
	'Encoding' => 'Encoding',
	'Incoming' => 'Incoming',
	'Outgoing' => 'Outgoing',
	'Default bounce email address' => 'Default bounce email address',
	'Default sender email address' => 'Default sender email address',
	'Email_bounce_info' => 'Emails that failed delivery will be returned to this email address.',
	'SMTP server mode (preferred)' => 'SMTP server mode (preferred)',
	'Encryption' => 'Encryption',
	'Port' => 'Port',
	'Authentication' => 'Authentication',

	'_mail' => array(
		'You must provide at least one recipient email address' => 'You must provide at least one recipient email address',
		'Unknown encoding %s' => 'Unknown encoding %s',
		// PHP
		'PHP mail() function failed' => 'Could not instantiate mail() function.',
		// Sendmail
		'Failed to execute: %s' => 'Failed to execute: %s',
		// SMTP
		'recipients_failed' => 'SMTP Error: The following recipients failed: ',
		'data_not_accepted' => 'SMTP Error: Data not accepted.',
		'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
		# SMTP SUCCESS CODES
		211 => 'System status, or system help reply.',
		214 => 'Help message.',
		220 => 'Domain service ready. Ready to start TLS.',
		221 => 'Domain service closing transmission channel.',
		250 => 'OK, queuing for node node started. Requested mail action okay, completed.',
		251 => 'OK, no messages waiting for node node. User not local, will forward to forwardpath.',
		252 => 'OK, pending messages for node node started. Cannot VRFY user (e.g., info is not local), but will take message for this user and attempt delivery.',
		253 => 'OK, messages pending messages for node node started.',
		# SMTP INTERMEDIATE CODES
		354 => 'Start mail input; end with &lt;CRLF&gt;.&lt;CRLF&gt;.',
		355 => 'Octet-offset is the transaction offset.',
		# SMTP ERROR CODES
		421 => 'Domain service not available, closing transmission channel.',
		# SMTP FAILURE CODES
		432 => 'A password transition is needed.',
		450 => 'Requested mail action not taken: mailbox unavailable. ATRN request refused.',
		451 => 'Requested action aborted: local error in processing. Unable to process ATRN request now',
		452 => 'Requested action not taken: insufficient system storage.',
		453 => 'You have no mail.',
		454 => 'TLS not available due to temporary reason. Encryption required for requested authentication mechanism.',
		458 => 'Unable to queue messages for node node.',
		459 => 'Node node not allowed: reason.',
		# SMTP ERROR CODES
		500 => 'Command not recognized: command. Syntax error.',
		501 => 'Syntax error, no parameters allowed.',
		502 => 'Command not implemented.',
		503 => 'Bad sequence of commands.',
		504 => 'Command parameter not implemented.',
		521 => 'Machine does not accept mail.',
		# SMTP FAILURE CODES
		530 => 'Must issue a STARTTLS command first. Encryption required for requested authentication mechanism.',
		533 => 'AUTH command is not enabled.',
		534 => 'Authentication mechanism is too weak.',
		538 => 'Encryption required for requested authentication mechanism.',
		550 => 'Requested action not taken: mailbox unavailable.',
		551 => 'User not local; please try forwardpath.',
		552 => 'Requested mail action aborted: exceeded storage allocation.',
		553 => 'Requested action not taken: mailbox name not allowed.',
		554 => 'Transaction failed.'
	)
);
