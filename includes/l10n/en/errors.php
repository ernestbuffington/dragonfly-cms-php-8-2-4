<?php
/*
	Dragonfly™ CMS, Copyright © since 2004
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/
if (!defined('CPG_NUKE')) { exit; }

$LNG = array(
'_SECURITY_STATUS' => array(
	301 => 'Moved Permanently',
	302 => 'Found',
	303 => 'See Other',
	304 => 'Not Modified',

	400 => 'Bad Request',
	401 => 'Unauthorized',
	402 => 'Payment Required',
	403 => 'Forbidden',
	404 => 'Not Found',
	405 => 'Method Not Allowed',
	409 => 'Conflict',
	410 => 'Gone',
	428 => 'Precondition Required',
	429 => 'Too Many Requests',
	431 => 'Request Header Fields Too Large',

	500 => 'Internal Server Error',
	503 => 'Service Unavailable',
	511 => 'Network Authentication Required',

	800 => 'Bad IP',
	801 => 'Spam url in referer header',
	802 => 'Bad user-agent',
	803 => 'Flood Protection',
),
'_SECURITY_MSG' => array(
	# Redirection
	301 => 'The URL that you requested, has been moved permanently to a new URI and any future references to this page SHOULD use the new URI.',
	302 => 'The URL that you requested, has been moved temporarily to a new URI and any future references to this page SHOULD remain.',
	303 => 'The URL that you requested, is probably found on a different URI and any future references SHOULD use the new URI.',
	# Client Errors
	400 => 'The URL that you requested, was a bad request.',
	401 => 'The URL that you requested, requires preauthorization to access.',
	402 => 'The URL that you requested, requires payment to access.',
	403 => 'Access to the URL that you requested, is forbidden.',
	404 => "The URL that you requested, could not be found. Perhaps you either mistyped the URL or we have a broken link.\n\nWe have logged this error and will correct the problem if it is a broken link.",
	405 => 'The URL that you requested, does not accept the specified request method.',
	409 => 'The request could not be completed due to a conflict with the current state of the resource.',
	410 => 'The URL that you requested, is not available anymore. Perhaps you either mistyped the URL or have followed an old link.',
	428 => '',
	429 => '',
	431 => '',
	# Server Errors
	500 => "The URL that you requested, resulted in a server configuration error. It is possible that the condition causing the problem will be gone by the time you finish reading this.\n\nWe have logged this error and will correct the problem.",
	503 => 'The URL that you requested, is temporarily unavailable.',
	511 => '',
	# Security Errors
	800 => 'You are banned from this site due to a bad ip.',
	801 => 'You are banned from this site due to a spam url in the referer header.',
	802 => 'You are banned from this site due to a bad user-agent.',
	803 => 'You are banned from this site due to ignoring our anti-flood warnings.',

	'_FLOOD' => "You are not allowed to flood our system.\nYou may view our website again after %s seconds",
	'Last_warning' => "\nThis is your last warning, next time you will be banned!"
));
