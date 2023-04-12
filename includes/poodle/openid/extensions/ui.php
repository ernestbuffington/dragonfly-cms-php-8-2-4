<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version

	http://code.google.com/apis/accounts/docs/OpenID.html
*/

namespace Poodle\OpenID\Extensions;

class UI extends \Poodle\OpenID\Message_Fields
{
	public const
		NS_1_0 = 'http://specs.openid.net/extensions/ui/1.0';

	protected
		$valid_keys = array(
			# request
			'mode', // popup | x-has-session
			'icon'  // true
			# response
		);

	function __construct($uri, $alias=null)
	{
		parent::__construct(self::NS_1_0, $alias?$alias:'ui');
	}
}

\Poodle\OpenID\Message::registerNamespaceClass(UI::NS_1_0, 'Poodle\\OpenID\\Extensions\\UI');
