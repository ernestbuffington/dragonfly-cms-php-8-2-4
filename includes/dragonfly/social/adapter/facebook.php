<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

# https://developers.facebook.com/docs/reference/plugins/like/
# http://developers.facebook.com/docs/opengraphprotocol/

namespace Dragonfly\Social\Adapter;

class Facebook extends \Dragonfly\Social\Adapter
{

	protected
		$install = array(
			'active' => 0,
			'appid' => '',
			'admins' => '',
			'sharebutton_action' => 'like',
			'sharebutton_colorscheme' => 'light',
			'sharebutton_font' => '',
			'sharebutton_layout' => 'button_count',
			'sharebutton_send' => 'false',
			'sharebutton_show_faces' => 'false',
			'sharebutton_width' => '80');

	function __construct()
	{
		parent::init('Facebook');
	}

	public function loadApi() {
		static $done;
		//Poodle.onDOMReady( function() {
			// Poodle.$Q("html",1).attr("xmlns:fb","http://ogp.me/ns/fb#");
		//});
		//.js#status=true&cookie=true&xfbml=1&appId='.self::$appid.'");
		if (empty($done)) {
			$done = 1;
			\Dragonfly\Output\Css::inline('.fb-like{width:'.$this->sharebutton_width.'px;margin:0 2px}');
			\Dragonfly\Output\Js::inline('
Poodle.onDOMReady(function(){
 Poodle.loadScript("//connect.facebook.net/en_US/all.js#xfbml=1");
})
');
		}
	}

	public function htmlHeadTags() {
		if ($this->admins) {
			$admins = array_map('trim', explode(',', $this->admins));
			foreach ($admins as $admin) {
				\Dragonfly\Page::metatag('fb:admins', $admin, 'property');
			}

		}
		\Dragonfly\Page::metatag('fb:appid', $this->appid, 'property');
	}


	public function html5Button(array $args=array()) {
		$ret = '<div id="fb-root"></div><div class="fb-like"';
		if (!empty($args['url'])) $ret .= ' data-href="'.$args['url'].'"';
		if (!empty($args['ref'])) $ret .= ' data-ref="'.$args['ref'].'"';
		if ('recommend' === $this->sharebutton_action) $ret .= ' data-action="recommend"';
		if ('dark' === $this->sharebutton_colorscheme) $ret .= ' data-colorscheme="dark"';
		if ($this->sharebutton_font)       $ret .= ' data-font="'.$this->sharebutton_font.'"';
		if ($this->sharebutton_layout)     $ret .= ' data-layout="'.$this->sharebutton_layout.'"';
		if ($this->sharebutton_send)       $ret .= ' data-send="'.$this->sharebutton_send.'"';
		if ($this->sharebutton_show_faces) $ret .= ' data-show-faces="'.$this->sharebutton_show_faces.'"';
		if ($this->sharebutton_width)      $ret .= ' data-width="'.$this->sharebutton_width.'"';
		return $ret .='></div>';
	}
}
