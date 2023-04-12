<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://openid.net/specs/openid-provider-authentication-policy-extension-1_0.html
*/

namespace Poodle\OpenID\Extensions;

class PAPE extends \Poodle\OpenID\Message_Fields
{
	public const
		NS_1_0 = 'http://specs.openid.net/extensions/pape/1.0',

		# 4
		AUTH_MULTI_FACTOR          = 'http://schemas.openid.net/pape/policies/2007/06/multi-factor',
		AUTH_MULTI_FACTOR_PHYSICAL = 'http://schemas.openid.net/pape/policies/2007/06/multi-factor-physical',
		AUTH_PHISHING_RESISTANT    = 'http://schemas.openid.net/pape/policies/2007/06/phishing-resistant';

	protected
		$valid_keys = array(
			'auth_level.ns.*',            # <cust> optional
			# 5.1 request
			'max_auth_age',               # optional
			'preferred_auth_policies',    # optional
			'preferred_auth_level_types', # not required nor optional
			# 5.2 response
			'auth_policies',              # not required nor optional
			'auth_time',                  # optional, 0000-00-00T00:00:00Z, RFC3339
			'auth_level.*',               # <cust> optional
		);

	function __construct($uri, $alias=null)
	{
		parent::__construct(self::NS_1_0, $alias?$alias:'pape');
	}

	# TODO: overwrite methods to support wildcards
}

\Poodle\OpenID\Message::registerNamespaceClass(PAPE::NS_1_0, 'Poodle\\OpenID\\Extensions\\PAPE');
