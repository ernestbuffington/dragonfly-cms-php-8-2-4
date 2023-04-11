<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	https://dragonfly.coders.exchange

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

# https://developers.google.com/+/plugins/+1button/
namespace Dragonfly\Social\Adapter;

class Googleplus extends \Dragonfly\Social\Adapter
{

	protected
		$install = array(
			'active' => 0,
			'sharebutton_annotation' => 'bubble',
			'sharebutton_recommendations' => 'false',
			'sharebutton_size' => 'medium',
			'sharebutton_width' => '65');

	function __construct()
	{
		parent::init('Googleplus');
	}

	public function loadApi() {
		static $done;
		if (empty($done)) {
			\Dragonfly\Output\Css::inline('#___plusone_0{width:'.$this->sharebutton_width.'px !important}');
			\Dragonfly\Output\Js::inline('
window.___gcfg = { lang: "en-US" };
Poodle.onDOMReady(function(){Poodle.loadScript("https://apis.google.com/js/plusone.js")})');
			$done = 1;
		}
	}

	public function html5Button(array $args=array()) {
		$ret = '<div class="g-plusone"><g:plusone>';
		if (!empty($args['url'])) $ret .= ' data-href="'.$args['url'].'"';
		if ($this->sharebutton_width) $ret .=  ' data-width="'.$this->sharebutton_width.'"';
		if ($this->sharebutton_size)  $ret .= ' data-size="'.$this->sharebutton_size.'"';
		if ($this->sharebutton_annotation)      $ret .= ' data-annotation="'.$this->sharebutton_annotation.'"';
		if ($this->sharebutton_recommendations) $ret .=  ' data-recommendations="'.$this->sharebutton_recommendations.'"';
		return $ret .='</g:plusone></div>';
	}

	public function htmlHeadTags() {}
}
