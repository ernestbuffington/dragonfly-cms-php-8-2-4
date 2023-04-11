<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	http://dragonflycms.org

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://step2.googlecode.com/svn/spec/openid_oauth_extension/latest/openid_oauth_extension.html
*/

namespace Poodle\OpenID\Extensions;

class OAUTH extends \Poodle\OpenID\Message_Fields
{
	const
		NS_1_0 = 'http://specs.openid.net/extensions/oauth/1.0';

	protected
		$valid_keys = array(
			# request
			'consumer', 'scope',
			# response
			'request_token'
		);

	function __construct($uri, $alias=null)
	{
		parent::__construct(self::NS_1_0, $alias?$alias:'oauth');
	}
}

\Poodle\OpenID\Message::registerNamespaceClass(OAUTH::NS_1_0, 'Poodle\\OpenID\\Extensions\\OAUTH');
