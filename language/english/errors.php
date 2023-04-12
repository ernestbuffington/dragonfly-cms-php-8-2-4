<?php

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

	500 => 'Internal Server Error',
	503 => 'Service Unavailable',

	800 => 'Bad IP',
	801 => 'Spam url in referer header',
	802 => 'Unknown user-agent',
	803 => 'Flood Protection'
),
'_SECURITY_MSG' => array(
	# Redirection
	301 => 'The URL that you requested, '.get_uri().', has been moved permanently to a new URI and any future references to this page SHOULD use the new URI.',
	302 => 'The URL that you requested, '.get_uri().', has been moved temporarily to a new URI and any future references to this page SHOULD remain.',
	# Client Errors
	400 => 'The URL that you requested, '.get_uri().', was a bad request.',
	401 => 'The URL that you requested, '.get_uri().', requires preauthorization to access.',
	402 => 'The URL that you requested, '.get_uri().', requires payment to access.',
	403 => 'Access to the URL that you requested, '.get_uri().', is forbidden.',
	404 => 'The URL that you requested, '.get_uri().', could not be found. Perhaps you either mistyped the URL or we have a broken link.<br /><br />We have logged this error and will correct the problem if it is a broken link.',
	# Server Errors
	500 => 'The URL that you requested, '.get_uri().', resulted in a server configuration error. It is possible that the condition causing the problem will be gone by the time you finish reading this.<br /><br />We have logged this error and will correct the problem.',
	503 => 'The URL that you requested, '.get_uri().', is temporarily unavailable.',
	# Security Errors
	800 => 'You are banned from this site due to a bad ip.',
	801 => 'You are banned from this site due to a spam url in the referer header.',
	802 => 'You are banned from this site due to a unknown user-agent.',
	803 => 'You are banned from this site due to ignoring our anti-flood warnings.',

	'_FLOOD' => 'You are not allowed to flood our system.<br />You may view our website again after %s seconds',
	'Last_warning' => '<p>This is your last warning, next time you will be banned!</p>'
));
