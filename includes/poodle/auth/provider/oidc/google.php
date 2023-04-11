<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	https://developers.google.com/accounts/docs/OpenIDConnect

	https://console.developers.google.com/
	Google doesn't like testing with local IP's, use a domain name
	Error: invalid_request device_id and device_name are required for private IP: 192.168.1.20

	Access Not Configured. The API (Google+ API) is not enabled for your project.
	Please use the Google Developers Console to update your configuration.
	Free quota: 10,000 requests/day
*/

namespace Poodle\Auth\Provider\OIDC;

class Google extends \Poodle\Auth\Provider\OpenIDConnect
{
	const
		ISSUER_URI = 'https://accounts.google.com';
}
