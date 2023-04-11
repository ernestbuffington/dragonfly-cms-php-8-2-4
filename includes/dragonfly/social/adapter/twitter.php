<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

# https://dev.twitter.com/docs/cards
namespace Dragonfly\Social\Adapter;

class Twitter extends \Dragonfly\Social\Adapter
{

	protected
		$install = array(
			'active' => 0,
			'sharebutton_hashtags' => '',
			'sharebutton_dnt' => 'true',
			'sharebutton_related' => '',
			'sharebutton_via' => '',
			'sharebutton_size' => 'small',
			'sharebutton_showcount' => '1');

	function __construct()
	{
		parent::init('Twitter');
	}

	public function loadApi() {
		static $done;
		if (empty($done)) {
			\Dragonfly\Output\Css::inline('.twitter-share-button{width:90px !important}');
			\Dragonfly\Output\Js::inline('Poodle.onDOMReady(function(){Poodle.loadScript("//platform.twitter.com/widgets.js")})');
			$done = 1;
		}
	}

	public function html5Button(array $args=array()) {
		$ret = '<a class="twitter-share-button" href="https://twitter.com/share"';
		if (!empty($args['url']))   $ret .= ' data-href="'.$args['url'].'"';
		if (!empty($args['title'])) $ret .= ' data-text="'.$args['title'].'"';
		if ($this->sharebutton_via)      $ret .= ' data-via="'.$this->sharebutton_via.'"';
		if ($this->sharebutton_hashtags) $ret .= ' data-hashtags="'.$this->sharebutton_hashtags.'"';
		if ($this->sharebutton_related)  $ret .= ' data-related="'.$this->sharebutton_related.'"';
		if ($this->sharebutton_dnt)      $ret .= ' data-dnt="'.$this->sharebutton_dnt.'"';
		if (!$this->sharebutton_showcount) $ret .= ' data-count="none"';
		if ('large' === $this->sharebutton_size) $ret .= ' data-size="large"';
		return $ret .= '>Tweet</a>';
	}

	public function htmlHeadTags() {}
}
