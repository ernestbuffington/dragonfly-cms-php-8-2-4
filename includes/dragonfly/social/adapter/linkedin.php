<?php
/*********************************************
 *  CPG Dragonfly™ CMS
 *********************************************
	Copyright © since 2010 by CPG-Nuke Dev Team
	http://dragonflycms.org

	Dragonfly is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

# https://developer.linkedin.com/share-plugin
# https://developer.linkedin.com/share-plugin-reference
namespace Dragonfly\Social\Adapter;

class Linkedin extends \Dragonfly\Social\Adapter
{

	protected
		$install = array(
			'active' => 0,
			'sharebutton_counter' => 'right',
			'sharebutton_showzero' => 'true',
			'sharebutton_width' => '80');

	function __construct()
	{
		parent::init('Linkedin');
	}

	public function loadApi() {
		static $done;
		if (empty($done)) {
			\Dragonfly\Output\Css::inline('.linkedin-sb{display:inline;width:'.$this->sharebutton_width.'px}');
			\Dragonfly\Output\Js::inline('
Poodle.onDOMReady(function(){Poodle.loadScript("//platform.linkedin.com/in.js")})');
			$done = 1;
		}
	}

	public function html5Button(array $args=array()) {
		return '<div class="linkedin-sb"><script type="IN/Share"
 '.(empty($args['url']) ? '' : 'data-url="'.$args['url'].'"').'
 data-counter="'.$this->sharebutton_counter.'"
 data-showzero="'.$this->sharebutton_showzero.'"
 ></script></div>';
	}

	public function htmlHeadTags() {}
}
